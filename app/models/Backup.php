<?php

namespace SysInescolara\models;

class Backup
{
    private string $backupDir;
    private string $mysqldumpPath;
    private string $mysqlPath;

    public function __construct()
    {
        $this->backupDir = defined('ROOT_PATH') ? ROOT_PATH . '_backups' . DIRECTORY_SEPARATOR : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '_backups' . DIRECTORY_SEPARATOR;

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        $this->mysqldumpPath = $this->findMysqldump();
        $this->mysqlPath = $this->findMysql();
    }

    private function getConnectionOptions(string $dbHost, string $dbPort, bool $useSocketFallback = true): string
    {
        if ($useSocketFallback) {
            $socket = $this->detectSocket();
            if ($socket !== null) {
                return sprintf('--socket=%s', escapeshellarg($socket));
            }
        }
        return sprintf('--host=%s --port=%s', escapeshellarg($dbHost), escapeshellarg($dbPort));
    }

    private function detectSocket(): ?string
    {
        $possibleSockets = [
            '/run/mysqld/mysqld.sock',
            '/var/run/mysqld/mysqld.sock',
            '/var/lib/mysql/mysql.sock',
            '/tmp/mysql.sock',
        ];
        foreach ($possibleSockets as $socket) {
            if (file_exists($socket)) {
                return $socket;
            }
        }
        return null;
    }

    private function findMysqldump(): string
    {
        $xamppPath = 'C:\xampp\mysql\bin\mysqldump.exe';
        if (is_file($xamppPath)) {
            return $xamppPath;
        }
        $which = trim(shell_exec('which mysqldump 2>/dev/null') ?? '');
        if ($which !== '') {
            return $which;
        }
        return 'mysqldump';
    }

    private function findMysql(): string
    {
        $xamppPath = 'C:\xampp\mysql\bin\mysql.exe';
        if (is_file($xamppPath)) {
            return $xamppPath;
        }
        $which = trim(shell_exec('which mysql 2>/dev/null') ?? '');
        if ($which !== '') {
            return $which;
        }
        return 'mysql';
    }

