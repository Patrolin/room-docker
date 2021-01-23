<?php

declare(strict_types=1);

namespace database;


define('database\types', 0);
define('database\users', 1);
define('database\sessions', 2);

class Database
{
  protected $conn;

  function __construct($dbname, $username = "root", $password = "")
  {
    $this->conn = new \PDO("mysql:host=database;dbname=$dbname", $username, $password);
    $this->conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
    $this->conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_NAMED);
  }

  function register(?int $UUID, array $query): string
  {
    if ($UUID === null) $UUID = $this->new_UUID();
    \error\assert(isset($query["username"]), "Incomplete query", "IncompleteRequest");
    if (!isset($query["password1"]))
      return !$this->username_exists($query["username"]) ? '1' : '';

    $stmt = $this->conn->prepare("INSERT INTO `users` (`uuid`, `username`, `hash`) VALUES (:uuid, :username, :hash)");
    $users = $stmt->execute([
      ":uuid" => $UUID,
      ":username" => $query["username"],
      ":hash" => password_hash($query["password1"], PASSWORD_BCRYPT),
    ]);
    if ($users) {
      $stmt = $this->conn->prepare("INSERT INTO `routing` (`uuid`, `table`) VALUES (:uuid, :table)");
      $stmt->execute([
        ":uuid" => $UUID,
        ":table" => \database\users,
      ]);
    }
    return $users ? '1' : '';
  }
  function new_UUID(): int
  {
    // paranoid UUID generation
    do {
      $stmt = $this->conn->query("SELECT UUID_SHORT()");
      $UUID = $stmt->fetch()["UUID_SHORT()"];
    } while ($this->UUID_exists($UUID));
    return $UUID;
  }
  function UUID_exists($UUID): bool
  {
    $stmt = $this->conn->prepare("SELECT `uuid` FROM `routing` WHERE `uuid` = :uuid");
    $stmt->execute([
      ":uuid" => $UUID,
    ]);
    return $stmt->fetch() !== false;
  }
  function username_exists($username): bool
  {
    $stmt = $this->conn->prepare("SELECT `username` FROM `users` WHERE `username` = :username");
    $stmt->execute([
      ":username" => $username,
    ]);
    return $stmt->fetch() !== false;
  }

  function login(array $query): string
  {
    \error\assert(isset($query["username"]), "Incomplete query", "IncompleteRequest");
    if (!isset($query["password1"]))
      return !$this->username_exists($query["username"]) ? '1' : '';

    if ($this->username_exists($query["username"]) && $this->password_matches($query))
      return $this->create_session($query);
    return '';
  }
  function password_matches(array $query)
  {
    $stmt = $this->conn->prepare("SELECT `hash` FROM `users` WHERE `username` = :username");
    if (!$stmt->execute([
      ":username" => $query["username"]
    ])) return false;
    $hash = $stmt->fetch()["hash"];
    return password_verify($query["password1"], $hash);
  }
  function create_session(array $query, ?int $expire = null)
  {
    if ($expire === null)
      $expire = time() + 30 * 86400; // TODO: avoid the year 2038 problem

    $token = $this->new_token();

    $stmt = $this->conn->prepare("SELECT `uuid` FROM `users` WHERE `username` = :username");
    $stmt->execute([
      ":username" => $query["username"],
    ]);
    $UUID = $stmt->fetch()["uuid"];

    $stmt = $this->conn->prepare("INSERT INTO `sessions` (`token`, `expire`, `uuid`) VALUES (:token, :expire, :uuid)");
    $stmt->execute([
      ":token" => $token,
      ":expire" => $expire,
      ":uuid" => $UUID,
    ]);

    return "SESSION=$token; expires=never; path=/; SameSite=Lax; Secure=0"; // expire handled by server, TODO: implement https
  }
  function new_token(): string
  {
    // ensure token is unique
    do {
      $token = base64_encode(\utils\random_bytes(32)); // guarantee token doesn't include &
    } while ($this->token_exists($token));
    return $token;
  }
  function token_exists($token): bool
  {
    $stmt = $this->conn->prepare("SELECT `token` FROM `sessions` WHERE `token` = :token");
    $stmt->execute([
      ":token" => $token,
    ]);
    return $stmt->fetch() !== false;
  }

  function load_session($token)
  {
    if ($token === null) return false; // fast path

    $stmt = $this->conn->prepare("SELECT * FROM `sessions` WHERE `token` = :token");
    $stmt->execute([":token" => $token]);
    $session = $stmt->fetch();
    return $session;
  }
}
