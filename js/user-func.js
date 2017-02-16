$( document ).ready(function() 
{    // Скользящее меню
    $(".h-menu li").hover(
        function() {
            $(this).stop().animate({width: "235px"}, 500);
        },
        function() {
            if ($(this).hasClass("active")==false) {
                $(this).stop().animate({width: "27px"}, 500);
            }
        }
    );
    
    // Раскрывающийся список торрентов пользователя за которым ведётся мониторинг
    $(".user-torrent").click(function() {
        $(this).toggleClass("active");
        $(this).next().toggle();
        var id = $(this).attr('id');
        var date = new Date(new Date().getTime() + 60*1000);
        document.cookie="id="+id+"; path=/; expires="+date.toUTCString();
    });

    // Меню
    $(".h-menu li").click(function() {
        $(".h-menu li").stop().animate({width: "27px"}, 500);
        $(".h-menu li").removeClass("active");
        $(this).stop().animate({width: "235px"}, 500);
        $(this).addClass("active");
    });

    //Передаём пароль
    $("#enter").submit(function() {
        var $form = $(this),p = $form.find('input[name="password"]').val(),
        r_m = $form.find('input[name="remember"]').prop('checked');
        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'enter', password: p, remember: r_m},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else {
                    ohSnap(data.msg, 'green');
                    showIndexContent();
                }
                console.log(data.error)
            }, "json"
        );
        
        return false;
    });

    //Передаём тему для мониторинга
    $("#torrent_add").submit(function()
    {
        var $form = $(this),
            s = $form.find('input[type=submit]'),
            n_f = $form.find('input[name="name"]'),
            n = $(n_f).val(),
            u_f = $form.find('input[name="url"]'),
            u = $(u_f).val(),
            p_f = $form.find('input[name="path"]'),
            p = $(p_f).val(),
            u_h = $form.find('input[name="update_header"]').prop('checked');

        if (u == '')
        {
            ohSnap('Вы не указали ссылку на тему!', 'red');
            return false;
        }
                                    
        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'torrent_add', name: n, url: u, path: p, update_header: u_h},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                $(n_f).val('');
                $(u_f).val('');
                    ohSnap(data.msg, 'green');
                }
            }, "json"
        );
        return false;
    });

    //Передаём сериал для мониторинга
    $("#serial_add").submit(function()
    {
        formError = "";
        var $form = $(this),
            s = $form.find('input[type=submit]'),
            t = $form.find('select[name="tracker"]').val(),
            n_f = $form.find('input[name="name"]'),
            n = $(n_f).val(),
            h_f = $form.find('input[name="hd"]'),
            p_f = $form.find('input[name="path"]'),
            p = $(p_f).val();

        h = $(h_f).val();
        qualitySelected = false;
        for (var i = 0; i < h_f.length; i++)
        {
            if (h_f[i].checked)
            {
                var $form = $(this), h = h_f[i].value;
                qualitySelected = true;
            }
        }

        if (t == '')
            formError += "Вы не выбрали трекер!<br>";
        
        if (n == '')
            formError += "Вы не указали название сериала!<br>";

        if (!qualitySelected)
            formError += "Вы не выбрали качество!";

        if (formError != "")
        {
            ohSnap(formError, 'red');
            return false;
        }

        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'serial_add', tracker: t, name: n, hd: h, path: p},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                $(n_f).val('');
                $(h_f).removeAttr('checked');
                    ohSnap(data.msg, 'green');
                }
            }, "json"
        );
        return false;
    });
    
    //Передаём данные для обновления
    $("#torrent_update").submit(function()
    {
        formError = "";
        var $form = $(this),
            s = $form.find('input[type=submit]'),
            i_f = $form.find('input[name="id"]'),
            id = $(i_f).val(),
            t_f = $form.find('input[name="tracker"]'),
            t = $(t_f).val(),
            n_f = $form.find('input[name="name"]'),
            n = $(n_f).val(),
            u_f = $form.find('input[name="url"]'),
            u = $(u_f).val(),
            update = $form.find('input[name="update"]').prop('checked'),
            p_f = $form.find('input[name="path"]'),
            p = $(p_f).val();
            s_f = $form.find('input[name="script"]'),
            s = $(s_f).val();
            h_f = $form.find('input[name="hd"]'),
            r_f = $form.find('input[name="reset"]').prop('checked');
            
        h = $(h_f).val();
        for (var i = 0; i < h_f.length; i++)
        {
            if (h_f[i].checked)
            {
                var $form = $(this), h = h_f[i].value
            }
        }
            
        if (u == '')
            formError += "Вы не указали ссылку на тему!\n";
        
        if (n == '')
            formError += "Вы не указали название сериала!";

        if (formError != "")
        {
            ohSnap(formError, 'red');
            return false;
        }

        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'update', id: id, tracker: t, name: n, url: u, update: update, path: p, script: s, hd: h, reset: r_f},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
            }
                else
                {
                    $.get("include/show_table.php",
            		    function(data) {
            			    $('#content').delay(3000).empty().append(data);
            		    }
                    );                    
                    $('.coverAll').hide();
                    $('.blok').empty();
                    ohSnap(data.msg, 'green');
                }
            }, "json"
        );

        return false;
    });    

    //Передаём пользователя для мониторинга
    $("#user_add").submit(function()
    {
        formError = "";
        var $form = $(this),
            s = $form.find('input[type=submit]'),
            t = $form.find('select[name="tracker"]').val(),
            n_f = $form.find('input[name="name"]'),
            n = $(n_f).val();

        if (t == '')
            formError += "Вы не выбрали трекер!\n";
        
        if (n == '')
            formError += "Вы не указали имя пользователя!";

        if (formError != "")
        {
            ohSnap(formError, 'red');
            return false;
        }
        
        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'user_add', tracker: t, name: n},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    ohSnap(data.msg, 'green');
                    $(n_f).val('');
                }
            }, "json"
        );
        return false;
    });
    
    //Удаляем темы 
    $("#threme_clear").submit(function()
    {
        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'threme_clear'},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    ohSnap(data.msg, 'green');
                    $.get("include/show_watching.php",
                        function(data) {
                            $('#content').empty().append(data);
                        }
                    );
                }
            }, "json"
        );
        return false;
    });

    //Передаём личные данные
    $("#credential").submit(function()
    {
        formError = "";
        var $form = $(this),
            b = $form.find('input[type=button]'),
            id = $form.find('input[name="id"]').val(),
            l = $form.find('input[name="log"]').val(),
            p = $form.find('input[name="pass"]').val(),
            passkey = $form.find('input[name="passkey"]').val();

        if (l == '')
            formError += "Вы не указали логин!\n";
        
        if (p == '')
            formError += "Вы не указали пароль!";

        if (formError != "")
        {
            ohSnap(formError, 'red');
            return false;
        }
                                    
        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'update_credentials', id: id, log: l, pass: p, passkey: passkey},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    $(b).removeAttr('disabled');
                    ohSnap(data.msg, 'green');
                }
            }, "json"
        );
        return false;
    });
        
    //Передаём настройки
    $("#setting").submit(function()
    {
        formError = "";
        var $form = $(this),
            s = $form.find('input[type=submit]'),
            serverAddress = $form.find('input[name="serverAddress"]').val();
            auth = $form.find('input[name="auth"]').prop('checked');
            proxy = $form.find('input[name="proxy"]').prop('checked');
            autoProxy = $form.find('input[name="autoProxy"]').prop('checked');
            proxyType = $form.find('select[name="proxyType"]').val();
            proxyAddress = $form.find('input[name="proxyAddress"]').val();
            rss = $form.find('input[name="rss"]').prop('checked');
            debug = $form.find('input[name="debug"]').prop('checked');
        
        if (serverAddress == '')
            formError += "Вы не указали адрес сервера TM.\n";
        
        if (proxy == 'checked' && proxyAddress == '')
            formError += "Вы не указали адрес proxy-сервера.\n";
        
        if (formError != "")
        {
            ohSnap(formError, 'red');
            return false;
        }

        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'update_settings', serverAddress: serverAddress, 
            auth: auth, proxy: proxy, autoProxy: autoProxy, proxyType: proxyType, proxyAddress: proxyAddress,
            rss: rss, debug: debug},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    ohSnap(data.msg, 'green');
                }
            }, "json"
        );
        return false;
    });

    //Передаём пароль
    $("#change_pass").submit(function()
    {
        formError = "";
        var $form = $(this),
            s = $form.find('input[type=submit]'),
            p = $form.find('input[name="password"]').val(),
            p2 = $form.find('input[name="password2"]').val();
            
        if (p == '')
            formError += "Пароль не может быть пустым.\n";
        
        if (p != p2) 
            formError += "Пароль и подтверждение должны совпадать.";

        if (formError != "")
        {
            ohSnap(formError, 'red');
            return false;
        }
        
        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'change_pass', pass: p},
            function(data) {
                if (data.error)
                {
                    ohSnap(data, 'green');
                }
                else
                    document.location.reload();
            }, "json"
        );
        return false;
    });
    
    //Вызов процедуры обновления
    $("#system_update").submit(function()
    {
        $('#system_update').empty().append('<div id="loader"></div>');
        
        $.post("action.php",{action: 'system_update'},
            function(data) {
                $('#system_update').empty().html(data);
                checkUpdate();
            }
        );
    });

    //Сохраняем настройки торрент-клиента
    $("#torrent_client_settings").submit(function()
    {
        formError = "";
        var $form = $(this),
            //s = $form.find('input[type=submit]'),
            torrent = $form.find('input[name="torrent"]').prop('checked');
            torrentClient = $form.find('select[name="torrentClient"]').val().replace('_lable', '');

        var params = new Object;
        params['torrent'] = torrent;
        params['torrentClient'] = torrentClient;
        
        var torrentSettings = new Object;
        $('.' + torrentClient + '_setting').each(function(){
            torrentSettings[$(this).attr('name')] = getElementValue($(this));
        });

        params['settings'] = torrentSettings;
        
        if (torrent == 'checked' && torrentClient == ''  && torrentSettings['torrentAddress'] == '' && torrentSettings['pathToDownload'] == '')
            formError += "Вы не указали настройки торрент-клиента.";

        if (formError != "")
        {
            ohSnap(formError, 'red');
            return false;
        }

        ohSnap('Обрабатывается запрос...', 'yellow');
        $.post("action.php",{action: 'updateTorrentClientSettings', params: JSON.stringify(params)},
            function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    ohSnap(data.msg, 'green');
                }
            }, "json"
        );
        return false;
    });

    //Сохраняем настройки уведомлений
    $("#notifier_settings").submit(function()
    {
        //debugger;        
        var table = document.getElementById('notifiers-table');

        var notifiersSettings = {}; 
        for (var i = 1, row; row = table.rows[i]; i++) {
            var notifier = {};
            notifier["group"] = parseInt(row.getAttribute('group'));

            var notifSelect = row.children[0].children[0];
            notifier["notifier"] = notifSelect.options[notifSelect.selectedIndex].value;

            notifier["address"] = row.children[1].children[0].value;
            notifier["sendUpdate"] = row.children[2].children[0].checked;
            notifier["sendWarning"] = row.children[3].children[0].checked;
            notifier["sendNews"] = row.children[4].children[0].checked;

            notifiersSettings[i - 1] = notifier;
        } 
        ohSnap('Обрабатывается запрос...', 'yellow');
        var settings = JSON.stringify(notifiersSettings);
        $.post("action.php",{action: 'updateNotifierSettings', settings: settings},
            function(data) {
                if (data.error) {
                    ohSnap(data.msg, 'red');
                }
                else {
                    ohSnap(data.msg, 'green');
                }
            }, "json"
        );           
        return false;
    });

    // Поле ввода select, которое выполняет переключение видимости блоков div
    $(".select-switch").change(function() {
        changeDiv($(this).attr("id"), $(this).val());
    });

});

