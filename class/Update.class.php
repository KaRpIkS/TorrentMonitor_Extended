<?php
$dir = str_replace('class', '', dirname(__FILE__));

include_once $dir."config.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";

class Update {
    private static $xml_page;
    
    private static function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file)
            (is_dir($dir.'/'.$file)) ? Update::delTree($dir.'/'.$file) : unlink($dir.'/'.$file);
        return rmdir($dir); 
    }
    
    public static function runUpdate()
    {
        $xml_page = Update::xml_page();
        $version = Sys::version();
        $dbVer   = Sys::dbVersion();
        $ROOTPATH = str_replace('class', '', dirname(__FILE__));
        $dbType = Config::read('db.type');
        
        $count = count($xml_page->update) - 1;
        
        //Если версия базы данных не актуальна, то выполняем обновление
        if ( version_compare($dbVer, $version, '<') ) {
            for ($i=$count; $i>=0; $i--) {
                $updVersion = (string) $xml_page->update[$i]->version;
                if ( version_compare($dbVer, $updVersion, '<') ) {
                    $queryes = $xml_page->update[$i]->$dbType;
                    $queryes_common = $xml_page->update[$i]->queryes;
                    echo '<b>Обновление базы данных c'.$dbVer.' по '.$updVersion.':</b></br>';
                    if (isset($queryes->query) && ! empty($queryes->query)) {
                        $x=0;
                        foreach($queryes->query as $query) {
                            Database::updateQuery($query);
                            $x++;
                        }
                        echo 'Выполнено '.$x.' запросов на обновление.<br>';
                    }
                    
                    if( isset($queryes_common->query) && ! empty($queryes_common->query) ) {
                         $y=0;
                        foreach($queryes_common->query as $query) {
                            Database::updateQuery($query);
                            $y++;
                        }
                        echo 'Выполнено '.$y.' запросов на обновление.<br>';
                    }
                    
                    echo 'Обновление базы данных завершено</br></br>';
                    Database::updateSettings('dbVer', $updVersion);
                    $dbVer = $updVersion;
                }
            }
        }

        for ($i=$count; $i>=0; $i--)
        {
            $updVersion = (string) $xml_page->update[$i]->version;
            $description = $xml_page->update[$i]->description;
            $files = $xml_page->update[$i]->files;
            $queryes = $xml_page->update[$i]->$dbType;
            $queryes_common = $xml_page->update[$i]->queryes;
            $deleteFolders = $xml_page->update[$i]->deleteFolders;
            $createFolders = $xml_page->update[$i]->createFolders;
            
            if ( version_compare($version, $updVersion, '<') )
            {
                echo '<b>Установка обновления '.$updVersion.':</b></br>';
                $file = file_get_contents('http://vlmaksime.github.io/tme/stable.zip');
                if ( ! empty($file))
                {
                    if (file_put_contents($ROOTPATH.'stable.zip', $file))
                    {
                        $zip = new ZipArchive;
                        if ($zip->open($ROOTPATH.'stable.zip') === TRUE)
                        {
                            
                            if (isset($deleteFolders->folder) && ! empty($deleteFolders->folder))
                            {
                                foreach($deleteFolders->folder as $folder)
                                {
                                    Update::delTree($ROOTPATH.$folder);
                                }
                            }
                            if (isset($createFolders->create) && ! empty($createFolders->create))
                            {
                                foreach($createFolders->create as $folder)
                                {
                                    if ( ! mkdir($structure, 0777, true))
                                    {
                                        echo 'Не удалось создать директорию: '.$file.", обновление прервано.<br>";
                                        break;
                                    }
                                }
                            }
                            
                            $zip->extractTo($ROOTPATH.'tmp');
                            $zip->close();
                            unlink($ROOTPATH.'stable.zip');
                            
                            if (isset($files->file) && ! empty($files->file))
                            {
                                foreach($files->file as $file)
                                {
                                    if ( ! copy($ROOTPATH.'tmp/'.$file, $ROOTPATH.$file))
                                    {
                                        echo 'Не удалось скопировать файл: '.$file.", обновление прервано.<br>";
                                        break;
                                    }
                                    else
                                        echo 'Файл: '.$file.' обновлён.<br>';
                                }
                            }
                            
                            Update::delTree($ROOTPATH.'tmp');
                            
                            if (isset($queryes->query) && ! empty($queryes->query))
                            {
                                $x=0;
                                foreach($queryes->query as $query)
                                {
                                    Database::updateQuery($query);
                                    $x++;
                                }
                                echo 'Выполнено '.$x.' запросов на обновление.<br>';
                            }
                            
                            if( isset($queryes_common->query) && ! empty($queryes_common->query) )
                            {
                                 $y=0;
                                foreach($queryes_common->query as $query)
                                {
                                    Database::updateQuery($query);
                                    $y++;
                                }
                                echo 'Выполнено '.$y.' запросов на обновление.<br>';
                            }
                            
                            Database::updateSettings('dbVer', $updVersion);
                        }
                        else
                            echo 'Не могу разархивировать stable.zip<br>';
                    }
                    else
                        echo 'Не могу сохранить stable.zip<br>';
                }
                else
                    echo 'Не удалось скачать stable.zip<br>';
            }
        }
    }
    
    private static function xml_page()
    {
        if ( empty(Update::$xml_page) ) {
            $opts = stream_context_create(array(
                'http' => array(
                    'timeout' => 1
                    )
                ));
    
            $xmlstr = @file_get_contents('http://vlmaksime.github.io/tme/update.xml', false, $opts);
            Update::$xml_page = @simplexml_load_string($xmlstr);
        }
        return Update::$xml_page;
    }
    
    public static function getUpdateInfo()
    {
        $changelog = array();
        
        $version = Sys::version();
        $dbVer   = Sys::dbVersion();

        $xml_page = Update::xml_page();
        $count = count($xml_page->update) - 1;
        
        for ($i=$count; $i>=0; $i--)
        {
            $updVersion = (string) $xml_page->update[$i]->version;
            if ( version_compare($version, $updVersion, '<') )
            {
                $desc = $xml_page->update[$i]->description;
                $changelog[] = array('ver' => $updVersion,
                                     'desc' => $desc);
            }
        }
        
        if ( count($сhangelog) == 0 && version_compare($dbVer, $version, '<') ) {
            $changelog[] = array('ver' => $version,
                                 'desc' => 'Обновление базы данных');
        }
        
        return $changelog;
    }    
}
?>
