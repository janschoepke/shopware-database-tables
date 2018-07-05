<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

$HOST = "172.16.238.10";
$USER = "web";
$PASS = "root";
$PORT = "3306";

$NAMES = [
    "sw_5_0_0",
    "sw_5_2_0",
    "sw_5_4_5"
];

//echo '<pre>';

function getSQLTables($database){
    global $mysqli;
    $mysqli->select_db($database);

    $result = $mysqli->query("SELECT TABLE_NAME AS tableName FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . $database . "';");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getTableRows($table) {
    global $mysqli;
    $result = $mysqli->query("DESCRIBE " . $table . ";");
    return $result->fetch_all(MYSQLI_NUM);
}

function getTableKeys($database, $table) {
    global $mysqli;
    $result = $mysqli->query("SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = '" . $database . "' AND REFERENCED_TABLE_NAME = '" . $table . "';");
    return $result->fetch_all(MYSQLI_NUM);
}

$mysqli = new mysqli($HOST, $USER, $PASS);

if ($mysqli->connect_errno) {
    printf("Connect failed: %s\n", $mysqli->connect_error);
    exit();
}

$tables;

foreach($NAMES as $name) {
    $tables = getSQLTables($name);
    foreach($tables as &$tableValue) {
        $tableValue['rowInformation'] = getTableRows($tableValue['tableName']);
        $tableValue['keyInformation'] = getTableKeys($name, $tableValue['tableName']);
    }
    echo "Inspection of " . $name . " finished successful. \n";
}

//var_dump($tables);

