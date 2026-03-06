<?php
session_start();
// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
    header("Location:../admin.php?id=login");
  } else {
$session = $_GET['session'];
$ping = $_GET['ping'];
if(isset($ping) && !empty($session)){
    include_once('../include/config.php');
    $iphost = explode('!', $data[$session][1])[1];
    $host=explode(":",$iphost)[0];
    $port=explode(":",$iphost)[1];
    if(empty($port)){
        $port = 8728;
    }else{
        $port = $port;
    }
function ping($host,$port){
	$fsock = fsockopen($host,$port,$errno,$errstr,5);
	if (! $fsock ){
		return (
            '<div id="pingX" class="col-12">
			<div class="card">
        	<div class="card-header">
            <h3 class="card-title">Ping Test ['.$host.':'.$port.'] </h3>
        	</div>
        	<div class="card-body">'.
            "Host : ".$host."&nbsp;Port : ".$port."<br>".
			"Error Code : ".$errno."<br>".
            "Error Message : ".$errstr.
            "<br><b class='text-warning'>Ping Timeout </b><br>".
            '<span class="pointer btn bg-grey" onclick="closeX()"><i class="fa fa-close text-red "></i> Close</span>'.
			'</div>
              </div>
            </div>');
	}else{
		return (
            '<div id="pingX" class="col-12">
			<div class="card">
        	<div class="card-header">
            <h3 class="card-title">Ping Test ['.$host.':'.$port.']</h3>
        	</div>
        	<div class="card-body">'.
            "Host : ".$host."&nbsp;Port : ".$port."<br>".
            "<b class='text-green'>Ping OK</b><br>".
            '<span class="pointer btn bg-grey" onclick="closeX()"><i class="fa fa-close text-red "></i> Close</span>'.
			'</div>
            </div>
            </div>');
	}
	fclose($fsock);
}

$ping_test = ping($host,$port);
print_r($ping_test);
}
}