<?php
// Server in the this format: <computer>\<instance name> or
// <server>,<port> when using a non default port number
ini_set("error_reporting", E_ALL);
ini_set("display_errors", 1);
spl_autoload_register(function ($name) {
	$path = __DIR__;
	include($path . '/' . $name . '.php');
});


//$server   = 'd-db-w.itcs.uiuc.edu';
//$user     = 'ExtensionWebUser';
//$database = 'UIExtension';
//$password = 'Ca?R&st9sp#6';

$server   = getenv('DB_UIEXTENSION_HOST');
$user     = getenv('DB_UIEXTENSION_USERNAME');
$database = getenv('DB_UIEXTENSION_DBNAME');
$password = getenv('DB_UIEXTENSION_PASSWORD');


$odbc = new ODBC("Driver={ODBC Driver 13 for SQL Server};Server=$server;Database=$database;", $user, $password);