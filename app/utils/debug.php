<?php

declare(strict_types=1);

namespace debug;


function arrayify($x): array
{
  switch (gettype($x)) {
    case "object":
      $x = get_object_vars($x);
    case "array":
      $x = array_map("Debug::arrayify", $x);
    default:
      return $x;
  }
}
function prettyPrint($x)
{
  return json_encode($x, JSON_PRETTY_PRINT);
}


$level = 0;
function getLevel()
{
  global $level;
  return $level;
}
function setLevel(int $l)
{
  global $level, $levels;
  \error\assert(0 <= $l);
  \error\assert($l < $levels);
  $level = $l;
}

$levels = 0;
function nextLevel()
{
  global $levels;
  return $levels++;
}
function getLevels()
{
  global $levels;
  return $levels;
}

define('debug\NONE', nextLevel());
define('debug\MINIMAL', nextLevel());
define('debug\VERBOSE', nextLevel());
define('debug\STATISTICS', nextLevel());
