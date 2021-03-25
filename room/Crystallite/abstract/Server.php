<?php

declare(strict_types=1);


abstract class Server
{
  protected $sock;
  protected string $host;
  protected int $port;
  protected $connections = [];
  protected $tps = 50; // maximum ticks per connection per second
  const heartbeatMin = 60; // seconds
  const heartbeatMax = 6000; // seconds

  function __construct($sock)
  {
    \error\assert(is_resource($sock), "\$sock must be a resource");
    $this->sock = $sock;
  }

  abstract function run();
}
