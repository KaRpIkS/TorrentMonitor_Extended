<?php
class tvmekcinfo
{
    protected static $sess_cookie;
    protected static $exucution;
    protected static $warning;
    
    //проверяем cookie
    public static function checkCookie($sess_cookie)
    {
        $result = Sys::getUrlContent(
            array(
                'type'           => 'POST',
                'returntransfer' => 1,
                'url'            => 'http://tv.mekc.info/',
                'cookie'         => $sess_cookie,
                'sendHeader'     => array('Host' => 'tv.mekc.info', 'Content-length' => strlen($sess_cookie)),
                'convert'        => array('windows-1251', 'utf-8//IGNORE'),
            )
        );

        if (preg_match('/<a href=\"http:\/\/tv\.mekc\.info\/userdetails\.php\?id=.*\">.*<\/a>/U', $result))
            return TRUE;
        else
            return FALSE;          
    }
    
    public static function checkRule($data)
    {
        if (preg_match('/\D+/', $data))
            return FALSE;
        else
            return TRUE;
    }
    
    //функция преобразования даты
    private static function dateNumToString($data)
    {
        $data1 = explode(' ', $data);
        $data2 = explode('-', $data1[0]);

        $data3 = $data2[2].' '.Sys::dateNumToString($data2[1]).' '.$data2[0];
        $date = $data3.' в '.$data[1];        
        return $date;        
    }
    
    //функция получения кук
    protected static function getCookie($tracker)
    {
        //проверяем заполнены ли учётные данные
        if (Database::checkTrackersCredentialsExist($tracker))
        {
            //получаем учётные данные
            $credentials = Database::getCredentials($tracker);
            $login = iconv('utf-8', 'windows-1251', $credentials['login']);
            $password = $credentials['password'];
            
            //авторизовываемся на трекере
            $page = Sys::getUrlContent(
                array(
                    'type'           => 'POST',
                    'header'         => 1,
                    'returntransfer' => 1,
                    'url'            => 'http://tv.mekc.info/takelogin.php',
                    'postfields'     => 'username='.$login.'&password='.$password.'&x=0&y=0',
                    'convert'        => array('windows-1251', 'utf-8//IGNORE'),
                )
            );

            if ( ! empty($page))
            {
                //проверяем подходят ли учётные данные
                if (preg_match('/<b>Ошибка входа<\/b>/', $page, $array))
                {
                    //устанавливаем варнинг
                    if (tvmekcinfo::$warning == NULL)
                    {
                        tvmekcinfo::$warning = TRUE;
                        Errors::setWarnings($tracker, 'credential_wrong');
                    }
                    //останавливаем процесс выполнения, т.к. не может работать без кук
                    tvmekcinfo::$exucution = FALSE;
                }
                else
                {
                    //если подходят - получаем куки
                    if (preg_match_all('/Set-Cookie: (.*);/iU', $page, $array))
                    {
                        tvmekcinfo::$sess_cookie = implode('; ', $array[1]);
                        Database::setCookie($tracker, tvmekcinfo::$sess_cookie);
                        //запускам процесс выполнения, т.к. не может работать без кук
                        tvmekcinfo::$exucution = TRUE;
                    }
                }
            }
            //если вообще ничего не найдено
            else
            {
                //устанавливаем варнинг
                if (tvmekcinfo::$warning == NULL)
                {
                    tvmekcinfo::$warning = TRUE;
                    Errors::setWarnings($tracker, 'not_available');
                }
                //останавливаем процесс выполнения, т.к. не может работать без кук
                tvmekcinfo::$exucution = FALSE;
            }
        }
        else
        {
            //устанавливаем варнинг
            if (tvmekcinfo::$warning == NULL)
            {
                tvmekcinfo::$warning = TRUE;
                Errors::setWarnings($tracker, 'credential_miss');
            }
            //останавливаем процесс выполнения, т.к. не может работать без кук
            tvmekcinfo::$exucution = FALSE;
        }
    }
    
