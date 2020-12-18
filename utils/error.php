<?php

declare(strict_types=1);

namespace error;


function assert(bool $b, $msg = "", $classname = "Exception")
{
  $class = "error\\$classname";
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
