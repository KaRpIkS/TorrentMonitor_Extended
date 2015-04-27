<?php

include_once dirname(__FILE__).'/../class/Notifier.class.php';
include_once dirname(__FILE__).'/../class/Errors.class.php';

class ProwlNotifier extends Notifier
{
    public function VerboseName()
    {
        return "Prowl";
    }

    public function Description()
    {
        return "Сервис уведомлений <a href='http://www.prowlapp.com/'>Prowl</a>";
    }

    protected function localSend($type, $date, $tracker, $message, $header_message, $name=0)
    {
        if ($type == 'warning')
            $priority = 2;
        else
            $priority = 0;
        
        $msg = $this->messageText($tracker, $date, $message);
        $postfields = 'apikey='.$this->SendAddress().'&application=TorrentMonitor&priority'.$priority.'&event=Notification&description='.$msg;
        $response = Sys::getUrlContent(
            array(
                'type'           => 'POST',
                'header'         => 1,
                'returntransfer' => 1,
                'url'            => 'https://api.prowlapp.com/publicapi/add',
                'postfields'     => $postfields,
            )
        );    	
        return array('success' => preg_match('/success code=\"200\"/', $response), 'response' => $response);

    }
}


?>
