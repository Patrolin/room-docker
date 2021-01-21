<?php

declare(strict_types=1);

namespace websocket;

include_once "utils/utils.php";
include_once "Crystallite/http/http.php";


abstract class Server extends \http\Server
{
  function handleConnection(int $i)
  {
    $conn = $this->connections[$i];

    if ($conn instanceof \websocket\Connection) {
      try {
        $this->websocketHeartbeat($i);
      } catch (\error\ConnectionClosed $e) { // I shouldn't have to do this, but PHP errors are terrible
        // pass
      }
      if (isset($this->connections[$i]))
        $this->websocketTick($i);
    } else if ($this->acceptWebsocket($i)) {
      $this->websocketHandshake($i);
    } else
      parent::handleConnection($i);
  }

  function websocketHeartbeat(int $i)
  {
    $conn = $this->connections[$i];
    switch ($conn->state) {
      case \Connection::OPEN:
        $now = \utils\process_time();
        if (($now - $conn->lastPong) > self::heartbeatMax) {
          $conn->close();
          return;
        } else if (($now - $conn->lastPing) > self::heartbeatMin) {
          $conn->send(\websocket\createMessage(1, \websocket\PING));
          $conn->lastPing = \utils\process_time();
        }

        $conn->read();
        if ($conn->state === \Connection::READ) {
          [
            "opcode" => $opcode,
            "payload" => $payload,
          ] = $msg = \websocket\parseMessage($conn->request);

          switch ($opcode) {
            case \websocket\CLOSE:
              if ($conn->state !== \Connection::CLOSED)
                $conn->close();
              break;
            case \websocket\PING:
              $conn->send(\websocket\createMessage(1, \websocket\PONG, $payload));
              $conn->lastPing = $now; // don't PING if client already did
            case \websocket\PONG:
              $conn->lastPong = $now; // don't close connection if either of us successfully PINGed
              $conn->acknowledge();
              break;
            default:
              break;
          }

          $conn->incoming++;
          if (\debug\getLevel() === \debug\WEBSOCKETS && $conn->state === \Connection::READ)
            echo "in: " . \debug\var_dump_str($msg) . "\n\n";
        }
        break;
      case \Connection::READ:
        \error\assert(false, "Unhandled Websocket connection");
      case \Connection::CLOSED:
        unset($this->connections[$i]);
        break;
      default:
        break;
    }
  }

  abstract function websocketTick(int $i);

  function acceptWebsocket(int $i)
  {
    $conn = $this->connections[$i];

    if (!($conn instanceof \http\Connection) || $conn->state !== \Connection::READ)
      return false;

    try {
      [
        "method" => $method,
        "version" => $version,
        "headers" => $headers,
      ] = \http\parseRequest($conn->request);
    } catch (\error\BadRequest $e) {
      return false;
    } catch (\error\NotImplemented $e) {
      return false;
    }


    return $method === "GET" &&
      $version >= 1.1 &&
      in_array("upgrade", array_map("strtolower", $headers["Connection"] ?? [])) &&
      in_array("websocket", array_map("strtolower", $headers["Upgrade"] ?? [])) &&
      strlen(base64_decode($headers["Sec-WebSocket-Key"][0] ?? "")) === 16; // 16 bytes
  }


  function websocketHandshake(int $i)
  {
    $conn = $this->connections[$i];
    if (\debug\getLevel() === \debug\WEBSOCKETS)
      echo $conn->request;

    [
      "headers" => $headers,
    ] = \http\parseRequest($conn->request);

    if (in_array("13", $headers["Sec-WebSocket-Version"])) {
      $response = $this->websocketResponse($i);
      if (is_array($response)) {
        $websocketAccept = base64_encode(sha1(trim($headers["Sec-WebSocket-Key"][0]) . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true));
        $upgrade = \http\createResponse(
          101,
          [
            "Connection" => "Upgrade",
            "Upgrade" => "websocket",
            "Sec-WebSocket-Accept" => $websocketAccept
          ]
        );
        $conn->send($upgrade);
        $conn = $this->connections[$i] = new \websocket\Connection($conn->sock);

        foreach ($response as $r)
          $conn->send($r);
        return;
      } else if ($response === null)
        $response = \http\createResponse(\http\NOTIMPLEMENTED);
    } else
      $response = \http\createResponse(\http\UPGRADEREQUIRED, ["Sec-WebSocket-Version" => "13"]);

    $conn->send($response);
    $conn->close();
  }


  abstract function websocketResponse(int $i);
}
