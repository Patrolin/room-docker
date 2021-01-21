<?php

declare(strict_types=1);

include_once "utils/utils.php";
include_once "database/database.php";
include_once "Crystallite/http/http.php";
include_once "Crystallite/websocket/websocket.php";

use database\Database;

define('debug\MAIN', debug\nextLevel());
debug\setLevel(debug\STATISTICS);

define('ROOT', getcwd());


class App extends websocket\Server
{
  protected $conversation = "";
  protected Database $database;
  protected $tps = 10;

  function __construct(...$args)
  {
    $this->database = new Database("room", "root", "groot");
    $this->database->register("lin");
    parent::__construct(...$args);
  }

  function httpResponse(int $i)
  {
    $conn = $this->connections[$i];

    [
      "method" => $method,
      "path" => $path,
      "headers" => $headers,
    ] = http\parseRequest($conn->request);

    $responseHeaders = [];

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

    if (isset($headers["Cookie"][0])) {
      $cookie = http\parseCookie($headers["Cookie"][0]);
      if (isset($cookie["SESSION"])) {
        $stmt = $this->pdo->prepare("SELECT * FROM `sessions` WHERE token = :token LIMIT 1");
        $stmt->execute([":token" => $cookie["SESSION"]]);
        var_dump($stmt->fetch());
        // TODO: load session from database
      }
      // Secure HttpOnly SameSite
    }

    switch ($path) {
      case "./":
        if (true || isset($_SESSION))
          $response = http\createResponse(http\OK, $responseHeaders, file_get_contents("client/index.html"));
        else {
          $responseHeaders["Location"] = "http://" . $this->host . ":" . $this->port . "/login";
          $response = http\createResponse("303 Temporary Redirect", $responseHeaders);
        }
        break;
      case "./login":
      case "./login/":
        if ($method === "GET")
          $response = http\createResponse(http\OK, $responseHeaders, file_get_contents("client/login.html"));
        else if ($method === "POST") {
          $response = http\createResponse(http\NOTIMPLEMENTED, $responseHeaders, file_get_contents("client/login.html"));
        }
        break;
      case "./register":
      case "./register/":
        if ($method === "GET")
          $response = http\createResponse(http\OK, $responseHeaders, file_get_contents("client/register.html"));
        else if ($method === "POST") {
          //$this->database->register();
          $responseHeaders["Location"] = "http://" . $this->host . ":" . $this->port . "/";
          $response = http\createResponse("303 Temporary Redirect", $responseHeaders);
        }
        break;
      default:
        chdir('./client');
        if (file_exists($path) && !preg_match('/\.html$/', $path)) {
          if (preg_match('/\.css$/', $path))
            $responseHeaders["Content-Type"] = "text/css";
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
      "path" => $path,
    ] = http\parseRequest($conn->request);

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
