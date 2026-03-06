<?php
session_start();
// hide all error
error_reporting(0);

	if ($removereport != "") {
		$uids = explode("~", $removereport);
	
		$nuids = count($uids);
	
		for ($i = 0; $i < $nuids; $i++) {
	
			$API->comm("/system/script/remove", array(
				".id" => "$uids[$i]",
			));
	
		}
		$_SESSION[$session.'idhr'] = "";
	}
	echo "<script>window.location='./?report=selling".$_SESSION['report']."&session=" . $session . "'</script>";