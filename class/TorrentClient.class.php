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

    final public function SetParams($address, $user, $pwd, $path, $delDistr, $delOld)
    {
        $this->SetProperty('clientAddress', $address);
        $this->SetProperty('clientUser', $user);
        $this->SetProperty('clientPwd', $pwd);
        $this->SetProperty('pathToDownload', $path);
        $this->SetProperty('deleteDistribution', $delDistr);
        $this->SetProperty('deleteOldFiles', $delOld);
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
}
?>
