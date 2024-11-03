<?php

// include autoload or MikroLink.php
include('../src/MikroLink.php');

// use MikroLink class+
use MikrotikApi\MikroLink;

$api = new MikroLink;
// default: $api = new MikroLink(int $timeout = 1,  int $attempts = 3,int  $delay = 0,$logFile = 'mikrolink.log',$printLog = false);

$api->connect('192.168.1.1', 'admin', 'password', 8989);

$backupName = 'mikrotikfullbackup';
$api->exec('/system/backup/save', ['dont-encrypt' => 'yes', 'name' => $backupName]);

$api->disconnect();