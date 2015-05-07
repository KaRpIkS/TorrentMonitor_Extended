<?php
include_once dirname(__FILE__).'/../TorrentClient.class.php';
include_once dirname(__FILE__).'/../Database.class.php';

class Deluge extends TorrentClient
{
    public function Description()
    {
        return "http://deluge-torrent.org/";
    }

    protected function localDelete($hash, $withData)
    {
        if ( $withData == TRUE )
            $delOpt = '--remove_data';
        else
            $delOpt = '';

        $torrentAddress = $this->ClientAddress();
        $torrentLogin = $this->ClientUser();
        $torrentPassword = $this->ClientPwd();
        $command = `deluge-console 'connect $torrentAddress $torrentLogin $torrentPassword; rm $hash $delOpt'`;
    }

    protected function localAdd($params_array)
    {
        $id = $params_array['id'];
        $file = $params_array['file'];

        $torrentAddress = $this->ClientAddress();
        $torrentLogin = $this->ClientUser();
        $torrentPassword = $this->ClientPwd();
        $pathToDownload = $this->GetDownloadPath($id);

        $command = `deluge-console 'connect $torrentAddress $torrentLogin $torrentPassword; add -p "$pathToDownload" $file'`;
        if ( ! preg_match('/Torrent added!/', $command) )
        {
            $result['status'] = FALSE;
            $result['msg'] = 'add_fail';
        }
        else
        {
            #получаем хэш раздачи
            $hashNew = `deluge-console 'connect $torrentAddress $torrentLogin $torrentPassword; info --sort-reverse=active_time' | grep ID: | awk '{print $2}' | tail -n -1`;
            $result['status'] = TRUE;
            $result['hash'] = $hashNew;
        }
        return $result;
    }

    protected function localCheckSettings()
    {
        $address = $this->ClientAddress();
        $user = $this->ClientUser();
        $pwd = $this->ClientPwd();
        $result = `deluge-console 'connect $address $user $pwd; '`;
        if ( ! empty($result) )
            return array('text' => '<p>'.$result.'</p>', 'error' => true);
        else
            return array('text' => 'OK', 'error' => false);
    }
}
?>
