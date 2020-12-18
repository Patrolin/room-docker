<?php

declare(strict_types=1);

namespace utils;

include_once "utils/debug.php";
include_once "utils/error.php";
include_once "utils/socket.php";


date_default_timezone_set("UTC");

function process_time(): float
{
  return microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
}


function getCurrentFile(): string
{
  return substr(__FILE__, strlen(getcwd()));
}

function getFirstFile(): string
{
  return "/" . $_SERVER["SCRIPT_NAME"];
}
