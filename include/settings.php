<?php
$dir = str_replace('include', '', dirname(__FILE__));
include_once $dir."class/System.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));

include_once $dir."class/Database.class.php";
include_once $dir."class/Lib/rain.tpl.class.php";
include_once $dir."class/Notifier.class.php";
include_once $dir."class/TorrentClient.class.php";

// заполнение шаблона
raintpl::configure("root_dir", $dir );
raintpl::configure("tpl_dir" , Sys::getTemplateDir() );

$tpl = new RainTPL;

// Заполняем шаблон значениями настроек
$settings = Database::getAllSetting();
foreach ($settings as $row)
    foreach ($row as $key=>$val)
        $tpl->assign( $key, $val );


// Заполняем шаблон настройками торрент-клиента
$tpl->assign( 'torrentClients', Sys::getTorrentClients() );
$client = TorrentClient::Create();
if ( $client != null)
{
    $torrentClient = $client->Name();
    $torrentClientAddress = $client->ClientAddress();
    $torrentClientUser = $client->ClientUser();
    $torrentClientPwd = $client->ClientPwd();
    $pathToDownload = $client->PathToDownload();
    $deleteDistribution = $client->DeleteDistribution();
    $deleteOldFiles = $client->DeleteOldFiles();
}
else
{
    $torrentClient = '';
    $torrentClientAddress = '';
    $torrentClientUser = '';
    $torrentClientPwd = '';
    $pathToDownload = '';
    $deleteDistribution = '';
    $deleteOldFiles = '';
}
$tpl->assign( 'torrentClient', $torrentClient );
$tpl->assign( 'torrentClientAddress', $torrentClientAddress );
$tpl->assign( 'torrentClientUser', $torrentClientUser );
$tpl->assign( 'torrentClientPwd', $torrentClientPwd );
$tpl->assign( 'pathToDownload', $pathToDownload );
$tpl->assign( 'deleteDistribution', $deleteDistribution );
$tpl->assign( 'deleteOldFiles', $deleteOldFiles );



// заполняем раздел с нотификаторами
$notifiersList = array();
foreach (Database::getActivePluginsByType(Notifier::$type) as $plugin)
{
    $notifier = Notifier::Create($plugin['name'], $plugin['group']);
    if ($notifier == null)
        continue;

    $needSendUpdate = "";
    $needSendWarning = "";
    $needSendNews = "";
    if ($notifier->SendUpdate() == TRUE)
        $needSendUpdate = 'checked';
    if ($notifier->SendWarning() == TRUE)
        $needSendWarning = 'checked';
    if ($notifier->SendNews() == TRUE)
        $needSendNews = 'checked';

    $notifiersList[] = array('notifier' => $notifier,
                             'needSendUpdate' => $needSendUpdate,
                             'needSendWarning' => $needSendWarning,
                             'needSendNews' => $needSendNews,
                            );
}

$tpl->assign( 'notifiersList', $notifiersList );
$tpl->assign( 'notifiers', Sys::getNotifiers() );


// финальный вывод готового HTML
$tpl->draw( 'settings' );
?>
