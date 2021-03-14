<?php

declare(strict_types=1);

include_once "utils/utils.php";
include_once "database/database.php";
include_once "Crystallite/http/http.php";
include_once "Crystallite/websocket/websocket.php";

use database\Database;

\debug\setLevel(\debug\NONE);

define('ROOT', getcwd());


class App extends websocket\Server
{
  protected Database $database;
  protected $tps = 10;

  function __construct(...$args)
  {
    $dbhost = getenv('DB_HOST');
    $dbpass = getenv('DB_PASS');
    $this->database = new Database($dbhost, "room", "root", $dbpass);
    foreach (["lin", "foo", "bar", "baz"] as $name) {
      $this->database->register(null, [
        "username" => $name,
        "password1" => "asdasdasd",
      ]);
    }
    parent::__construct(...$args);
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
        $response = http\createResponse(http\OK, $responseHeaders, file_get_contents("client/index.html"));
        break;
      case "./chat":
        $session = $this->database->load_session($cookie["SESSION"] ?? null);
        if ($session !== false)
          $response = http\createResponse(http\OK, $responseHeaders, file_get_contents("client/chat.html"));
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
              $responseHeaders["Location"] = "http://$host/chat";
              $response = http\createResponse(\http\SEEOTHER, $responseHeaders);
            } else {
              $response = http\createResponse(http\OK, $responseHeaders, file_get_contents("client/login.html"));
            }
            break;
          case "POST":
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

    return $response;
  }

  function websocketResponse(int $i)
  {
    $conn = $this->connections[$i];
    [
      "url" => $url,
      "headers" => $headers,
    ] = http\parseRequest($conn->request);
    $path = $url["path"];

    $cookie = isset($headers["Cookie"][0])
      ? http\parseCookie($headers["Cookie"][0])
      : [];

    $session = $this->database->load_session($cookie["SESSION"] ?? null);

    switch ($path) {
      case "/chat":
      case "/chat/":
        if ($session !== false) {
          return [$session];
        } else {
          return http\createRequest(http\UNAUTHORIZED);
        }
      default:
        return http\createResponse(http\NOTFOUND);
    }
  }

  function tickSecond()
  {
    $online = new \Ds\Set();
    foreach ($this->connections as $c) {
      if ($c instanceof \websocket\Connection)
        $online->add($c->room_state["uuid"] . "");
    }
    foreach ($this->connections as $c) {
      if ($c instanceof \websocket\Connection)
        $c->send(websocket\createMessage(1, websocket\TEXT, json_encode([
          "type" => "online",
          "msg" => $online->toArray(),
        ])));
    }
  }

  function websocketTick(int $i)
  {
    $conn = $this->connections[$i];
    if ($conn->state === Connection::SUSPENDED)
      $conn->state = Connection::READ;
    if ($conn->state === Connection::READ) {
      [
        "opcode" => $opcode,
        "payload" => $payload,
      ] = websocket\parseMessage($conn->request);

      $A = $conn->room_state["uuid"];

      if ($opcode === websocket\TEXT) {
        $in = json_decode($payload, true);
        switch ($in["type"] ?? null) {
          case "hello":
            $this->sendHello($conn);
            break;
          case "search":
            $conn->send(websocket\createMessage(1, websocket\TEXT, json_encode([
              "type" => $in["type"],
              "msg" => $this->database->search(+$in["table"], $in["msg"]),
            ])));
            break;
          case "join":
            $B = $in["msg"];
            $this->database->join_channel($A, $B);
            foreach ($this->connections as $c) {
              if ($c instanceof websocket\Connection && ($c->room_state["uuid"] === $A || $c->room_state["uuid"] === $B))
                $this->sendHello($c);
            }
            break;
          case "block":
            $B = $in["msg"];
            $this->database->block_channel($A, $B);
            foreach ($this->connections as $c) {
              if ($c instanceof websocket\Connection && $c->room_state["uuid"] === $A)
                $this->sendHello($c);
            }
            break;
          case "reload":
            $conn->send(websocket\createMessage(1, websocket\TEXT, json_encode([
              "type" => $in["type"],
              "msg" => $this->database->reload_messages($A, $in["msg"]),
            ])));
            break;
          case "msg":
            $B = $in["B"];
            $timestamp = $this->database->send_message($A, $B, $in["msg"]);
            if ($timestamp === false) {
              $conn->suspend();
              return;
            }
            foreach ($this->connections as $c) {
              if ($c instanceof websocket\Connection) {
                if ($c->room_state["uuid"] === $A || $c->room_state["uuid"] === $B)
                  $this->sendMessage($c, $A, $B, $timestamp, $in["msg"]);
              }
            }
            break;
        }
        $conn->acknowledge();
      }
    }
  }
  function sendHello($c)
  {
    $A = $c->room_state["uuid"];
    $c->send(websocket\createMessage(1, websocket\TEXT, json_encode([
      "type" => "hello",
      "msg" => $this->database->get_user_info($A),
    ])));
  }
  function sendMessage($c, string $A, string $B, int $timestamp, string $msg)
  {
    $c->send(websocket\createMessage(1, websocket\TEXT, json_encode([
      "type" => "msg",
      "A" => $A . "",
      "B" => $B . "",
      "timestamp" => $timestamp . "",
      "msg" => $msg,
    ])));
  }
}

sleep(10);
$port = getenv('ROOM_PORT');
$port = $port ? $port : "8080";
$server = new App("0.0.0.0", (int) $port);
$server->run();
