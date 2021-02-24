<?php

declare(strict_types=1);

include_once "utils/utils.php";
include_once "database/database.php";
include_once "Crystallite/http/http.php";
include_once "Crystallite/websocket/websocket.php";

use database\Database;

define('debug\MAIN', debug\nextLevel());
debug\setLevel(debug\WEBSOCKETS);

define('ROOT', getcwd());


class App extends websocket\Server
{
  protected $conversation = "";
  protected Database $database;
  protected $tps = 10;

  function __construct(...$args)
  {
    $this->database = new Database("room", "root", "groot");
    $this->database->register(0, [
      "username" => "lin",
      "password1" => "asdasdasd",
    ]);
    parent::__construct(...$args);
  }
  function load_session($headers)
  {
    if (isset($headers["Cookie"][0])) {
      $cookie = http\parseCookie($headers["Cookie"][0]);
      $session = $this->database->load_session($cookie["SESSION"]);
    } else {
      $cookie = [];
      $session = false;
    }
    return [$cookie, $session];
  }

  function httpResponse(int $i)
  {
    $conn = $this->connections[$i];

    [
      "method" => $method,
      "url" => $url,
      "headers" => $headers,
      "body" => $body,
    ] = http\parseRequest($conn->request);
    $host = $headers["Host"][0] ?? $headers["Origin"][0] ?? $headers["Referer"] ?? null;
    $path = $url["path"];

    $responseHeaders = [];
    $responseHeaders["Content-Type"] = "text/html; charset=UTF-8";

    switch ($method) {
      case "GET":
      case "POST":
        break;
      default:
        return http\createResponse(http\NOTIMPLEMENTED, $responseHeaders);
    }

    $path = ".$path";
    if (preg_match('/\.{2}/', $path))
      return http\createResponse(http\FORBIDDEN, $responseHeaders);

    $cookie = isset($headers["Cookie"][0])
      ? http\parseCookie($headers["Cookie"][0])
      : [];

    switch ($path) {
      case "./":
        $session = $this->database->load_session($cookie["SESSION"] ?? null);
        if ($session !== false)
          $response = http\createResponse(http\OK, $responseHeaders, file_get_contents("client/index.html"));
        else {
          $responseHeaders["Location"] = "http://$host/login";
          $response = http\createResponse(\http\SEEOTHER, $responseHeaders);
        }
        break;
      case "./login":
      case "./login/":
        switch ($method) {
          case "GET":
            $session = $this->database->load_session($cookie["SESSION"] ?? null);
            if ($session !== false) {
              $responseHeaders["Location"] = "http://$host/";
              $response = http\createResponse(\http\SEEOTHER, $responseHeaders);
            } else {
              $response = http\createResponse(http\OK, $responseHeaders, file_get_contents("client/login.html"));
            }
            break;
          case "POST":
            var_dump($body);
            $query = \utils\parse_query($body);
            if (!isset($query["login"]))
              break;
            switch ($query["login"]) {
              case "Register":
                try {
                  $register = $this->database->register(null, $query);
                } catch (\error\IncompleteRequest $e) {
                }
                $response = \http\createResponse(
                  \http\OK,
                  [],
                  $register,
                );
                break 2;
              case "Login":
                try {
                  $login = $this->database->login($query);
                } catch (\error\IncompleteRequest $e) {
                }
                $response = \http\createResponse(
                  \http\OK,
                  [],
                  $login,
                );
                break;
            }
        }
        if (!isset($response)) $response = \http\createResponse(\http\BADREQUEST);
        break;
      default:
        chdir('./client');
        if (file_exists($path) && !preg_match('/\.html$/', $path)) {
          switch (pathinfo($path, PATHINFO_EXTENSION)) {
            case 'css':
              $responseHeaders["Content-Type"] = "text/css";
              break;
            case 'js':
              $responseHeaders["Content-Type"] = "text/javascript";
              break;
          }
          $response = http\createResponse(http\OK, $responseHeaders, file_get_contents($path));
        } else
          $response = http\createResponse(http\NOTFOUND, $responseHeaders);
        chdir(ROOT);
        break;
    }

    if (isset($_SESSION)) {
      // TODO: store session in database
    }

    return $response;
  }

  function websocketResponse(int $i)
  {
    $conn = $this->connections[$i];
    [
      "url" => $url,
    ] = http\parseRequest($conn->request);
    $path = $url["path"];

    switch ($path) {
      case "/chat":
      case "/chat/":
        return [websocket\createMessage(1, websocket\TEXT, $this->conversation)];
      default:
        return http\createResponse(http\NOTFOUND);
    }
  }

  function websocketTick(int $i)
  {
    $conn = $this->connections[$i];
    if ($conn->state === Connection::READ) {
      [
        "opcode" => $opcode,
        "payload" => $payload,
      ] = websocket\parseMessage($conn->request);

      if ($opcode === websocket\TEXT) {
        if (debug\getLevel() === debug\MAIN)
          echo "$i. $payload\n";
        $this->conversation .= "$payload\n";
        foreach ($this->connections as $j => $c) {
          if ($c !== $conn && $c instanceof websocket\Connection && $c->state === Connection::OPEN) {
            $c->send(websocket\createMessage(1, websocket\TEXT, "$payload\n"));
            if (debug\getLevel() === debug\MAIN)
              echo ".$j $payload\n";
          }
        }
        $conn->acknowledge();
      }
    }
  }
}

$port = getenv('APP_PORT');
$port = $port ? $port : "8080";
$server = new App("0.0.0.0", (int) $port);
$server->run();
