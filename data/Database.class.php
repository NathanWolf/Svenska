<?php

namespace com\elmakers\svenska;

use Exception;
use PDO;

require_once 'config.inc.php';

class Database {
    private $pdo;

    private $server;
    private $database;
    private $user;
    private $password;
    private $port;
    private $retries = 5;
    private $rowsAffected;

    public function __construct($admin = false) {
        $parameters = _CONFIG['database'];

        $this->server = $parameters['server'];
        $this->database = $parameters['database'];
        $this->user = $admin ? $parameters['user_admin'] : $parameters['user'];
        $this->password = $admin ? $parameters['password_admin'] : $parameters['password'];
        $this->port = $parameters['port'] ?? null;
    }

    public function connect() {
        if (!$this->pdo) {
            try {
                $connection = $this->getConnectionString();
                $this->pdo = new PDO($connection, $this->user, $this->password);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (Exception $ex) {
                // PDO/MySQL error messages have a tendancy to reveal sensitive information.
                // So don't throw this up, just log it.
                error_log($ex->getMessage());
                error_log($ex->getTraceAsString());
                throw new Exception('Failed to connect to database');
            }
        }
        return $this->pdo;
    }

    public function getConnectionString() {
        $connection = 'mysql:host=' . $this->server . ';dbname=' . $this->database;
        $connection .= ';charset=utf8';
        if ($this->port) {
            $connection .= ';port=' . $this->port;
        }
        return $connection;
    }

    public function query($sql, $parameters = array(), $options = PDO::FETCH_ASSOC) {
        $statement = $this->prepare($sql, $parameters);
        if (!$statement->execute()) {
            throw new Exception("SQL statement failed to execute");
        }
        $records = array();
        while ($row = $statement->fetch($options)) {
            $records[] = $row;
        }

        return $records;
    }

    public function queryOne($table, $filter, $parameters = array()) {
        $rows = $this->query("SELECT * FROM $table WHERE $filter", $parameters);
        return $rows ? $rows[0] : null;
    }

    public function get($table, $id, $idField = 'id') {
        $rows = $this->query("SELECT * FROM $table WHERE $idField=:id", array('id' => $id));
        if ($rows) {
            if (count($rows) > 1) {
                throw new Exception("Duplicate records in $table for id $id");
            }
            return $rows[0];
        }
        return null;
    }

    public function getUnique($table, $values) {
        $filters = array();
        foreach ($values as $key => $value) {
            $filters[] = "$key = :$key";
        }
        $clause = join(' AND ', $filters);
        $rows = $this->query("SELECT * FROM $table WHERE $clause", $values);
        if ($rows) {
            if (count($rows) > 1) {
                throw new Exception("Duplicate records in $table for unique query");
            }
            return $rows[0];
        }
        return null;
    }

    public function getMultiple($table, $id, $idField = 'id') {
        return $this->query("SELECT * FROM $table WHERE $idField=:id", array('id' => $id));
    }

    public function getAll($table, $sort = null) {
        $sql = "SELECT * FROM $table";
        if ($sort) {
            $sql .= " ORDER BY $sort";
        }
        return $this->query($sql);
    }

    function prepare($sql, $parameters = array()) {
        $connection = $this->connect();
        $statement = $connection->prepare($sql);
        if (!$statement) {
            throw new Exception("Failed to create prepared statement: " . json_encode($connection->errorInfo()));
        }
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        return $statement;
    }

    function execute($sql, $parameters = array()) {
        $tries = $this->retries + 1;
        $statement = $this->prepare($sql);
        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                throw new Exception("Can't persist array for column $key");
            }
            $statement->bindValue($key, $value);
        }
        $success = false;
        while (!$success && $tries > 0) {
            try {
                $statement->execute();
                $this->rowsAffected = $statement->rowCount();
                $success = true;
            } catch (Exception $e) {
                $tries--;
                if ($tries <= 0) {
                    error_log("Exception executing SQL query: {$e->getMessage()}.  Query: '$sql'");
                    error_log($e->getTraceAsString());
                    throw new Exception("An error occurred executing a SQL query");
                }
            }
        }
        return $this->lastInsertId();
    }

    function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    function rowsAffected() {
        return $this->rowsAffected;
    }

    function insert($table, $data) {
        $fields = array_keys($data);
        $sql = "INSERT INTO $table (" . join(',', $fields);
        $sql .= ') values (:' . join(',:', $fields) . ')';
        return $this->execute($sql, $data);
    }

    function replace($table, $data) {
        $fields = array_keys($data);
        $sql = "REPLACE INTO $table (" . join(',', $fields);
        $sql .= ') values (:' . join(',:', $fields) . ')';
        return $this->execute($sql, $data);
    }

    function save($table, $row, $id = 'id', $id2 = null) {
        $update = array();
        $parameters = array();
        // This should be set automatically
        unset($row['updated']);
        foreach ($row as $key => $value) {
            if ($key != $id && $key != $id2) {
                $update[] = "$key = :$key";
                $parameters[$key] = $value;
            }
        }
        if (!isset($row[$id]) || !$row[$id]) {
            throw new Exception("Trying to save a row to $table without an id ($id)");
        }
        if ($id2 && (!isset($row[$id2]) || !$row[$id2])) {
            throw new Exception("Trying to save a row to $table without an secondary id ($id2)");
        }

        $sql = "update $table set " . implode(',', $update) . " where $id = :id";
        $parameters['id'] = $row[$id];
        if ($id2) {
            $sql .= " AND $id2 = :id2";
            $parameters['id2'] = $row[$id2];
        }
        $this->execute($sql, $parameters);
        return true;
    }

    function beginTransaction() {
        $connection = $this->connect();
        $connection->beginTransaction();
    }

    function rollBack() {
        $connection = $this->connect();
        $connection->rollBack();
    }

    function commit() {
        $connection = $this->connect();
        $connection->commit();
    }

    static function index($data, $id = 'id') {
        $results = array();
        foreach ($data as $record) {
            $recordId = $record[$id];
            $results[$recordId] = $record;
        }
        return $results;
    }

    static function multiIndex(&$data, $primary, $secondary) {
        $results = array();
        foreach ($data as &$record) {
            $recordId = $record[$primary];
            $secondaryValue = $record[$secondary];
            if (isset($results[$recordId])) {
                $results[$recordId][$secondaryValue] = $record;
            } else {
                $results[$recordId] = array($secondaryValue => $record);
            }
        }
        return $results;
    }
}
