<?php

$dr = dirname(__FILE__);
include_once $dr.'/Plugin.class.php';
include_once $dr.'/Database.class.php';


abstract class TorrentClient extends Plugin
{
    public static $type='TorrentClient';

    // Ф-ция реализующая проверку корректности настроек
    protected abstract function localCheckSettings();

    // Ф-ция удаления раздачи из клиента
    protected abstract function localDelete($hash, $withData);

    // Ф-ция реализующая непосредственно добавление торрента
    protected abstract function localAdd($params_array);

    // Ф-ция заполняет пустые параметры значениями по умолчанию
    protected abstract function localSetDefaultSettings($settings);

    final public function Name()
    {
        return get_called_class();
    }

    public function VerboseName()
    {
        return $this->Name();
    }

    final public function Type()
    {
        return TorrentClient::$type;
    }

    final public function ClientAddress()
    {
        return $this->GetProperty('clientAddress');
    }

    final public function ClientUser()
    {
        return $this->GetProperty('clientUser');
    }

    final public function ClientPwd()
    {
        return $this->GetProperty('clientPwd');
    }

    final public function PathToDownload()
    {
        return $this->GetProperty('pathToDownload');
    }

    final public function DeleteDistribution()
    {
        return $this->GetProperty('deleteDistribution');
    }

    final public function DeleteOldFiles()
    {
        return $this->GetProperty('deleteOldFiles');
    }

    final public function SetParams($settings)
    {
        foreach($settings as $key => $val) {
            $this->SetProperty($key, $val);
        }
    }

    protected function GetDownloadPath($id)
    {
        $pathToDownload = $this->PathToDownload();
        $individualPath = Database::getTorrentDownloadPath($id);
        if ( ! empty($individualPath))
            $pathToDownload = $individualPath;
       return $pathToDownload;
    }

    final public function RemoveTorrentOnUpdate($hash, $tracker)
    {
        if ( ! empty($hash))
        {
            $del_params = $this->NeedRemoveTorrent($tracker);
            if ( $del_params['delete'] == TRUE )
                $this->localDelete($hash, $del_params['with_data']);
        }
    }

    protected function NeedRemoveTorrent($tracker)
    {
        # по дефолту - удаляем только раздачу.
        # А для определённого перечня трекеров - отдельно обрабатываем настройки удаления
        $result['delete'] = TRUE;
        $result['with_data'] = FALSE;

        if ($tracker == 'lostfilm.tv' || $tracker == 'novafilm.tv' ||  $tracker == 'baibako.tv' || $tracker == 'newstudio.tv')
        {
            $result['delete'] = $this->DeleteDistribution();
            $result['with_data'] = $this->DeleteOldFiles();
        }
        return $result;
    }

    public static function Create($clientName = '')
    {
        if ($clientName == '')
        {
            foreach (Database::getActivePluginsByType(TorrentClient::$type) as $plugin)
            {
                $clientName = $plugin['name'];
                break;
            }
        }

        if ( empty($clientName) )
            return null;

        include_once dirname(__FILE__)."/TorrentClients/".$clientName.".class.php";

        if  (!class_exists($clientName))
            return null;

        return new $clientName();
    }

    public static function CheckSettings()
    {
        $client = TorrentClient::Create();
        if ($client == null)
            return array('text' => 'Class for torrent client not found!', 'error' => true);

        $result = $client->localCheckSettings();
        $client = null;
        return $result;
    }

    public static function Delete($hash, $delOpt='')
    {
       $client = TorrentClient::Create();
        if ($client == null)
        {
            $result['status'] = FALSE;
            $result['msg'] = 'client_not_configured';
            return $result;
        }

        $client->localDelete($hash, $delOpt);
        $client = null;
        $result['status'] = TRUE;
        return $result;
    }

    public static function Add($params_array)
    {
        $client = TorrentClient::Create();
        if ($client == null)
        {
            $result['status'] = FALSE;
            $result['msg'] = 'client_not_configured';
            return $result;
        }

        $hash = $params_array['hash'];
        $tracker = $params_array['tracker'];
        $id = $params_array['id'];
        $client->RemoveTorrentOnUpdate($hash, $tracker);
        $result = $client->localAdd($params_array);

        if ($result['status'] == TRUE)
        {
            #обновляем hash в базе
            Database::updateHash($id, $result['hash']);
            //сбрасываем варнинг
            Database::clearWarnings(TorrentClient::$type);
        }

        $client = null;
        return $result;
    }

    public function SettingsHtml()
    {
        $fields     = self::GetSettingsFields();
        $usedFields = $this->UsedSettingsFields();
        $settings   = $this->GetSettings();
        $clientName = $this->Name();
        
        $clientFields = array();
        foreach ($usedFields as $fieldName)
            if ( isset($fields[$fieldName]) ) {
                $clientFields[$fieldName] = $fields[$fieldName];
                $clientFields[$fieldName]['val'] = isset($settings[$fieldName]) ? $settings[$fieldName] : '';
                $clientFields[$fieldName]['class'] = $clientName.'_setting';
            }
 
        return Sys::fieldsToHtml($clientFields);
    }

    // Ф-ция формирует перечень полей настроек клиента
    protected static function GetSettingsFields()
    {
        $fields = array();

        //Адрес клиента
        $fields[clientAddress] = array('type'    => 'input-text',
                                       'verbName' => 'Адрес, порт торрент-клиента',
                                       'desc'     => 'Например: 127.0.0.1:58846',
                                 );

        //Логин
        $fields[clientUser] = array('type'     => 'input-text',
                                    'verbName' => 'Логин',
                                    'desc'     => 'Например: KorP',
                              );

        //Пароль
        $fields[clientPwd] = array('type'     => 'input-pwd',
                                   'verbName' => 'Пароль',
                                   'desc'     => 'Например: Pa$$w0rd',
                             );

        //Каталог для скачивания
        $fields[pathToDownload] = array('type'     => 'input-text',
                                        'verbName' => 'Директория для скачивания',
                                        'desc'     => 'Например: /var/lib/transmission/downloads',
                                  );

        //Удалять раздачи из torrent-клиента
        $fields[deleteDistribution] = array('type'     => 'input-checkbox',
                                            'verbName' => 'Удалять раздачи из torrent-клиента',
                                      );

        //Удалять файлы старых раздач
        $fields[deleteOldFiles] = array('type'     => 'input-checkbox',
                                        'verbName' => 'Удалять файлы старых раздач',
                                        'desc'     => 'Только для lostfilm.tv, novafilm.tv, baibako.tv и newstudio.tv',
                                  );

        return $fields;
    }

    // Ф-ция возвращает перечень полей, которые используются для клиента
    protected function UsedSettingsFields()
    {
        $settings   = $this->GetSettings();
        $usedFields = array();
        
        //По умолчанию выводим все поля, для которых существуют настройки
        foreach($settings as $key => $val)
            $usedFields[] = $key;
        
        return $usedFields;
    }

    // Ф-ция возвращает параметры клиента
    protected function GetSettings()
    {
        $settings = array('clientAddress'      => $this->ClientAddress(),
                          'clientUser'         => $this->ClientUser(),
                          'clientPwd'          => $this->ClientPwd(),
                          'pathToDownload'     => $this->PathToDownload(),
                          'deleteDistribution' => $this->DeleteDistribution(),
                          'deleteOldFiles'     => $this->DeleteOldFiles(),
                    );
        return $this->localSetDefaultSettings($settings);
    }
}
?>
