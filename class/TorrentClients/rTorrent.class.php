<?php
include_once dirname(__FILE__).'/../TorrentClient.class.php';
include_once dirname(__FILE__).'/../Database.class.php';
include_once dirname(__FILE__).'/../Lib/rTorrentRPC.class.php';
include_once dirname(__FILE__).'/../Lib/BEncode.class.php';

class rTorrent extends TorrentClient
{
    public function Description()
    {
        return 'http://rakshasa.github.io/rtorrent/';
    }

    protected function localDelete($hash, $withData)
    {
        if ($withData == TRUE)
        {
            // ??? удаление файлов раздачи
        }

        // Отправляем команду на удаление раздачи из торрент клиента
        $req = new rXMLRPCRequest($this->ClientAddress(), new rXMLRPCCommand('d.erase', $hash));
        $req->run();
    }

    protected function localAdd($params_array)
    {

        $id = $params_array['id'];
        $file = $params_array['file'];

        // Достаем хеш из торрент файла
        $bcoder = new Bhutanio\BEncode;
        $torrent = $bcoder->bdecode(@file_get_contents($file));
        $hash = sha1($bcoder->bencode($torrent['info']));

        $pathToDownload = $this->GetDownloadPath($id);

        // Отправляем команду на добавление новой раздачи и старт загрузки
        $req = new rXMLRPCRequest($this->ClientAddress());
        $cmd = new rXMLRPCCommand('load_start');
        $cmd->addParameter($file);
        // Если данная директория для загрузки не существует или не директория - создать ее
        if (!is_dir($pathToDownload))
            $req->addCommand(new rXMLRPCCommand('execute', array('mkdir','-p',$pathToDownload)));
        $cmd->addParameter('d.set_directory='."\"".$pathToDownload."\"");
        $req->addCommand($cmd);

        if ($req->success())
        {
            // Надо подождать секунду, пока раздача появится в списке
            sleep(1);
            // Проверим, добавилась раздача или нет
            $req = new rXMLRPCRequest($this->ClientAddress(), new rXMLRPCCommand('d.get_state', $hash));
            if ($req->success())
            {
                $return['status'] = TRUE;
                $return['hash'] = $hash;
            }
            else
            {
                $return['status'] = FALSE;
                $return['msg'] = 'add_fail';
            }
        }
        else
        {
            $return['status'] = FALSE;
            $return['msg'] = 'add_fail';
        }

        return $return;
    }

    protected function localCheckSettings()
    {
        // Запросим версию rTorrent
        $req =  new rXMLRPCRequest($this->ClientAddress(), new rXMLRPCCommand('system.client_version'));
        if ($req->success())
        {
            return array('text' => 'OK. Версия rTorrent: '.$req->val[0], 'error' => false);
        }
        elseif ($req->fault)
        {
            return array('text' => 'С подключением к rTorrent все в порядке, но при выполнении команды произошла ошибка!<br>#'.$req->val[0].': '.$req->val[1], 'error' => true);
        }
        else
        {
            return array('text' => 'Не удалось подключиться к rTorrent по адресу '.$this->ClientAddress().'<br>Проверьте правильность настроек IP:ПОРТ торрент-клиента', 'error' => true);
        }
    }

}
