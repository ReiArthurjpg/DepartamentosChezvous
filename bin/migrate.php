<?php

declare(strict_types=1);

// Carrega variáveis de ambiente do .env local
(static function (string $rootDir): void {
    $envFiles = [$rootDir . '/.env', $rootDir . '/.env.prod'];
    foreach ($envFiles as $envFile) {
        if (!is_file($envFile) || !is_readable($envFile)) {
            continue;
        }
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            continue;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if ($name !== '' && !isset($_ENV[$name]) && getenv($name) === false) {
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
        break;
    }
})(dirname(__DIR__));

$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
$db = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: '';
$user = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: '';
$pass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

if ($db === '' || $user === '') {
    fwrite(STDERR, "[FATAL] Credenciais do banco (DB_DATABASE, DB_USERNAME) não configuradas.\n");
    exit(1);
}

echo "[MIGRATE] Conectando ao banco {$db} em {$host}:{$port}...\n";

try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);
    $pdo = new PDO($dsn, (string) $user, (string) $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "[FATAL] Falha de conexao com MySQL: " . $e->getMessage() . "\n");
    exit(1);
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS `_migrations` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL UNIQUE,
        `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$executed = $pdo->query("SELECT `migration` FROM `_migrations`")->fetchAll(PDO::FETCH_COLUMN);
$executedSet = array_flip($executed);

$migrationsDir = dirname(__DIR__) . '/database/migrations';
if (!is_dir($migrationsDir)) {
    fwrite(STDERR, "[WARN] Diretorio de migracao '{$migrationsDir}' nao encontrado.\n");
    exit(0);
}

$files = scandir($migrationsDir, SCANDIR_SORT_ASCENDING);
if (!is_array($files)) {
    fwrite(STDERR, "[FATAL] Nao foi possivel ler o diretorio de migracoes.\n");
    exit(1);
}

$sqlFiles = array_filter($files, static fn (string $f): bool => str_ends_with($f, '.sql'));
natsort($sqlFiles);

$appliedCount = 0;
foreach ($sqlFiles as $file) {
    if (isset($executedSet[$file])) {
        continue;
    }

    $filePath = $migrationsDir . '/' . $file;
    $sql = file_get_contents($filePath);
    if ($sql === false || trim($sql) === '') {
        echo "[SKIP] Arquivo vazio: {$file}\n";
        continue;
    }

    echo "[APPLY] Executando {$file}...\n";
    try {
        $pdo->exec($sql);
        while ($pdo->query("SELECT 1")->nextRowset()) {}

        $stmt = $pdo->prepare("INSERT INTO `_migrations` (`migration`) VALUES (:migration)");
        $stmt->execute([':migration' => $file]);

        echo "[SUCCESS] {$file} aplicado com sucesso.\n";
        $appliedCount++;
    } catch (Throwable $e) {
        fwrite(STDERR, "[ERROR] Falha ao aplicar {$file}: " . $e->getMessage() . "\n");
        exit(1);
    }
}

if ($appliedCount === 0) {
    echo "[INFO] Nenhuma migracao pendente. Banco de dados atualizado.\n";
} else {
    echo "[INFO] Total de migracoes aplicadas nesta execucao: {$appliedCount}.\n";
}

exit(0);

