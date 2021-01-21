<?php

declare(strict_types=1);

namespace database;


class Database
{
  function __contruct($dbname, $username = "root", $password = "")
  {
    $this->conn = new \PDO("mysql:host=localhost;dbname=$dbname", $username, $password);
    $this->conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $this->conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_NAMED);
  }

  function register($email, $username)
  {
    $stmt = $this->conn->prepare("SELECT uid FROM `users` WHERE email = :email OR username = :username");
    $stmt->execute([
      ":email" => $email,
      ":username" => $username
    ]);
    var_dump($stmt->fetch());
  }

  function login()
  {
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
