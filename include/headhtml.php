<?php
session_start();
 // hide all error
error_reporting(0);
?>
<!DOCTYPE html>
<html>
	<head>
		<title>MIKHMON <?= $hotspotname; ?></title>
		<meta charset="utf-8">
		<meta http-equiv="cache-control" content="private" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<!-- Tell the browser to be responsive to screen width -->
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<!-- Theme color -->
		<meta name="theme-color" content="<?= $themecolor ?>" />
		<!-- Font Awesome -->
		<link rel="stylesheet" type="text/css" href="css/font-awesome/css/font-awesome.min.css" />
		<!-- Mikhmon UI -->
		<link rel="stylesheet" href="css/mikhmon-ui.<?= $theme; ?>.min.css">
		<!-- favicon -->
		<link rel="icon" href="./img/favicon.png" />
		<!-- jQuery -->
		<script src="js/jquery.min.js"></script>
		<!-- pace -->
		<link href="css/pace.<?= $theme; ?>.css" rel="stylesheet" />
		<script src="js/pace.min.js"></script>

		
	</head>
	<body>
		<div class="wrapper">

			
