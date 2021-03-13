<?php

declare(strict_types=1);


abstract class Connection
{
  public $sock;

  const OPEN      = 0;
  const READ      = 1;
  const CLOSED    = 2;
  const SUSPENDED = 3;

  public $state = Connection::OPEN;
  public $request = "";

  public float $lastPong;

  function __construct($sock)
  {
    \error\assert(is_resource($sock), "\$sock must be a resource");
    $this->sock = $sock;
    $this->lastPong = \utils\process_time();
  }

  function send(string $response)
  {
    if ($this->state === Connection::CLOSED) return false;

    if (@socket_write($this->sock, $response) === false) {
      $err = socket_last_error($this->sock);
      if ($err !== socket\ERROR_BROKENPIPE)
        echo "\nWRITE " . $err . ": " . socket_strerror($err) . "\n";
      $this->close();
      return false;
    } else
      return true;
  }

  function close()
  {
    \error\assert($this->state !== Connection::CLOSED, "Cannot close() on Connection::CLOSED");

    @socket_close($this->sock);
    $this->state = Connection::CLOSED;
  }

  function suspend()
  {
    \error\assert($this->state !== Connection::OPEN, "Cannot suspend() on Connection::OPEN");
    \error\assert($this->state !== Connection::CLOSED, "Cannot suspend() on Connection::CLOSED");
    $this->state = Connection::SUSPENDED;
  }
}
