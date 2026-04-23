<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();
$migrationsPath = dirname(__DIR__) . '/db/migrations';

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL,
        run_at DATETIME NOT NULL,
        UNIQUE KEY uq_migrations_name (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$files = glob($migrationsPath . '/*.sql');
if ($files === false) {
    $files = [];
}

sort($files);

$checkStatement = $pdo->prepare('SELECT 1 FROM migrations WHERE migration = :migration LIMIT 1');
$insertStatement = $pdo->prepare('INSERT INTO migrations (migration, run_at) VALUES (:migration, :run_at)');

foreach ($files as $file) {
    $migrationName = basename($file);

    $checkStatement->execute(['migration' => $migrationName]);
    if ($checkStatement->fetchColumn()) {
        echo "Skipping {$migrationName}\n";
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException('Unable to read migration: ' . $migrationName);
    }

    $pdo->exec($sql);
    $insertStatement->execute([
        'migration' => $migrationName,
        'run_at' => gmdate('Y-m-d H:i:s'),
    ]);

    echo "Applied {$migrationName}\n";
}

echo "Migrations complete.\n";