    public static function main($torrentInfo)
    {
        extract($torrentInfo);
        
        $cookie = Database::getCookie($tracker);
        if (tvmekcinfo::checkCookie($cookie))
        {
            tvmekcinfo::$sess_cookie = $cookie;
            //запускам процесс выполнения
            tvmekcinfo::$exucution = TRUE;
        }            
        else
            tvmekcinfo::getCookie($tracker);

        if (tvmekcinfo::$exucution)
        {
            //получаем страницу для парсинга
            $page = Sys::getUrlContent(
                array(
                    'type'           => 'POST',
                    'header'         => 0,
                    'returntransfer' => 1,
                    'url'            => 'http://tv.mekc.info/details.php?id='.$torrent_id,
                    'cookie'         => tvmekcinfo::$sess_cookie,
                    'sendHeader'     => array('Host' => 'tv.mekc.info', 'Content-length' => strlen(tvmekcinfo::$sess_cookie)),
                    'convert'        => array('windows-1251', 'utf-8//IGNORE'),
                )
            );

            if ( ! empty($page))
            {
                //ищем на странице дату регистрации торрента
                if (preg_match('/<td style=\"background:#dde1e2;border:2px solid #f4f4f4;\" valign=\"top\" align=\"left\">(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})<\/td>/', $page, $array))
                {
                    //проверяем удалось ли получить дату со страницы
                    if (isset($array[1]))
                    {
                        //если дата не равна ничему
                        if ( ! empty($array[1]))
                        {
                            //находим имя торрента для скачивания        
                            if (preg_match('/(http:\/\/88\.198\.108\.196\/give_it_to_meh\.php\?id=\d{3,6}&passkey=.*)\">/', $page, $links))
                            {
                                //ссылка
                                $link = str_replace('give_it_to_meh', 'download', $links[1]);
                                //сбрасываем варнинг
                                Database::clearWarnings($tracker);
                                //приводим дату к общему виду
                                $date = $array[1];
                                $date_str = tvmekcinfo::dateNumToString($array[1]);
                                //если даты не совпадают, перекачиваем торрент
                                if ($date != $timestamp)
                                {
                                    //сохраняем торрент в файл
                                    $torrent = Sys::getUrlContent(
                                        array(
                                            'type'           => 'GET',
                                            'returntransfer' => 1,
                                            'url'            => $link,
                                        )
                                    );
                                    
                                    if ($auto_update)
                                    {
                                        $name = Sys::getHeader('http://tv.mekc.info/details.php?id='.$torrent_id);
                                        //обновляем заголовок торрента в базе
                                        Database::setNewName($id, $name);
                                    }
    
                                    $message = $name.' обновлён.';
                                    $status = Sys::saveTorrent($tracker, $torrent_id, $torrent, $id, $hash, $message, $date_str);
                                    
                                    //обновляем время регистрации торрента в базе
                                    Database::setNewDate($id, $date);
                                }
                            }
                            else
                            {
                                //устанавливаем варнинг
                                if (tvmekcinfo::$warning == NULL)
                                {
                                    tvmekcinfo::$warning = TRUE;
                                    Errors::setWarnings($tracker, 'not_available');
                                }
                                //останавливаем процесс выполнения, т.к. не может работать без кук
                                tvmekcinfo::$exucution = FALSE;
                            }
                        }
                        else
                        {
                            //устанавливаем варнинг
                            if (tvmekcinfo::$warning == NULL)
                            {
                                tvmekcinfo::$warning = TRUE;
                                Errors::setWarnings($tracker, 'not_available');
                            }
                            //останавливаем процесс выполнения, т.к. не может работать без кук
                            tvmekcinfo::$exucution = FALSE;
                        }
                    }
                    else
                    {
                        //устанавливаем варнинг
                        if (tvmekcinfo::$warning == NULL)
                        {
                            tvmekcinfo::$warning = TRUE;
                            Errors::setWarnings($tracker, 'not_available');
                        }
                        //останавливаем процесс выполнения, т.к. не может работать без кук
                        tvmekcinfo::$exucution = FALSE;
                    }
                }
                else
                {
                    //устанавливаем варнинг
                    if (tvmekcinfo::$warning == NULL)
                    {
                        tvmekcinfo::$warning = TRUE;
                        Errors::setWarnings($tracker, 'not_available');
                    }
                    //останавливаем процесс выполнения, т.к. не может работать без кук
                    tvmekcinfo::$exucution = FALSE;
                }
            }
            else
            {
                //устанавливаем варнинг
                if (tvmekcinfo::$warning == NULL)
                {
                    tvmekcinfo::$warning = TRUE;
                    Errors::setWarnings($tracker, 'not_available');
                }
                //останавливаем процесс выполнения, т.к. не может работать без кук
                tvmekcinfo::$exucution = FALSE;
            }
        }
    }
    
    // функция генерирует url ссылку на раздачу
    public static function generateURL($tracker, $torrent_id) {
        return 'http://'.$tracker.'/details.php?id='.$torrent_id;
    }
}
?>