//Подгрузка страниц
function show(name)
{
    if (name == 'check' || name == 'execution' || name == 'update')
        $('#content').empty().append('<div id="loader"></div>');

    $.get("include/"+name+".php",
        function(data) {
            $('#content').empty().append(data);
    });
    
    if (name == 'check' || name == 'execution' || name == 'update')
        $('#content').empty().append('<div id="loader"></div>');

	if (name == 'show_table')
	{
		window.clearTimeout(this.timeoutID);
		this.timeoutID = window.setTimeout(function(){ show('show_table') },7000);
	}
	else if (name == 'show_warnings')
	{
		window.clearTimeout(this.timeoutID);
		this.timeoutID = window.setTimeout(function(){ show('show_warnings') },7000);
	}
	else
	{
		window.clearTimeout(this.timeoutID);
		delete this.timeoutID;
	}
	
	return false;
}

//Развернуть/свернуть слой
function expand(id)
{
	var div = "#"+id;
	if ($(div).is(":hidden"))
		$(div).slideDown("slow");
	else 
		$(div).slideUp("slow");
	return false;
}

//Получаем значение элемента
function getElementValue(element) {
    var value;
    
    if ( element.attr('type') == 'checkbox' )
        value = element.prop('checked');
    else
        value = element.val();
    
    return value;
}

