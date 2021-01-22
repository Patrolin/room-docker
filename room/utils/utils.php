<?php

declare(strict_types=1);

namespace utils;

include_once "utils/debug.php";
include_once "utils/error.php";
include_once "utils/socket.php";


date_default_timezone_set("UTC");

function isUTF8($str): bool
{
  return (bool) preg_match('//u', $str);
}

function process_time(): float
{
  return microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
}

function random_bytes($bytes): string
{
  while (true) {
    try {
      return \random_bytes($bytes);
    } catch (\Exception $e) {
      sleep(0.1);
    }
  }
}


function getCurrentFile(): string
{
  return substr(__FILE__, strlen(getcwd()));
}

function getFirstFile(): string
{
  return "/" . $_SERVER["SCRIPT_NAME"];
}

function parse_query(string $str): array
{
  # result array
  $arr = array();

  # split on outer delimiter
  $pairs = explode('&', $str);

  # loop through each pair
  foreach ($pairs as $i) {
    # split into name and value
    $e = explode('=', $i, 2);
    $name = $e[0];
    $value = $e[1] ?? null;

    # if name already exists
    if (isset($arr[$name])) {
      # stick multiple values into an array
      if (is_array($arr[$name])) {
        $arr[$name][] = $value;
      } else {
        $arr[$name] = array($arr[$name], $value);
      }
    }
    # otherwise, simply stick it in a scalar
    else {
      $arr[$name] = $value;
    }
  }

  # return result array
  return $arr;
}
