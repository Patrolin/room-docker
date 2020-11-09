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

  function __construct($sock)
  {
    $this->lastPing = \utils\process_time();
    parent::__construct($sock);
  }

  function read($bytes = 2048)
  {
    \error\assert($this->state !== Connection::READ, "Cannot read() on Connection::READ");
    \error\assert($this->state !== Connection::CLOSED, "Cannot read() on Connection::CLOSED");

    if ($msg = socket_read($this->sock, $bytes)) {
      $this->buffer .= $msg;
      try {
        [
          "msgLength" => $msgLength,
          "FIN" => $FIN,
          "opcode" => $opcode,
          "payload" => $payload
        ] = \websocket\parseMessage($this->buffer);
      } catch (\error\IncompleteRequest $e) {
        // pass
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

        if ($opcode === \websocket\TEXT && !mb_check_encoding($payload, "UTF-8")) {
          $this->close(); // TODO: implement websocket status codes
        }
      } else {
        if ($opcode !== \websocket\CONTINUED) {
          \error\assert($this->fragmentOpcode === 0);
          $this->fragmentOpcode = $opcode;
          $this->fragment = $payload;
        } else
          $this->fragment .= $payload;
      }
      $this->buffer = substr($this->buffer, $msgLength, strlen($this->buffer) - $msgLength);
    } else {
      $err = socket_last_error($this->sock);
      if ($err !== \socket\ERROR_AGAIN) {
        echo "\nREAD " . $err . ": " . socket_strerror($err) . "\n";
        $this->close();
      }
    }
  }

  function acknowledge()
  {
    \error\assert($this->state !== Connection::OPEN, "Cannot acknowledge() on Connection::OPEN");
    \error\assert($this->state !== Connection::CLOSED, "Cannot acknowledge() on Connection::CLOSED");

    $this->request = "";
    $this->state = Connection::OPEN;
  }

  function send(string $response)
  {
    $msg = \websocket\parseMessage($response);
    $this->response = $msg;
    if (\debug\getLevel() === \debug\MINIMAL)
      if ($msg["opcode"] !== \websocket\PING && $msg["opcode"] !== \websocket\PONG)
        echo "out: " . \debug\prettyPrint($msg) . "\n\n";

    if (parent::send($response))
      $this->outgoing++;
  }
}