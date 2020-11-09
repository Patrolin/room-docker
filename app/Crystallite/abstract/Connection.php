<?php

declare(strict_types=1);


abstract class Connection
{
  public $sock;

  const OPEN = 0;
  const READ = 1;
  const CLOSED = 2;

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
    \error\assert($this->state !== Connection::CLOSED, "Cannot send() on Connection::CLOSED");

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
}
