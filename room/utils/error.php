<?php

declare(strict_types=1);

namespace error;


function assert(bool $b, $msg = "", $classname = "Exception")
{
  $class = strcmp($classname, "Exception")
    ? "error\\$classname"
    : "Exception";
  if (!$b)
    throw new $class("$msg\n");
}


class BadRequest extends \Exception
{
}

class NotImplemented extends \Exception
{
}

class IncompleteRequest extends \Exception
{
}

class ConnectionClosed extends \Exception
{
}
