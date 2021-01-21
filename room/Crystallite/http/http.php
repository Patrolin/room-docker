<?php

declare(strict_types=1);

namespace http;

include_once "utils/utils.php";
include_once "Crystallite/http/HttpConnection.php";
include_once "Crystallite/http/HttpServer.php";


define('http\SWITCHINGPROTOCOLS', '101 Switching Protocols');

define('http\OK', '200 OK');

define('http\FOUND', '302 Found (obsoleted by 303, 307)');
define('http\SEEOTHER', '303 See Other'); // force GET
define('http\TEMPORARYREDIRECT', '307 Temporary Redirect'); // keep method

define('http\BADREQUEST', '400 Bad Request');
define('http\FORBIDDEN', '403 Forbidden'); // authentication won't help
define('http\NOTFOUND', '404 Not Found');
define('http\UPGRADEREQUIRED', '426 Upgrade Required');

define('http\NOTIMPLEMENTED', '501 Not Implemented');


function createRequest($method = "GET", $path = "/", $version = 1.1, $headers = [])
{
  $result = "$method $path HTTP/$version";

  foreach ($headers as $h => $v)
    $result .= "\r\n$h: $v";

  return "$result\r\n\r\n";
}

function parseRequest(string $request)
{
  $lines = explode("\r\n", $request);

  preg_match('/(^[A-Z]+) (.+) HTTP\/([\d\.]+)/', $lines[0], $match);
  \error\assert($match !== [], "Invalid http request", "BadRequest");
  [$_, $method, $raw_url, $version] = $match;
  $url = parse_url($raw_url);
  \error\assert($url !== false, "Invalid http url", "BadRequest");

  $headers = [];
  for ($i = 1; $i < sizeof($lines); $i++) {
    if (strlen($lines[$i]) === 0)
      break;
    preg_match('/(.+?): (.*)/', $lines[$i], $match);
    \error\assert($match !== [], "Invalid http headers", "BadRequest");
    if (isset($headers[$match[1]]))
      $headers[$match[1]] .= ", " . $match[2];
    else
      $headers[$match[1]] = $match[2];
  }
  foreach ($headers as $k => $v)
    $headers[$k] = explode(", ", $v);


  switch ($method) {
    case "HEAD":
    case "GET":
    case "CONNECT":
    case "OPTIONS":
    case "TRACE":
      $hasBody = false;
      break;
    case "POST":
    case "PUT":
    case "DELETE":
    case "PATCH":
      $hasBody = true;
      break;
    default:
      \error\assert(false, "$method not supported", "NotImplemented");
  }
  if ($hasBody) {
    $body = [];
    for ($i++; $i < sizeof($lines); $i++) {
      $body[] = $lines[$i];
    }
    $body = implode("\r\n", $body);
  } else
    $body = "";

  return [
    "method" => $method,
    "url" => $url,
    "version" => +$version,
    "headers" => $headers,
    "body" => $body,
  ];
}


function createResponse($status = 200, $headers = [], $body = "")
{
  $result = "HTTP/1.1 $status";

  foreach ($headers as $h => $v)
    $result .= "\r\n$h: $v";

  return "$result\r\n\r\n" . ($body ? "$body\r\n\r\n" : "");
}

// TODO: implement parseResponse()


// TODO: implement createCookie()

function parseCookie(string $cookie)
{
  $result = [];
  foreach (explode(";", $cookie) as $kv) {
    preg_match('/(.+?)=(.*)/', trim($kv), $match);
    $result[$match[1]] = $match[2];
  }
  return $result;
}
