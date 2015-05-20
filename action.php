<?php
$dir = dirname(__FILE__).'/';
include_once $dir.'config.php';
include_once $dir.'class/System.class.php';
include_once $dir.'class/Database.class.php';
include_once $dir.'class/Notifier.class.php';
include_once $dir.'class/TorrentClient.class.php';
include_once $dir.'class/Errors.class.php';
include_once $dir.'class/Trackers.class.php';
include_once $dir."class/Lib/rain.tpl.class.php";

if (isset($_POST['action']))
{
    $action = $_POST['action'];

    //Проверяем пароль
    if ($action == 'enter')
    {
        $password = md5($_POST['password']);
        $count = Database::countCredentials($password);

        if ($count == 1)
        {
            session_start();
            $_SESSION['TM'] = $password;
            $return['error'] = FALSE;
            if ($_POST['remember'] == 'true')
                setcookie('TM', $password, time()+3600*24*31);
            $return['msg'] = 'Вход выполнен успешно.';
        }
        else
        {
            $return['error'] = TRUE;
            $return['msg'] = 'Неверный пароль!';
        }
        echo json_encode($return);
    }

    //Добавляем тему для мониторинга
    elseif ($action == 'torrent_add')
    {
        if ($url = parse_url($_POST['url']))
        {
            $tracker = Trackers::getTrackerName( preg_replace('/www\./', '', $url['host']) );
            $threme  = Trackers::getThreme($tracker, $_POST['url']);

            if (is_array(Database::getCredentials($tracker)))
            {
                if (Trackers::moduleExist($tracker))
                {
                    if (Trackers::checkRule($tracker, $threme))
                    {
                        if (Database::checkThremExist($tracker, $threme))
                        {
                            if ( ! empty($_POST['name']))
                                $name = $_POST['name'];
                            else
                                $name = Sys::getHeader($_POST['url']);

                            Database::setThreme($tracker, $name, $_POST['path'], $threme, Sys::strBoolToInt($_POST['update_header']));
                            $return['error'] = FALSE;
                            $return['msg'] = 'Тема добавлена для мониторинга.';
                        }
                        else
                        {
                            $return['error'] = TRUE;
                            $return['msg'] = 'Вы уже следите за данной темой на трекере <b>'.$tracker.'</b>.';
                        }
                    }
                    else
                    {
                        $return['error'] = TRUE;
                        $return['msg'] = 'Не верная ссылка.';
                    }
                }
                else
                {
                    $return['error'] = TRUE;
                    $return['msg'] = 'Отсутствует модуль для трекера - <b>'.$tracker.'</b>.';
                }
            }
            else
            {
                $return['error'] = TRUE;
                $return['msg'] = 'Вы не можете следить за этим сериалом на трекере - <b>'.$tracker.'</b>, пока не введёте свои учётные данные!';
            }
        }
        else
        {
            $return['error'] = TRUE;
            $return['msg'] = 'Не верная ссылка.';
        }
        echo json_encode($return);
    }

    //Добавляем сериал для мониторинга
    elseif ($action == 'serial_add')
    {
        $tracker = $_POST['tracker'];
        if (is_array(Database::getCredentials($tracker)))
        {
            if (Trackers::moduleExist($tracker))
            {
                if (Trackers::checkRule($tracker, $_POST['name']))
                {
                    if (Database::checkSerialExist($tracker, $_POST['name'], $_POST['hd']))
                    {
                        Database::setSerial($tracker, $_POST['name'], $_POST['path'], $_POST['hd']);
                        $return['error'] = FALSE;
                        $return['msg'] = 'Сериал добавлен для мониторинга.';
                    }
                    else
                    {
                        $return['error'] = TRUE;
                        $return['msg'] = 'Вы уже следите за данным сериалом на этом трекере - <b>'.$tracker.'</b>.';
                    }
                }
                else
                {
                    $return['error'] = TRUE;
                    $return['msg'] = 'Название содержит недопустимые символы.';
                }
            }
            else
            {
                $return['error'] = TRUE;
                $return['msg'] = 'Отсутствует модуль для трекера - <b>'.$tracker.'</b>.';
            }
        }
        else
        {
            $return['error'] = TRUE;
            $return['msg'] = 'Вы не можете следить за этим сериалом на трекере - <b>'.$tracker.'</b>, пока не введёте свои учётные данные!';
        }
        echo json_encode($return);
    }

    //Обновляем отслеживаемый item
    elseif ($action == 'update')
    {
        $tracker = $_POST['tracker'];
        $reset   = Sys::strBoolToInt($_POST['reset']);

        $trackerType = Trackers::getTrackerType($tracker);

        if ($trackerType == 'series')
        {
            if (Trackers::checkRule($tracker ,$_POST['name']))
            {
                Database::updateSerial($_POST['id'], $_POST['name'], $_POST['path'], $_POST['hd'], $reset, $_POST['script']);

                $return['error'] = FALSE;
                $return['msg'] = 'Сериал обновлён.';
            }
            else
            {
                $return['error'] = TRUE;
                $return['msg'] = 'Название содержит недопустимые символы.';
            }
        }
        else if ($trackerType == 'threme')
        {
            $url = parse_url($_POST['url']);
            $tracker = Trackers::getTrackerName( preg_replace('/www\./', '', $url['host']) );
            $threme  = Trackers::getThreme($tracker, $_POST['url']);

            $update = Sys::strBoolToInt($_POST['update']);

            if (Trackers::checkRule($tracker, $threme))
            {
                Database::updateThreme($_POST['id'], $_POST['name'], $_POST['path'], $threme, $update, $reset, $_POST['script']);

                $return['error'] = FALSE;
                $return['msg'] = 'Тема обновлена.';
            }
            else
            {
                $return['error'] = TRUE;
                $return['msg'] = 'Не верный ID темы.';
            }
        }
        echo json_encode($return);
    }

    //Добавляем пользователя для мониторинга
    elseif ($action == 'user_add')
    {
        $tracker = $_POST['tracker'];
        if (is_array(Database::getCredentials($tracker)))
        {
            if (Trackers::moduleExist($tracker))
            {
                if (Database::checkUserExist($tracker, $_POST['name']))
                {
                    Database::setUser($tracker, $_POST['name']);
                    $return['error'] = FALSE;
                    $return['msg'] = 'Пользователь добавлен для мониторинга.';
                }
                else
                {
                    $return['error'] = TRUE;
                    $return['msg'] = 'Вы уже следите за данным пользователем на этом трекере - <b>'.$tracker.'</b>.';
                }
            }
            else
            {
                $return['error'] = TRUE;
                $return['msg'] = 'Отсутствует модуль для трекера - <b>'.$tracker.'</b>.';
            }
        }
        else
        {
            $return['error'] = TRUE;
            $return['msg'] = 'Вы не можете следить за этим пользователем на трекере - <b>'.$tracker.'</b>, пока не введёте свои учётные данные!';
        }
        echo json_encode($return);
    }

    //Удаляем пользователя из мониторинга и все его темы
    elseif ($action == 'delete_user')
    {
        Database::deletUser($_POST['user_id']);
        $return['error'] = FALSE;
        $return['msg'] = 'Слежение за пользователем удалено.';
        echo json_encode($return);
    }

    //Удаляем тему из буфера
    elseif ($action == 'delete_from_buffer')
    {
        Database::deleteFromBuffer($_POST['id']);
        $return['error'] = FALSE;
        $return['msg'] = 'Тема удалена из буфера.';
        echo json_encode($return);
    }

    //Очищаем весь список тем
    elseif ($action == 'threme_clear')
    {
        $array = Database::selectAllFromBuffer();
        for($i=0; $i<count($array); $i++)
        {
            Database::deleteFromBuffer($array[$i]['id']);
        }
        Database::deleteFromBuffer($_POST['id']);
        $return['error'] = FALSE;
        $return['msg'] = 'Буфер очищен.';
        echo json_encode($return);
    }

    //Перемещаем тему из буфера в мониторинг постоянный
    elseif ($action == 'transfer_from_buffer')
    {
        Database::transferFromBuffer($_POST['id']);
        $return['error'] = FALSE;
        $return['msg'] = 'Тема перенесена из буфера.';
        echo json_encode($return);
    }

    //Помечаем тему для скачивания
    elseif ($action == 'threme_add')
    {
        $update = Database::updateThremesToDownload($_POST['id']);
        if ($update)
        {
            $return['error'] = FALSE;
        }
        else
        {
            $return['error'] = TRUE;
            $return['msg'] = 'Пометить тему для закачки.';
        }
        echo json_encode($return);
    }

    //Удаляем мониторинг
    elseif ($action == 'del')
    {
        Database::deletItem($_POST['id']);
        $return['error'] = FALSE;
        $return['msg'] = 'Удалено.';
        echo json_encode($return);
    }

    //Обновляем личные данные
    elseif ($action == 'update_credentials')
    {
        if ( ! isset($_POST['passkey']))
            $_POST['passkey'] = '';
        Database::setCredentials($_POST['id'], $_POST['log'], $_POST['pass'], $_POST['passkey']);
        $return['error'] = FALSE;
        $return['msg'] = 'Данные для трекера обновлены.';
        echo json_encode($return);
    }

    //Обновляем настройки
    elseif ($action == 'update_settings')
    {
        Database::updateSettings('serverAddress', Sys::checkPath($_POST['serverAddress']));
        Database::updateSettings('auth', Sys::strBoolToInt($_POST['auth']));
        Database::updateSettings('proxy', Sys::strBoolToInt($_POST['proxy']));
        Database::updateSettings('autoProxy', Sys::strBoolToInt($_POST['autoProxy']));
        Database::updateSettings('proxyType', $_POST['proxyType']);
        Database::updateSettings('proxyAddress', $_POST['proxyAddress']);
        Database::updateSettings('rss', Sys::strBoolToInt($_POST['rss']));
        Database::updateSettings('debug', Sys::strBoolToInt($_POST['debug']));

        $return['error'] = FALSE;
        $return['msg'] = 'Настройки монитора обновлены.';
        echo json_encode($return);
    }
    elseif ($action == 'updateTorrentClientSettings')
    {
        $params = json_decode($_POST['params']);

        $useTorrent  = $params->torrent;
        $clientClass = $params->torrentClient;

        Database::updateSettings('useTorrent', $useTorrent);

        $client = TorrentClient::Create($clientClass);
        if ($client != NULL)
        {
            if ($useTorrent == FALSE)
                Database::removePluginSettings($client);
            else
            {
               $client->SetParams($params->settings);
            }
            $client = NULL;
        }

        $return['error'] = FALSE;
        $return['msg'] = 'Настройки торрент-клиента обновлены.';
        echo json_encode($return);
    }
    elseif ($action == 'updateNotifierSettings')
    {
        $notifiersSettings = json_decode($_POST['settings'], true);
        foreach ($notifiersSettings as $key => $settings)
        {
            $notifier = Notifier::Create($settings['notifier'], $settings['group']);
            if ($notifier != NULL)
                $notifier->SetParams($settings['address'], $settings['sendUpdate'], $settings['sendWarning'], $settings['sendNews']);
            $notifier = NULL;
        }
        $return['error'] = FALSE;
        $return['msg'] = 'Настройки уведомлений обновлены.';
        echo json_encode($return);
    }

    //Меняем пароль
    elseif ($action == 'change_pass')
    {
        $pass = md5($_POST['pass']);
        $q = Database::updateCredentials($pass);
        if ($q)
        {
            $return['error'] = FALSE;
            $return['msg'] = 'Пароль успешно изменен.';
        }
        else
        {
            $return['error'] = TRUE;
            $return['msg'] = 'Не удалось сменить пароль!';
        }
        echo json_encode($return);
    }

    //Добавляем тему на закачку
    elseif ($action == 'download_thremes')
    {
        if ( ! empty($_POST['checkbox']))
        {
            $arr = $_POST['checkbox'];
            foreach ($arr as $id => $val)
            {
                Database::updateDownloadThreme($id);
            }
            $return['error'] = FALSE;
            $return['msg'] = count($arr).' тем помечено для закачки.';
            echo json_encode($return);
        }
        Database::updateDownloadThremeNew();
    }

    //Помечаем новость как прочитанную
    elseif ($action == 'markNews')
    {
        Database::markNews($_POST['id']);
        return TRUE;
    }

    //Выполняем обновление системы
    elseif ($action == 'system_update')
    {
        Sys::launchUpdate();
    }

    // Получаем список доступных нотификаторов
    elseif ($action == 'getNotifierList')
    {
        echo json_encode(Sys::getNotifiers());
    }

    elseif ($action == 'removeNotifierSettings')
    {
        $notifier = Notifier::Create($_POST['notifierClass'], $_POST['group']);
        if ($notifier != NULL)
            Database::removePluginSettings($notifier);
        $notifier = NULL;
    }

    //Возвращаем содержимое страницы index в зависимости от состояния авторизации
    elseif ($action == 'getIndexContent')
    {
        $result = array();

        // заполнение шаблона
        raintpl::configure("root_dir", $dir );
        raintpl::configure("tpl_dir" , Sys::getTemplateDir() );

        if (Sys::checkAuth())
        {
            $errors = Database::getWarningsCount();

            $count = 0;
            if ( ! empty($errors))
                for ($i=0; $i<count($errors); $i++)
                    $count += $errors[$i]['count'];

            $updateInfo = Sys::checkUpdate();

            $tpl = new RainTPL;
            $tpl->assign( "updateState", $updateInfo['update'] ? 'block' : 'none' );
            $tpl->assign( "updateMsg"  , $updateInfo['msg'] );
            $tpl->assign( "version"    , $updateInfo['ver'] );
            $tpl->assign( "error_count", $count );

            $result['content'] = $tpl->draw( 'index_main', true );
            $result['type'] = 'main';
        }
        else
        {
            $tpl = new RainTPL;
            $result['content'] = $tpl->draw( 'index_auth', true );
            $result['type'] = 'auth';
        }

        echo $result['content'];
    }

    //Возвращаем информацию об обновлениях и актуальную версию
    elseif ($action == 'getUpdateInfo') {
        $result = Sys::checkUpdate();
        echo json_encode($result);
    }

    // Неизвестная команда
    else {
        $return['error'] = TRUE;
        $return['msg'] = 'Неизвестная команда "'.$_POST['action'].'"';
        echo json_encode($return);
    }
}

if (isset($_GET['action']))
{
    //Сортировка вывода торрентов
    if ($_GET['action'] == 'order')
    {
        session_start();
        if ($_GET['order'] == 'date')
            setcookie('order', 'date', time()+3600*24*365);
        elseif ($_GET['order'] == 'dateDesc')
            setcookie('order', 'dateDesc', time()+3600*24*365);
        elseif ($_GET['order'] == 'name')
            setcookie('order', '', time()+3600*24*365);
        header('Location: index.php');
    }
}
?>