    public function create(string $dbHost, string $dbPort, string $dbName, string $dbUser, string $dbPass, string $prefix = ''): array
    {
        $timestamp = date('Ymd_His');
        $label = $prefix !== '' ? $prefix . '_' : '';
        $filename = $label . $dbName . '_' . $timestamp . '.sql';
        $filepath = $this->backupDir . $filename;

        $connectionOpts = $this->getConnectionOptions($dbHost, $dbPort);

        $cmd = sprintf(
            '"%s" %s --user=%s --password=%s --routines --triggers --single-transaction --databases --default-character-set=utf8mb4 %s 1> "%s" 2>&1',
            $this->mysqldumpPath,
            $connectionOpts,
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            $dbName,
            $filepath
        );

        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !is_file($filepath) || filesize($filepath) === 0) {
            $errorMsg = !empty($output) ? implode("\n", $output) : 'Error desconocido al crear el respaldo';
            if (is_file($filepath)) {
                unlink($filepath);
            }
            return ['success' => false, 'message' => $errorMsg];
        }

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath),
        ];
    }

    public function restore(string $filename): array
    {
        $filepath = $this->backupDir . basename($filename);

        if (!is_file($filepath)) {
            return ['success' => false, 'message' => 'El archivo de respaldo no existe.'];
        }

        $dbName = $this->detectDatabaseFromFile($filepath);
        if ($dbName === null) {
            return ['success' => false, 'message' => 'No se pudo detectar la base de datos en el archivo.'];
        }

        $dbConfig = $this->getDbConfig($dbName);
        if ($dbConfig === null) {
            return ['success' => false, 'message' => "No se encontró configuración para la base de datos '$dbName'."];
        }

        $connectionOpts = $this->getConnectionOptions($dbConfig['host'], $dbConfig['port']);

        $filepath = str_replace('/', DIRECTORY_SEPARATOR, $filepath);

        $cmd = sprintf(
            '"%s" %s --user=%s --password=%s "%s"',
            $this->mysqlPath,
            $connectionOpts,
            escapeshellarg($dbConfig['user']),
            escapeshellarg($dbConfig['pass']),
            $dbConfig['name']
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            return ['success' => false, 'message' => 'No se pudo iniciar el proceso mysql.'];
        }

        $input = fopen($filepath, 'rb');
        if ($input) {
            $bom = fread($input, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($input);
            }
            stream_copy_to_stream($input, $pipes[0]);
            fclose($input);
        }
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $errorMsg = !empty($stderr) ? $stderr : (!empty($stdout) ? $stdout : 'Error desconocido al restaurar');
            return ['success' => false, 'message' => $errorMsg];
        }

        return ['success' => true, 'message' => "Base de datos '$dbName' restaurada correctamente."];
    }

    private function detectDatabaseFromFile(string $filepath): ?string
    {
        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return null;
        }

        $dbName = null;
        while (($line = fgets($handle)) !== false) {
            if (preg_match('/^-- Current Database: `(.+?)`/m', $line, $m)) {
                $dbName = $m[1];
                break;
            }
            if (preg_match('/^CREATE DATABASE .+? `(.+?)`/i', $line, $m)) {
                $dbName = $m[1];
                break;
            }
            if (preg_match('/^USE `(.+?)`/i', $line, $m)) {
                $dbName = $m[1];
                break;
            }
            if (preg_match('/^-- Host:.*Database:\s*(.+)$/i', $line, $m)) {
                $dbName = trim($m[1]);
                break;
            }
            if (ftell($handle) > 4096) {
                break;
            }
        }
        fclose($handle);

        return $dbName;
    }

    private function getDbConfig(string $dbName): ?array
    {
        $dbHost = getenv('DB_HOST') ?: 'localhost';
        $dbPort = getenv('DB_PORT') ?: '3306';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASSWORD') ?: '';
        $dbMain = getenv('DB_NAME') ?: 'sysinescolara';
        $dbSecName = getenv('DB_SEC_NAME') !== false ? getenv('DB_SEC_NAME') : 'SysInescolara-Seguridad';

        if (strcasecmp($dbName, $dbMain) === 0) {
            return [
                'host' => $dbHost,
                'port' => $dbPort,
                'user' => $dbUser,
                'pass' => $dbPass,
                'name' => $dbMain,
            ];
        }

        if (strcasecmp($dbName, $dbSecName) === 0) {
            return [
                'host' => getenv('DB_SEC_HOST') !== false ? getenv('DB_SEC_HOST') : $dbHost,
                'port' => getenv('DB_SEC_PORT') !== false ? getenv('DB_SEC_PORT') : $dbPort,
                'user' => getenv('DB_SEC_USER') !== false ? getenv('DB_SEC_USER') : $dbUser,
                'pass' => getenv('DB_SEC_PASSWORD') !== false ? getenv('DB_SEC_PASSWORD') : $dbPass,
                'name' => $dbSecName,
            ];
        }

        return null;
    }

    public function list(): array
    {
        $files = glob($this->backupDir . '*.sql');
        if ($files === false || empty($files)) {
            return [];
        }

        $backups = [];
        foreach ($files as $filepath) {
            $filename = basename($filepath);
            if (preg_match('/^(.*?)_(\d{8}_\d{6})\.sql$/', $filename, $m)) {
                $backups[] = [
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'db_label' => $m[1],
                    'timestamp' => $m[2],
                    'date' => $this->formatTimestamp($m[2]),
                    'size' => filesize($filepath),
                    'size_formatted' => $this->formatSize(filesize($filepath)),
                ];
            }
        }

        usort($backups, fn($a, $b) => strcmp($b['timestamp'], $a['timestamp']));

        return $backups;
    }

    public function delete(string $filename): bool
    {
        $filepath = $this->backupDir . basename($filename);
        if (is_file($filepath)) {
            return unlink($filepath);
        }
        return false;
    }

    public function getFilePath(string $filename): ?string
    {
        $filepath = $this->backupDir . basename($filename);
        return is_file($filepath) ? $filepath : null;
    }

    public function getBackupDir(): string
    {
        return $this->backupDir;
    }

    private function formatTimestamp(string $ts): string
    {
        $dt = \DateTime::createFromFormat('Ymd_His', $ts);
        if ($dt) {
            return $dt->format('d/m/Y h:i:s A');
        }
        return $ts;
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    public function getDbNames(): array
    {
        return [
            'main' => getenv('DB_NAME') ?: 'sysinescolara',
            'security' => getenv('DB_SEC_NAME') !== false ? getenv('DB_SEC_NAME') : 'SysInescolara-Seguridad',
        ];
    }

    public function getDbConfigs(): array
    {
        $dbHost = getenv('DB_HOST') ?: 'localhost';
        $dbPort = getenv('DB_PORT') ?: '3306';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASSWORD') ?: '';
        $dbMain = getenv('DB_NAME') ?: 'sysinescolara';
        $dbSecName = getenv('DB_SEC_NAME') !== false ? getenv('DB_SEC_NAME') : 'SysInescolara-Seguridad';

        return [
            'main' => [
                'host' => $dbHost,
                'port' => $dbPort,
                'user' => $dbUser,
                'pass' => $dbPass,
                'name' => $dbMain,
                'label' => 'Datos',
            ],
            'security' => [
                'host' => getenv('DB_SEC_HOST') !== false ? getenv('DB_SEC_HOST') : $dbHost,
                'port' => getenv('DB_SEC_PORT') !== false ? getenv('DB_SEC_PORT') : $dbPort,
                'user' => getenv('DB_SEC_USER') !== false ? getenv('DB_SEC_USER') : $dbUser,
                'pass' => getenv('DB_SEC_PASSWORD') !== false ? getenv('DB_SEC_PASSWORD') : $dbPass,
                'name' => $dbSecName,
                'label' => 'Seguridad',
            ],
        ];
    }
}