//Удаляем пользователя
function delete_user(id)
{
    if (confirm("Удалить?"))
    {
    	ohSnap('Обрабатывается запрос...', 'yellow');
    	$.post("action.php",{action: 'delete_user', user_id: id},
    		function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    $.get("include/show_watching.php",
            		    function(data) {
            			    $('#content').delay(3000).empty().append(data);
            		    }
                    );
                    ohSnap(data.msg, 'green');
                }
            }, "json"
    	);
    	return false;
    }
}

//Удаляем тему из буфера
function delete_from_buffer(id)
{
    if (confirm("Удалить?"))
    {
    	ohSnap('Обрабатывается запрос...', 'yellow');
    	$.post("action.php",{action: 'delete_from_buffer', id: id},
    		function(data) {
    			if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    $.get("include/show_watching.php",
            		    function(data) {
            			    $('#content').delay(3000).empty().append(data);
            		    }
                    );
                    ohSnap(data.msg, 'green');
                }
            }, "json"
    	);
    	return false;
    }
}

//Перемещаем тему из буфера в мониторинг постоянный
function transfer_from_buffer(id)
{
	ohSnap('Обрабатывается запрос...', 'yellow');
	$.post("action.php",{action: 'transfer_from_buffer', id: id},
		function(data) {
                if (data.error)
                {
                    ohSnap(data.msg, 'red');
                }
                else
                {
                    $.get("include/show_watching.php",
            		    function(data) {
            			    $('#content').delay(3000).empty().append(data);
            		    }
                    );
                    ohSnap(data.msg, 'green');
                }
            }, "json"
	);
	return false;

}

