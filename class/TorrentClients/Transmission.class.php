<?php
include_once dirname(__FILE__).'/../TorrentClient.class.php';
include_once dirname(__FILE__).'/../Database.class.php';
include_once dirname(__FILE__).'/../Lib/TransmissionRPC.class.php';

class Transmission extends TorrentClient
{
    public function Description()
    {
        return "http://www.transmissionbt.com/";
    }

    protected function localDelete($hash, $withData)
    {
        if ( $withData == TRUE)
            $delOpt = 'true';
        else
            $delOpt = 'false';

    	$rpc = new TransmissionRPC('http://'.$this->ClientAddress().'/transmission/rpc', $this->ClientUser(), $this->ClientPwd());
        $rpc->remove($hash, $delOpt);
        $rpc = null;
    }

    protected function localAdd($params_array)
    {
        $id = $params_array['id'];
        $file = $params_array['file'];
        try
        {
    	    $rpc = new TransmissionRPC('http://'.$this->ClientAddress().'/transmission/rpc', $this->ClientUser(), $this->ClientPwd());
            #$rpc->debug=true;
            $result = $rpc->sstats();

            $pathToDownload = $this->GetDownloadPath($id);
            $result = $rpc->add($file, $pathToDownload);
            $command = $result->result;
            $idt = @$result->arguments->torrent_added->id;

            if (preg_match('/Couldn\'t connect to server/', $command))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'add_fail';
            }
            elseif (preg_match('/No Response/', $command))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'no_response';
            }
            elseif (preg_match('/invalid or corrupt torrent file/', $command))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'torrent_file_fail';
            }
            elseif (preg_match('/duplicate torrent/', $command))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'duplicate_torrent';
            }
            elseif (preg_match('/gotMetadataFromURL: http error 404: Not Found/', $command))
            {
                $return['status'] = FALSE;
                $return['msg'] = '404';
            }
            elseif (preg_match('/gotMetadataFromURL: http error 401: Unauthorized/', $command))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'unauthorized';
            }
            elseif (preg_match('/username/', $command))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'log_passwd';
            }
            elseif (preg_match('/success/', $command))
            {
                #получаем хэш раздачи
                $result = $rpc->get($idt, array('hashString'));
                $hashNew = $result->arguments->torrents[0]->hashString;
                $return['status'] = TRUE;
                $return['hash'] = $hashNew;
            }
            else
            {
                $return['status'] = FALSE;
                $return['msg'] = 'unknown';
            }
        }
        catch (Exception $e)
        {
            if (preg_match('/Invalid username\/password./', $e->getMessage()))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'log_passwd';
            }
            if (preg_match('/Forbidden/', $e->getMessage()))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'log_passwd';
            }
            elseif (preg_match('/Unable to connect/', $e->getMessage()))
            {
                $return['status'] = FALSE;
                $return['msg'] = 'connect_fail';
            }
            else
                die('[ERROR]'.$e->getMessage().PHP_EOL);
        }
        return $return;
    }

    protected function localCheckSettings()
    {
     	try
	    {
    	    $rpc = new TransmissionRPC('http://'.$this->ClientAddress().'/transmission/rpc', $this->ClientUser(), $this->ClientPwd());
    	    $result = $rpc->sstats()->result;
        }
        catch (Exception $e)
        {
            return array('text' => '<p>'.$e->getMessage().'</p>', 'error' => true);
        }

        if ( $result != "success" )
            return array('text' => '<p>'.$result.'</p>', 'error' => true);
        else
            return array('text' => 'OK', 'error' => false);
    }

}
?>
