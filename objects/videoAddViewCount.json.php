<?php
header('Content-Type: application/json');
global $global, $config;
if(!isset($global['systemRootPath'])){
    require_once '../videos/configuration.php';
}
if (empty($_POST['id'])) {
    die('{"error":"'.__("Permission denied").'"}');
}
require_once $global['systemRootPath'].'objects/video.php';
$obj = new Video("", "", $_POST['id']);
if(empty($obj)){
    die("Object not found");
}

if(empty($_SESSION['addViewCount'])){
    $_SESSION['addViewCount'] = array();
}
// the video count one new view after the amount of time of the video lenght
if(empty($_SESSION['addViewCount'][$_POST['id']]) || $_SESSION['addViewCount'][$_POST['id']] <= time()){
    $resp = $obj->addView();
    $seconds = parseDurationToSeconds($obj->getDuration());
    $_SESSION['addViewCount'][$_POST['id']] = strtotime("+{$seconds} seconds");
}else{
    $resp = 0;
}
$count = $obj->getViews_count();
echo '{"status":"'.!empty($resp).'","count":"'.$count.'"}';
