<?php
class Update {
    public static $root_dir;
    public static $install_dir;
    public static $latest_version;

    private static $version;
    private static $db_version;
    private static $dbType;

    private static function deleteDirectory($dir) {
        $result = false;
        if ( $handle = opendir($dir) ) {
            $result = true;
            while ( (($file=readdir($handle))!==false) && ($result) ) {
                if ($file!='.' && $file!='..') {
                    $path = $dir.'/'.$file;
                    if ( is_dir($path) ) {
                        $result = self::deleteDirectory($path);
                    } else {
                        $result = unlink($path);
                    }
                }
            }
            closedir($handle);
            if ($result){
                $result = rmdir($dir);
            }
        }
        return $result;
    }

    private static function NeedUpdate($version)
    {
        return ( version_compare(self::$version, $version, '<') || version_compare(self::$db_version, $version, '<') );
    }

    private static function InstallUpdate($updateParams, $version) {
        $deleteFolders  = isset($updateParams['deleteFolders']) ? $updateParams['deleteFolders'] : array();
        $createFolders  = isset($updateParams['createFolders']) ? $updateParams['createFolders'] : array();
        $deleteFiles    = isset($updateParams['deleteFiles']) ? $updateParams['deleteFiles'] : array();
        $copyFiles      = isset($updateParams['copyFiles']) ? $updateParams['copyFiles'] : array();
        $queryes        = isset($updateParams['queryes']) ? $updateParams['queryes'] : array();
        $queryes_common = isset($updateParams['queryes_common']) ? $updateParams['queryes_common'] : array();


        //Требуется обновление модулей
        if ( version_compare(self::$version, $version, '<') ) {
            //Удаляем каталоги
            foreach($deleteFolders as $folder) {
                if ( file_exists(self::$root_dir.$folder) ) {
                    if ( self::deleteDirectory(self::$root_dir.$folder) )
                        echo 'Каталог: <b>'.$folder.'</b> удален.<br>';
                    else {
                        echo 'Не удалось удалить каталог: <b>'.$folder.'</b><br>';
                        return TRUE;
                    }
                }
            }

            //Удаляем файлы
            foreach($deleteFiles as $file) {
                if ( file_exists(self::$root_dir.$folder) ) {
                    if ( unlink(self::$root_dir.$file) )
                        echo 'Файл: <b>'.$file.'</b> удален.<br>';
                    else {
                        echo 'Не удалось удалить файл: <b>'.$file.'</b><br>';
                        return TRUE;
                    }
                }
            }

            //Создаем новые каталоги
            foreach($createFolders as $folder) {
                if ( !file_exists(self::$root_dir.$folder) ) {
                    if ( mkdir(self::$root_dir.$folder, 0777, true) )
                        echo 'Каталог: <b>'.$folder.'</b> успешно создан.<br>';
                    else{
                        echo 'Не удалось создать директорию: <b>'.$folder.'</b><br>';
                        return TRUE;
                    }
                }
            }

            //Копируем файлы
            foreach($copyFiles as $file) {
                if ( copy(self::$install_dir.$file, self::$root_dir.$file) )
                    echo 'Файл: <b>'.$file.'</b> обновлён.<br>';
                else {
                    echo 'Не удалось скопировать файл: <b>'.$file.'</b><br>';
                    return TRUE;
                }
            }
        }

        // Требуется обновление БД
        if ( version_compare(self::$db_version, $version, '<') ) {
            //Выполняем запросы к базе данных
            if ( count($queryes) ) {
                foreach($queryes as $query) {
                    Database::updateQuery($query);
                }
                echo 'Выполнено '.count($queryes).' запросов на обновление.<br>';
            }
        }

    }

