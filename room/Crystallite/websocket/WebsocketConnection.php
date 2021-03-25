<?php

declare(strict_types=1);

namespace websocket;

include_once "utils/utils.php";
include_once "Crystallite/abstract/abstract.php";


class Connection extends \Connection
{
  public $incoming = 0;
  public $outgoing = 0;

  protected $buffer = "";
  protected $fragment = "";
  protected $fragmentOpcode = 0;

  public $request = "";
  public $response = "";

  public float $lastPing;
  public $room_state;

  function __construct($sock, $room_state)
  {
    $this->lastPing = \utils\process_time();
    parent::__construct($sock);
    $this->room_state = $room_state;
  }

  function read($bytes = 2048)
  {
    \error\assert($this->state !== Connection::READ, "Cannot read() on Connection::READ");
    \error\assert($this->state !== Connection::CLOSED, "Cannot read() on Connection::CLOSED", "ConnectionClosed");

    $msg = socket_read($this->sock, $bytes);
    if ($msg === false) {
      $err = socket_last_error($this->sock);
      if ($err !== \socket\ERROR_AGAIN) {
        echo "\nREAD " . $err . ": " . socket_strerror($err) . "\n";
        $this->close();
        return;
      }
    }

    $this->buffer .= $msg;
    try {
      [
        "msgLength" => $msgLength,
        "FIN" => $FIN,
        "opcode" => $opcode,
        "payload" => $payload
      ] = \websocket\parseMessage($this->buffer);
    } catch (\error\IncompleteRequest $e) {
      return;
    } catch (\error\BadRequest $e) {
      $this->close();
      return;
    }

    if ($FIN) {
      if (\websocket\isControlFrame($opcode)) {
        $this->request = substr($this->buffer, 0, $msgLength);
        $this->state = Connection::READ;
      } else if ($opcode !== \websocket\CONTINUED) {
        \error\assert($this->fragmentOpcode === 0, "Fragment must have an ending");
        $this->request = substr($this->buffer, 0, $msgLength);
        $this->state = Connection::READ;
      } else {
        \error\assert($this->fragmentOpcode !== 0, "Fragment must have a beginning");
        $opcode = $this->fragmentOpcode;
        $payload = $this->fragment . $payload;
        $this->request = \websocket\createMessage(1, $opcode, $payload);
        $this->fragmentOpcode = 0;
        $this->fragment = "";
        $this->state = Connection::READ;
      }

      if ($opcode === \websocket\TEXT && !\utils\isUTF8($payload)) {
        $this->close(); // TODO(): implement websocket status codes
      }
    } else {
      if ($opcode !== \websocket\CONTINUED) {
        \error\assert($this->fragmentOpcode === 0);
        $this->fragmentOpcode = $opcode;
        $this->fragment = $payload;
      } else
        $this->fragment .= $payload;
    }
    $this->buffer = substr($this->buffer, $msgLength);
  }

  function acknowledge()
  {
    \error\assert($this->state !== Connection::OPEN, "Cannot acknowledge() on Connection::OPEN");
    \error\assert($this->state !== Connection::CLOSED, "Cannot acknowledge() on Connection::CLOSED");

    $this->request = "";
    $this->response = "";
    $this->state = Connection::OPEN;
  }

  function send(string $response)
  {
    $msg = \websocket\parseMessage($response);
    $this->response = $msg;
    if (\debug\getLevel() === \debug\WEBSOCKETS)
      if ($msg["opcode"] !== \websocket\PING && $msg["opcode"] !== \websocket\PONG)
        echo "out: " . \debug\var_dump_str($msg) . "\n\n";

    if (parent::send($response))
      $this->outgoing++;
  }
}
