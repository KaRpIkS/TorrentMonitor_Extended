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

    private static function InstallUpdate($updateParams) {
        $deleteFolders  = isset($updateParams['deleteFolders']) ? $updateParams['deleteFolders'] : array();
        $createFolders  = isset($updateParams['createFolders']) ? $updateParams['createFolders'] : array();
        $deleteFiles    = isset($updateParams['deleteFiles']) ? $updateParams['deleteFiles'] : array();
        $copyFiles      = isset($updateParams['copyFiles']) ? $updateParams['copyFiles'] : array();
        $queryes        = isset($updateParams['queryes']) ? $updateParams['queryes'] : array();
        $queryes_common = isset($updateParams['queryes_common']) ? $updateParams['queryes_common'] : array();

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
            if ( unlink(self::$root_dir.$file) )
                echo 'Файл: <b>'.$file.'</b> удален.<br>';
            else {
                echo 'Не удалось удалить файл: <b>'.$file.'</b><br>';
                return TRUE;
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
        
        //Выполняем запросы к базе данных
        if ( count($queryes) ) {
            foreach($queryes->query as $query) {
                Database::updateQuery($query);
            }
            echo 'Выполнено '.count($queryes_common).' запросов на обновление.<br>';                            
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

            $updateParams = array('deleteFolders'  => $deleteFolders,
                                  'createFolders'  => $createFolders,
                                  'deleteFiles'    => $deleteFiles,
                                  'copyFiles'      => $copyFiles,
                                  'queryes'        => $queryes,
                            );

            //Выполнение обновления модулей и БД
            if ( self::InstallUpdate($updateParams) ) {
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

        echo 'Обновление завершено успешно';

        //Удаляем каталог с временными файлами
        if ( file_exists(self::$root_dir.'tmp') )
            self::deleteDirectory(self::$root_dir.'tmp');
    }
}
?>