//Передаём темы для скачивания
function threme_add(id, user_id)
{
        ohSnap('Обрабатывается запрос...', 'yellow');
	$.post("action.php",{action: 'threme_add', id: id, user_id: user_id},
		function(data) {
			if (data.error)
			{
                ohSnap('Ошибка передачи данных<br/>Попробуйте ещё раз.', 'red');
			}
			else
			{
				$.get("include/show_watching.php",
					function(data) {
						$('#content').empty().append(data);
					}
				);
                ohSnap(data.msg, 'green');
			}
		}, "json"
	);
	return false;
}

//Удаляем мониторинг
function del(id, name)
{
    if (confirm('Удалить '+name+'?'))
    {
    	ohSnap('Обрабатывается запрос...', 'yellow');
    	$.post("action.php",{action: 'del', id: id},
    		function(data) {
        		if (data.error)
    			{
                    ohSnap(data.msg, 'red');
    			}
    			else
    			{
    			$.get("include/show_table.php",
            		function(data) {
    						$('#content').empty().append(data);
            		}
            	);
                    ohSnap(data.msg, 'green');
    			}
    		}, "json"
    	);
    	return false;
    }
}

//Выводим нужный div в зависимости от выбранного в списке
function changeDiv(type, selectedValue)
{
    $('.' + type + '_label').each(function(){							
        if (selectedValue == $(this).attr('id') )
            $(this).show()
        else
            $(this).hide()
    });
}

