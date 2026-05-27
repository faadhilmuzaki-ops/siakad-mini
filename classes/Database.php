<?php
// classes/Database.php
declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    // Tidak boleh diinstansiasi dari luar
    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            require_once __DIR__ . '/../config/database.php';
            self::$instance = getDB();
        }
        return self::$instance;
    }
}
"<?php // oop" 
