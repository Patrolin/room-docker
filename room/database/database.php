<?php

declare(strict_types=1);

namespace database;


define('database\channels', 0);
define('database\users',         1);
define('database\sessions',      2);
define('database\routing',       3);

class Database
{
  protected $conn;

  function __construct($dbhost, $dbname, $username = "root", $password = "")
  {
    var_dump("Connecting to database...");
    while (true) {
      try {
        $this->connect($dbhost, $dbname, $username, $password);
        break;
      } catch (\PDOException $e) {
        // PDOException is protected so i literally can't do anything here
        var_dump($e);
        sleep(10);
      };
    }
    var_dump("Connected to database!");
  }

  function connect($dbhost, $dbname, $username = "root", $password = "")
  {
    $this->conn = new \PDO("mysql:host=$dbhost;dbname=$dbname", $username, $password);
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

    $stmt = $this->conn->prepare("INSERT INTO `users` (`uuid`, `hash`) VALUES (:uuid, :hash)");
    $users = $stmt->execute([
      ":uuid" => $UUID,
      ":hash" => password_hash($query["password1"], PASSWORD_BCRYPT),
    ]);
    if ($users) {
      $stmt = $this->conn->prepare("INSERT INTO `channels` (`uuid`, `table`, `name`) VALUES (:uuid, :table, :name)");
      $channels = $stmt->execute([
        ":uuid" => $UUID,
        ":table" => \database\users,
        ":name" => $query["username"],
      ]);
      \error\assert($channels, "Can't INSERT into channels");
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
    $stmt = $this->conn->prepare("SELECT `uuid` FROM `channels` WHERE `uuid` = :uuid");
    $stmt->execute([
      ":uuid" => $UUID,
    ]);
    return $stmt->fetch() !== false;
  }
  function username_exists($username)
  {
    $stmt = $this->conn->prepare("SELECT `uuid` FROM `channels` WHERE `table` = :table AND `name` = :name");
    $stmt->execute([
      ":table" => \database\users,
      ":name" => $username,
    ]);
    return $stmt->fetch();
  }

  function login(array $query): string
  {
    // Validation
    \error\assert(isset($query["username"]), "Incomplete query", "IncompleteRequest");
    if (!isset($query["password1"]))
      return !$this->username_exists($query["username"]) ? '1' : '';

    // Login
    $channels = $this->username_exists($query["username"]);
    if ($channels) {
      $UUID = $channels["uuid"];
      if ($this->password_matches($query, $UUID))
        return $this->create_session($UUID, null);
    }
    return '';
  }
  function password_matches(array $query, $UUID)
  {
    $stmt = $this->conn->prepare("SELECT `hash` FROM `users` WHERE `uuid` = :uuid");
    $stmt->execute([
      ":uuid" => $UUID
    ]);
    $users = $stmt->fetch();
    if (!$users) return false;

    $hash = $users["hash"];
    return password_verify($query["password1"], $hash);
  }
  function create_session($UUID, ?int $expire = null)
  {
    if ($expire === null)
      $expire = time() + 30 * 86400; // TODO(): avoid the year 2038 problem

    $token = $this->new_token();

    $stmt = $this->conn->prepare("INSERT INTO `sessions` (`token`, `expire`, `uuid`) VALUES (:token, :expire, :uuid)");
    $stmt->execute([
      ":token" => $token,
      ":expire" => $expire,
      ":uuid" => $UUID,
    ]);

    return "SESSION=$token; expires=never; path=/; SameSite=Lax"; // expire handled by server, TODO(): implement https
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

  function search_channels($name)
  {
  }
}
