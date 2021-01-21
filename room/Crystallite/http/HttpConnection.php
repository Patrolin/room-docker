<?php

declare(strict_types=1);

namespace http;

include_once "utils/utils.php";
include_once "Crystallite/abstract/abstract.php";


class Connection extends \Connection
{
  public $response = "";

  function read($bytes = 2048)
  {
    \error\assert($this->state !== \Connection::READ, "Cannot read() on Connection::READ");
    \error\assert($this->state !== \Connection::CLOSED, "Cannot read() on Connection::CLOSED");

    if ($msg = socket_read($this->sock, $bytes))
      $this->request .= $msg;
    else {
      $err = socket_last_error($this->sock);
      if ($err === \socket\ERROR_AGAIN) {
        if ($this->request) {
          $this->state = \Connection::READ;
        }
      } else {
        echo "\nREAD " . $err . ": " . socket_strerror($err) . "\n";
        $this->close();
      }
    }
  }

  function send(string $response)
  {
    \error\assert($this->state !== \Connection::OPEN, "Cannot send() on Connection::OPEN");

    if (\debug\getLevel() === \debug\HTTP)
      echo trim($response) . "\r\n\r\n\r\n";
    if (parent::send($response))
      $this->response = $response;
  }
}
