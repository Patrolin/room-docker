<?php

declare(strict_types=1);

namespace http;

include_once "utils/utils.php";
include_once "Crystallite/http/HttpConnection.php";
include_once "Crystallite/abstract/abstract.php";


abstract class Server extends \Server
{
  function __construct($host, $port)
  {
    $this->host = $host;
    $this->port = $port;

    $sock = socket_create(\socket\DOMAIN_IPV4, \socket\TYPE_STREAM, \socket\PROTOCOL_TCP) or die("socket_create() failed");
    socket_set_option($sock, \socket\LEVEL_SOCKET, \socket\OPTION_REUSEADDR, true) or die("socket_set_option() failed");
    socket_set_nonblock($sock) or die("socket_set_nonblock() failed");
    socket_bind($sock, $host, $port) or die("socket_bind() failed");
    socket_listen($sock, 20) or die("socket_listen() failed");
    parent::__construct($sock);

    echo "\r\nListening on http://" . $host . ":" . $port . "/\r\n\r\n\r\n";
  }

  function run()
  {
    $prevTime = \utils\process_time();
    while (true) {
      $timeBefore = \utils\process_time();
      $this->tick();
      $timeAfter = \utils\process_time();
      $tps = 1 / ($timeAfter - $prevTime);
      $prevTime = $timeAfter;
      $sleepDuration = max(0, (1 / $this->tps) - ($timeAfter - $timeBefore));
      if (\debug\getLevel() === \debug\STATISTICS)
        echo "TPS: " . $tps . "\r";
      usleep((int) (1e6 * $sleepDuration));
    }
  }

  function tick()
  {
    if (\debug\getLevel() === \debug\HTTP) {
      $prev_connections = $GLOBALS["pconnections"] ?? "";
      $curr_connections = \debug\var_dump_str($this->connections);
      if ($curr_connections !== $prev_connections) {
        echo "Handled: " . $curr_connections . "\n\n";
      }
      $GLOBALS["pconnections"] = $curr_connections;
    }
    $this->acceptHttp();

    if (\debug\getLevel() === \debug\HTTP) {
      $prev_connections = $GLOBALS["pconnections"] ?? "";
      $curr_connections = \debug\var_dump_str($this->connections);
      if ($curr_connections !== $prev_connections) {
        echo "Accepted: " . $curr_connections . "\n\n";
      }
      $GLOBALS["pconnections"] = $curr_connections;
    }
    foreach ($this->connections as $i => $conn)
      $this->handleConnection($i);
  }

  function acceptHttp()
  {
    while ($conn = socket_accept($this->sock)) { // TODO(): fight DoS
      socket_set_nonblock($conn);
      $this->connections[] = new \http\Connection($conn);
    }
  }


  function handleConnection(int $i)
  {
    $conn = $this->connections[$i];

    if ($conn instanceof \http\Connection)
      $this->httpHandshake($i);
    else {
      \error\assert($conn instanceof \Connection, "A connection must subclass Connection");
      \error\assert(false, "Unknown Connection class: " . get_class($conn));
    }
  }

  function httpHandshake(int $i)
  {
    $conn = $this->connections[$i];
    $this->httpHeartbeat($i);
    switch ($conn->state) {
      case \Connection::OPEN:
        $conn->read();
        break;
      case \Connection::READ:
        if (\debug\getLevel() === \debug\HTTP)
          echo trim($conn->request) . "\r\n\r\n\r\n";
        try {
          $response = $this->httpResponse($i);
        } catch (\error\NotImplemented $e) {
          $response = \http\createResponse(\http\NOTIMPLEMENTED);
        } catch (\error\BadRequest $e) {
          $response = \http\createResponse(\http\BADREQUEST);
        }
        $conn->send($response);
        $conn->close();
        break;
      case \Connection::CLOSED:
        unset($this->connections[$i]);
        break;
    }
  }

  function httpHeartbeat(int $i)
  {
    $conn = $this->connections[$i];
    $now = \utils\process_time();
    if (($now - $conn->lastPong) > self::heartbeatMax)
      $conn->close();
  }

  abstract function httpResponse(int $i);
}
