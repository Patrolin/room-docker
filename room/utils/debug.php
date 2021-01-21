<?php

declare(strict_types=1);

namespace debug;


function var_dump_str(): string
{
  $argc = func_num_args();
  $argv = func_get_args();

  if ($argc > 0) {
    ob_start();
    call_user_func_array('var_dump', $argv);
    $result = ob_get_contents();
    ob_end_clean();
    return $result;
  }

  return '';
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

define('debug\NONE',       nextLevel());
define('debug\STATISTICS', nextLevel());
define('debug\WEBSOCKETS', nextLevel());
define('debug\HTTP',       nextLevel());
