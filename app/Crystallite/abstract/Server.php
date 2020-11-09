<?php

declare(strict_types=1);


abstract class Server
{
  protected $sock;
  protected string $host;
  protected int $port;
  protected $connections = [];
  protected $tps = 50; // maximum ticks per connection per second
  const heartbeatMin = 5; // seconds
  const heartbeatMax = 60; // seconds

  function __construct($sock)
  {
    \error\assert(is_resource($sock), "\$sock must be a resource");
    $this->sock = $sock;
  }

  abstract function run();
}