<?php
spl_autoload_register(function ($class) {
    if (strpos($class, "App\\") === 0) {
        require_once __DIR__ . "/src/" . str_replace("\\", "/", substr($class, 4)) . ".php";
    }
});
$db = App\Config\Database::getConnection();
print_r($db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'obreiros'")->fetchAll(PDO::FETCH_ASSOC));

