<?php

class Database
{
    private ?PDO $connection = null;

    private ?string $databaseUrl = null;


    public function __construct()
    {
        try {
            $this->loadEnv();
        } catch (Exception $e) {
            fwrite(STDOUT, $e->getMessage());
            die($e->getMessage());
        }

        $this->connect();
    }

    /**
     * @throws Exception
     */
    private function loadEnv(): void
    {
        $envPath = __DIR__ . '/../.env';

        if (!file_exists($envPath)) {
            throw new Exception('.env-File not found: ' . $envPath);
        }

        $envFileContent = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($envFileContent as $line) {
            // Ignore comments
            if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
                continue;
            }

            // Key extraction
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Umgebung setzen
            $_ENV[$key] = $value;
        }

        $this->databaseUrl = trim($_ENV['DATABASE_URL'] ?? '', '"');


        if (empty($this->databaseUrl)) {
            throw new Exception('DATABASE_URL not found.');
        }
    }

    /**
     * Establishes and returns a PDO database connection using the provided database URL.
     *
     * @return PDO|null Returns the PDO connection or null on failure.
     */
    public function connect(): ?PDO
    {
        $parsedUrl = parse_url($this->databaseUrl);

        $host = $parsedUrl['host'] ?? '';
        $port = $parsedUrl['port'] ?? '3306';
        $user = $parsedUrl['user'] ?? '';
        $password = $parsedUrl['pass'] ?? '';
        $database = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '';

        // Extract optional query parameters
        $queryParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }

        // Build DSN with optional parameters
        $dsnOptions = "charset=utf8mb4";
        $dsn = "mysql:host=$host;port=$port;dbname=$database;$dsnOptions";

        // Build PDO options
        $pdoOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $user, $password, $pdoOptions);
            fwrite(STDOUT, "Successfully connected to the database\n");
            return $this->connection;
        } catch (PDOException $e) {
            fwrite(STDERR, "Error while connecting to MySQL: " . $e->getMessage() . "\n");
            return null;
        }
    }


    /**
     * @param string $query
     * @return bool
     */
    public function executeQuery(string $query): bool
    {
        try {
            $this->connection?->exec($query);
            echo "Query executed successfully\n";
            return true;
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function fetchResults(string $query): ?array
    {
        try {
            $stmt = $this->connection?->query($query);
            return $stmt?->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return null;
        }
    }

    /**
     * @return void
     */
    public function close(): void
    {
        $this->connection = null;
        echo "MySQL connection is closed\n";
    }

    /**
     * Retrieves the current database connection instance.
     *
     * @return PDO|null Returns a PDO object if the connection exists, or null if the connection is not established.
     */
    public function getConnection(): ?PDO
    {
        return $this->connection;
    }

}