//Меняем radiobutton
function changeField()
{
	var tracker = document.getElementById("tracker").value;
    if (tracker == 'baibako.tv' || tracker == 'hamsterstudio.org' || tracker == 'newstudio.tv' || tracker == 'novafilm.tv')
        $('#changedField').empty().append('<span class="quality"><input type="radio" name="hd" value="0"> SD<br /><input type="radio" name="hd" value="1"> HD 720<br /><input type="radio" name="hd" value="2"> HD 1080</span>');
	if (tracker == 'lostfilm.tv' || tracker == 'lostfilm-mirror')
		$('#changedField').empty().append('<span class="quality"><input type="radio" name="hd" value="0"> SD<br /><input type="radio" name="hd" value="1"> Автовыбор HD 720/1080<br /><input type="radio" name="hd" value="2"> HD 720 MP4');
}

//Форма редактирования записи
function showForm(id)
{
    $.get("include/form.php", {'id': id},
        function(data) {
            $('.blok').empty().append(data);
        }
    );
    $(".coverAll").show();
}

//Помечаем новость как прочитанную
function newsRead(id)
{
    $.post("action.php", {action: 'markNews', id: id},
        function(data) {
            $('.'+id).removeClass();
        }
    );
}

$(".add-notifier-title").click(function() {
    //debugger;
    var maxGroup = 0;
    var table = document.getElementById('notifiers-table');
    for (var i = 1, row; row = table.rows[i]; i++) {
        var rowGroup = parseInt(row.getAttribute('group'));
        if (rowGroup > maxGroup)
            maxGroup = rowGroup;
    }
    maxGroup += 1;

    var availableNotifiersHtml = "";
    $.post("action.php", {action: 'getNotifierList'},
		function(data) {
            for (var notifier in data) {
                    availableNotifiersHtml += "<option value=\"" + data[notifier].Name + "\" >" + data[notifier].VerboseName + "</option>";
            }
            var table_row = '<tr class="notifierSettings" group="' + maxGroup + '">' +
                    '<td class="notifierSettings"><select id="sendService" name="sendService" style="width: 150px;">' + availableNotifiersHtml + '</select> </td>' +
                    '<td class="notifierSettings"><input type="text" name="sendAddress" value="" style="width: 300px;"> </td>' +
                    '<td class="notifierSettings"><input type="checkbox" name="sendUpdate" > </td>' +
                    '<td class="notifierSettings"><input type="checkbox" name="sendWarning" > </td>' +
                    '<td class="notifierSettings"><input type="checkbox" name="sendNews" > </td>' +
                    '<td class="notifierSettings"><a class="delete" onclick="removeNotifierSetting(this)"></a> </td>' +
                    '</tr>';
            $(table_row).hide().insertAfter($('#notifiers-table tr:last')).removeClass("hide").addClass("show-row").hide().show('fast');
		}, "json"
	);
});

function removeNotifierSetting(btn) 
{
    var tr = btn.parentNode.parentNode;
    var group = tr.getAttribute('group');
    var notifSelect = tr.children[0].children[0];
    var notifier = notifSelect.options[notifSelect.selectedIndex].value;

    $.post("action.php", {action: 'removeNotifierSettings', notifierClass: notifier, group: group});

    tr.parentNode.removeChild(tr);
}


//Обновляем основную страницу без перезагрузки содержимого
function showIndexContent()
{
    $.post("action.php", {action: 'getIndexContent'},
        function(data) {
            $('#index_content').empty().append(data);
        }, "html"
    );
}

//Проверка информации об обновлениях
function checkUpdate() {
    $.post("action.php",{action: 'getUpdateInfo'},
        function(data) {
            if (data.update)
                $('#update_message').show();
            else
                $('#update_message').hide();
            
            $('#update_message').empty().html(data.msg);
            $('#versionInfo').empty().html(data.ver);
        }, "json"
    );
}

//Переводим фокус на переданное поле ввода 
function FocusOnInput(fieldName) {
    document.getElementById(fieldName).focus();
}
