<?php
$dir = str_replace('include', '', dirname(__FILE__));

include_once $dir."class/System.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));

include_once $dir."class/Database.class.php";
include_once $dir."class/Trackers.class.php";
include_once $dir."class/Lib/rain.tpl.class.php";

$credential = Database::getAllCredentials();
foreach ($credential as $key => $value) {
    if ( !Trackers::useAuthentication($value['tracker']) ) {
        unset($credential[$key]);
    }
}

// заполнение шаблона
raintpl::configure("root_dir", $dir );
raintpl::configure("tpl_dir" , Sys::getTemplateDir() );

$tpl = new RainTPL;

$tpl->assign( "credential", $credential );

$tpl->draw( 'credentials' );
?>
