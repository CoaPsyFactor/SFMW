<?php

namespace Simple;

abstract class Database
{
    public const DEFAULT_CONNECTION_NAME = 'default';
    public const DEFAULT_DRIVER = 'mysql';
    public const DEFAULT_HOST = 'localhost';
    public const DEFAULT_PORT = 3306;
    public const DEFAULT_USER = 'root';
    public const DEFAULT_PASSWORD = '';

    /** @var \PDO[] */
    private static $connections = [];

    /** @var array */
    private static $configurations = [];

    /**
     * Prepare connection
     *
     * @param array $config
     */
    public static function addConnection(array $config): void
    {
        $name = $config['name'] ?? self::DEFAULT_CONNECTION_NAME;

        if (empty($config['schema'])) {
            throw new \RuntimeException('Missing schema parameter (database name)');
        }

        self::$configurations[$name] = [
            'driver' => $config['driver'] ?? self::DEFAULT_DRIVER, 'host' => $config['host'] ?? self::DEFAULT_HOST,
            'port' => $config['port'] ?? self::DEFAULT_PORT, 'user' => $config['user'] ?? self::DEFAULT_USER,
            'password' => $config['password'] ?? self::DEFAULT_PASSWORD, 'schema' => $config['schema'] ?? null,
        ];
    }

    /**
     * Get instance to PDO object. Attempt connect if connection is not live
     *
     * @param string $connectionName Name of connection
     * @return \PDO
     */
    public static function getConnection(string $connectionName = self::DEFAULT_CONNECTION_NAME): \PDO
    {
        if (false === (self::$connections[$connectionName] ?? null) instanceof \PDO) {
            self::connect($connectionName);
        }

        return self::$connections[$connectionName];
    }

    /**
     * Retrieve single entity from storage
     *
     * @param string $query
     * @param array $placeholders
     * @param string $connectionName
     * @return array|null
     */
    public static function fetch(
        string $query, array $placeholders = [], string $connectionName = self::DEFAULT_CONNECTION_NAME
    ): ?array
    {
        $statement = self::prepareAndExecute(self::getConnection($connectionName), $query, $placeholders);

        if (null === $statement) {
            return null;
        }

        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        return is_array($result) ? $result : [];
    }

    /**
     * Retrieve multiple entities from storage
     *
     * @param string $query
     * @param array $placeholders
     * @param string $connectionName
     * @return array
     */
    public static function fetchAll(
        string $query, array $placeholders = [], string $connectionName = self::DEFAULT_CONNECTION_NAME
    ): ?array
    {
        $statement = self::prepareAndExecute(self::getConnection($connectionName), $query, $placeholders);

        if (null === $statement) {
            return null;
        }

        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return is_array($results) ? $results : [];
    }

    /**
     * Prepare and execute PDO statement if possible
     *
     * @param \PDO $connection
     * @param string $query
     * @param array $placeholders
     * @return \PDOStatement|null
     */
    private static function prepareAndExecute(\PDO $connection, string $query, array $placeholders = []): ?\PDOStatement
    {
        $statement = self::prepare($connection, $query, $placeholders);

        if (null === $statement) {
            return null;
        }

        if (false === $statement->execute()) {
            return null;
        }

        return $statement;
    }

    /**
     * Prepare query for execution, bind values
     *
     * @param \PDO $connection
     * @param string $query
     * @param array $placeholders
     * @return \PDOStatement|null
     */
    private static function prepare(\PDO $connection, string $query, array $placeholders = []): ?\PDOStatement
    {
        $statement = $connection->prepare($query);

        if (false === $statement) {
            return null;
        }

        foreach ($placeholders as $placeholder => $value) {
            $statement->bindParam($placeholder, $value);
        }

        return $statement;
    }

    /**
     * @param string $connectionName Name under which configuration is stored
     * @return void
     */
    private static function connect(string $connectionName = self::DEFAULT_CONNECTION_NAME): void
    {
        if (empty(self::$configurations[$connectionName])) {
            throw new \RuntimeException("Invalid connection '{$connectionName}'.");
        }

        [
            'driver' => $driver, 'host' => $host, 'port' => $port,
            'user' => $user, 'password' => $password, 'schema' => $schema
        ] = self::$configurations[$connectionName];

        self::$connections[$connectionName] = new \PDO(
            "{$driver}:host={$host};port={$port};dbname={$schema};", $user, $password
        );
    }
}