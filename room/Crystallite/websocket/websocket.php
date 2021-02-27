<?php

declare(strict_types=1);

namespace websocket;

include_once "utils/utils.php";
include_once "Crystallite/websocket/WebsocketConnection.php";
include_once "Crystallite/websocket/WebsocketServer.php";


define('websocket\CONTINUED', 0);
define('websocket\TEXT',      1);
define('websocket\BINARY',    2);

define('websocket\CLOSE',     8);
define('websocket\PING',      9);
define('websocket\PONG',     10);

function isControlFrame(int $n)
{
  return $n >= 8;
}


function createMessage($FIN = 1, $opcode = \websocket\TEXT, $payload = "")
{
  if (\websocket\isControlFrame($opcode))
    \error\assert($FIN === 1, "Control frame must have \$FIN = 1");

  $MASK = 0; // Server must not mask any frames...
  $length = strlen($payload);

  $msgLength = 2 +
    ($length >= 126 ? ($length >= 2 ** 16 ? 8 : 2) : 0) +
    ($MASK ? 4 : 0) +
    $length;
  $msg = array_fill(0, $msgLength, 0);
  $i = 0;

  $msg[$i++] = ($FIN << 7) | ($opcode);

  if ($length < 126) {
    $msg[$i++] = ($MASK << 7) | $length;
  } else if ($length < 2 ** 16) {
    $msg[$i++] = ($MASK << 7) | 126;
    $msg[$i++] = ($length >> 8) & 0xFF;
    $msg[$i++] = ($length >> 0) & 0xFF;
  } else {
    assert($length < 2 ** 64, "Payload too big");
    $msg[$i++] = ($MASK << 7) | 127;
    $msg[$i++] = ($length >> 56) & 0xFF;
    $msg[$i++] = ($length >> 48) & 0xFF;
    $msg[$i++] = ($length >> 40) & 0xFF;
    $msg[$i++] = ($length >> 32) & 0xFF;
    $msg[$i++] = ($length >> 24) & 0xFF;
    $msg[$i++] = ($length >> 16) & 0xFF;
    $msg[$i++] = ($length >>  8) & 0xFF;
    $msg[$i++] = ($length >>  0) & 0xFF;
  }

  if ($MASK) {
    $msg[$i++] = random_int(0x00, 0xFF);
    $msg[$i++] = random_int(0x00, 0xFF);
    $msg[$i++] = random_int(0x00, 0xFF);
    $msg[$i++] = random_int(0x00, 0xFF);
  }
  for ($j = 0; $j < $length; $j++) {
    $x = ord($payload[$j]);
    if ($MASK)
      $x = $x ^ $msg[($i - 4) + ($j & 0b11)];
    $msg[$i + $j] = $x;
  }


  return pack('C*', ...$msg);
}

function parseMessage($msg)
{
  $bytes = unpack('C*', $msg);
  $i = 1; // unpack indexes from 1

  \error\assert(isset($bytes[$i]), "\$msg is missing first byte", "IncompleteRequest");
  $FIN    = ($bytes[$i]   & 0b10000000) >> 7; // 1-terminated packets
  $RSV1   = ($bytes[$i]   & 0b01000000) >> 6; // custom bits (not handled)
  $RSV2   = ($bytes[$i]   & 0b00100000) >> 5;
  $RSV3   = ($bytes[$i]   & 0b00010000) >> 4;
  $opcode = ($bytes[$i++] & 0b00001111) >> 0;
  if (\websocket\isControlFrame($opcode))
    \error\assert($FIN === 1, "Control frame must have \$FIN = 1", "BadRequest");


  \error\assert(isset($bytes[$i]), "\$msg is missing second byte", "IncompleteRequest");
  $MASK   = ($bytes[$i]   & 0b10000000) >> 7;
  $length = ($bytes[$i++] & 0b01111111) >> 0;

  switch ($length) {
    case 126:
      \error\assert($bytes[$i] !== null && $bytes[$i + 1] !== null, "\$msg is missing two length bytes", "IncompleteRequest");
      $length = ($bytes[$i++] << 8) + ($bytes[$i++] << 0);
      break;
    case 127:
      \error\assert(
        $bytes[$i] !== null && $bytes[$i + 1] !== null && $bytes[$i + 2] !== null && $bytes[$i + 3] !== null &&
          $bytes[$i + 4] !== null && $bytes[$i + 5] !== null && $bytes[$i + 6] !== null && $bytes[$i + 7] !== null,
        "\$msg is missing eight length bytes",
        "ValueError"
      );
      $length = ($bytes[$i++] << 56) + ($bytes[$i++] << 48) + ($bytes[$i++] << 40) + ($bytes[$i++] << 32)
        + ($bytes[$i++] << 24) + ($bytes[$i++] << 16) + ($bytes[$i++] <<  8) + ($bytes[$i++] <<  0);
      break;
  }

  $msgLength = 2 +
    ($length >= 126 ? ($length >= 2 ** 16 ? 8 : 2) : 0) +
    ($MASK ? 4 : 0) +
    $length;
  \error\assert(strlen($msg) >= $msgLength, "\$msg is missing part of the payload", "IncompleteRequest");

  // TODO(): handle custom subprotocols
  // TODO(): handle custom extensions

  $payload = str_repeat("?", $length);

  if ($MASK)
    $i += 4;
  for ($j = 0; $j < $length; $j++) {
    $x = $bytes[$i + $j];
    if ($MASK)
      $x = $x ^ $bytes[($i - 4) + ($j & 0b11)];
    $payload[$j] = chr($x);
  }

  return [
    "msgLength" => $msgLength,
    "FIN" => $FIN,
    #"RSV1" => $RSV1,
    #"RSV2" => $RSV2,
    #"RSV3" => $RSV3,
    "opcode" => $opcode,
    "payload" => $payload
  ];
}

\error\assert(
  \websocket\parseMessage(
    \websocket\createMessage(1, \websocket\TEXT, "hello world")
  )["payload"]
    === "hello world",
  "websocket parsing must succeed"
);