    public static function Start()
    {
        self::$version    = Sys::version();
        self::$db_version = Sys::dbVersion();
        self::$dbType     = Config::read('db.type');

        /* Шаблон обновления
        $updVersion = '1.2.5.2';
        if ( self::NeedUpdate($updVersion) ) {
            echo '<br>Обновление до версии '.$updVersion.'<br>';

            //Подготовка параметров для обновления модулей и БД
            $deleteFolders = array(); //Перечень удаляемыйх каталогов
            $createFolders = array(); //Перечень создаваемых каталогов
            $deleteFiles   = array(); //Перечень удаляемыйх файлов
            $copyFiles     = array(); //Перечень обновляемых файлов
            $queryes       = array(); //Перечень выполняемых запросов

            $updateParams = array('deleteFolders' => $deleteFolders,
                                  'createFolders' => $createFolders,
                                  'deleteFiles'   => $deleteFiles,
                                  'copyFiles'     => $copyFiles,
                                  'queryes'       => $queryes,
                            );

            //Выполнение обновления модулей и БД
            if ( self::InstallUpdate($updateParams, $updVersion) ) {
                echo 'Обновление завершено с ошибками';
                exit;
            }

            //Выполнение произвольного алгоритма

            //Обновление данных о версии
            self::$version = $updVersion;
            self::$db_version = $updVersion;
            Database::updateSettings('dbVer', self::$db_version);
        }
        */

        $updVersion = '1.2.6.1';
        if ( self::NeedUpdate($updVersion) ) {
            echo '<br>Обновление до версии '.$updVersion.'<br>';

            //Подготовка параметров для обновления модулей и БД
            $deleteFolders = array('notifiers');
            $createFolders = array('class/Lib',
                                   'class/Notifiers',
                                   'class/TorrentClients',
                             );
            $deleteFiles   = array('class/Deluge.class.php',
                                   'class/Transmission.class.php',
                                   'class/TransmissionRPC.class.php',
                                   'class/rain.tpl.class.php',
                             );
            $copyFiles     = array('action.php',
                                   'index.php',
                                   'class/Database.class.php',
                                   'class/Errors.class.php',
                                   'class/System.class.php',
                                   'class/Update.class.php',
                                   'class/Notifier.class.php',
                                   'class/Notifiers/Email.Notifier.class.php',
                                   'class/Notifiers/Prowl.Notifier.class.php',
                                   'class/Notifiers/Pushbullet.Notifier.class.php',
                                   'class/Notifiers/Pushover.Notifier.class.php',
                                   'class/Lib/TransmissionRPC.class.php',
                                   'class/Lib/rain.tpl.class.php',
                                   'class/TorrentClient.class.php',
                                   'class/TorrentClients/Deluge.class.php',
                                   'class/TorrentClients/Transmission.class.php',
                                   'include/add.php',
                                   'include/check.php',
                                   'include/credentials.php',
                                   'include/form.php',
                                   'include/help.php',
                                   'include/news.php',
                                   'include/settings.php',
                                   'include/show_table.php',
                                   'include/show_warnings.php',
                                   'include/show_watching.php',
                                   'include/update.php',
                                   'js/user-func.js',
                                   'templates/default/settings.html'
                             );
            $queryes       = array("DELETE FROM settings WHERE `key` = 'send';",
                                   "DELETE FROM settings WHERE `key` = 'sendWarning';",
                                   "DELETE FROM settings WHERE `key` = 'torrentAddress';",
                                   "DELETE FROM settings WHERE `key` = 'torrentLogin';",
                                   "DELETE FROM settings WHERE `key` = 'torrentPassword';",
                                   "DELETE FROM settings WHERE `key` = 'pathToDownload';",
                                   "DELETE FROM settings WHERE `key` = 'deleteOldFiles';",
                                   "DELETE FROM settings WHERE `key` = 'deleteDistribution';",
                                   "DELETE FROM settings WHERE `key` = 'sendUpdate';",
                                   "DELETE FROM settings WHERE `key` = 'sendUpdateService';",
                                   "DELETE FROM settings WHERE `key` = 'sendWarningService';",
                            );

            //Обновление модулей
            $updateParams = array('deleteFolders' => $deleteFolders,
                                  'createFolders' => $createFolders,
                                  'deleteFiles'   => $deleteFiles,
                                  'copyFiles'     => $copyFiles,
                            );

            //Выполняем обновление модулей
            if ( self::InstallUpdate($updateParams, $updVersion) ) {
                echo 'Обновление завершено с ошибками<br>';
                exit;
            }

            //Перенос настроек торрент-клиентов
            if ( Database::getSetting('useTorrent') ) {
                echo 'Переносятся настройки торрент-клиента<br>';

                include_once self::$root_dir.'class/TorrentClient.class.php';

                $clientClass = Database::getSetting('torrentClient');
                $client = TorrentClient::Create($clientClass);
                if ($client != NULL) {
                    $client->SetParams(Database::getSetting('torrentAddress'),
                                       Database::getSetting('torrentLogin'),
                                       Database::getSetting('torrentPassword'),
                                       Database::getSetting('pathToDownload'),
                                       Database::getSetting('deleteDistribution'),
                                       Database::getSetting('deleteOldFiles')
                                    );
                    $client = NULL;
                }
            }

            //Выполняем запросы на удаление устаревших параметров
            $updateParams = array('queryes' => $queryes);
            self::InstallUpdate($updateParams, $updVersion);

            //Обновление данных о версии
            self::$version = $updVersion;
            self::$db_version = $updVersion;
            Database::updateSettings('dbVer', self::$db_version);
        }

        echo 'Обновление завершено успешно';

        //Удаляем каталог с временными файлами
        if ( file_exists(self::$root_dir.'tmp') )
            self::deleteDirectory(self::$root_dir.'tmp');
    }
}
?>
