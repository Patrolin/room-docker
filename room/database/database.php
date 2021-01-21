<?php

declare(strict_types=1);

namespace database;


class Database
{
  protected $conn;

  function __construct($dbname, $username = "root", $password = "")
  {
    var_dump([$dbname, $username, $password]);
    $this->conn = new \PDO("mysql:host=database;dbname=$dbname", $username, $password);
    $this->conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $this->conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_NAMED);
  }

  function getUUID($username)
  {
    $stmt = $this->conn->prepare("SELECT uuid FROM `users` WHERE username = :username");
    $stmt->execute([
      ":username" => $username
    ]);
    return $stmt->fetch();
  }
  function register(array $query)
  {
    $UUID = $this->getUUID($query["username"]);
    if ($UUID === false) {
      // TODO: implement Register
    }
    var_dump($UUID);
  }

  function login()
  {
    // TODO: implement Login
  }

  function createSession(array $value, int $expire = null)
  {
    if (!isset($expire))
      $expire = time() + 30 * 86400;

    do {
      $token = base64_encode(random_bytes(32));
      $stmt = $this->conn->prepare("SELECT token FROM `sessions` WHERE token = :token");
      $stmt->execute([":token" => $token]);
      var_dump($stmt->fetch());
      throw new \Exception();
    } while (true);

    $stmt = $this->conn->prepare("INSERT INTO `sessions` (token, expire, value) VALUES (:token, :expire, :value)");
    $stmt->execute([
      ":token" => $token,
      ":expire" => $expire,
      ":value" => $value,
    ]);
  }

  function loadSession()
  {
  }
}
