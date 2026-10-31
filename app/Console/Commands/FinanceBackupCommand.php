<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use ZipArchive;

class FinanceBackupCommand extends Command
{
    protected $signature = 'finance:backup';
    protected $description = 'Sauvegarde la base de données et les justificatifs financiers';

    public function handle(): int
    {
        $connectionName = config('database.default');
        $connection = config('database.connections.'.$connectionName, []);
        $driver = $connection['driver'] ?? '';
        $directory = storage_path('app/finance-backups');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            $this->error('Impossible de créer le répertoire privé des sauvegardes.');
            return self::FAILURE;
        }

        $stamp = now()->format('Ymd-His');
        $base = $directory.'/tenacos-finance-'.$stamp;
        $dumpExtension = $driver === 'sqlite' ? 'sqlite' : 'sql';
        $sqlPath = $base.'.'.$dumpExtension;
        $sqlGzipPath = $sqlPath.'.gz';
        $filesPath = $base.'-pieces.zip';

        if ($driver === 'sqlite') {
            $databasePath = $connection['database'] ?? null;
            if (! $databasePath || ! is_file($databasePath)) {
                $this->error('Fichier de base SQLite introuvable.');
                return self::FAILURE;
            }
            $pdo = app('db')->connection($connectionName)->getPdo();
            $quotedPath = "'".str_replace("'", "''", $sqlPath)."'";
            try {
                $pdo->exec('VACUUM INTO '.$quotedPath);
            } catch (\Throwable $exception) {
                $this->error('La copie SQLite a échoué : '.$exception->getMessage());
                return self::FAILURE;
            }
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            if (! $this->dumpMysql($connection, $sqlPath)) return self::FAILURE;
        } elseif ($driver === 'pgsql') {
            if (! $this->dumpPostgres($connection, $sqlPath)) return self::FAILURE;
        } else {
            $this->error('Pilote de base non pris en charge pour la sauvegarde : '.$driver);
            return self::FAILURE;
        }

        if (! $this->compress($sqlPath, $sqlGzipPath)) {
            @unlink($sqlPath);
            $this->error('La compression de la sauvegarde SQL a échoué.');
            return self::FAILURE;
        }
        @unlink($sqlPath);
        chmod($sqlGzipPath, 0600);

        if (! $this->archiveFinanceFiles($filesPath)) {
            @unlink($sqlGzipPath);
            $this->error('La sauvegarde des justificatifs financiers a échoué.');
            return self::FAILURE;
        }
        chmod($filesPath, 0600);
        $this->prune($directory, (int) env('FINANCE_BACKUP_RETENTION_DAYS', 30));

        $this->info('Sauvegarde terminée :');
        $this->line($sqlGzipPath);
        $this->line($filesPath);
        return self::SUCCESS;
    }

    private function dumpMysql(array $db, string $output): bool
    {
        $credentials = tempnam(sys_get_temp_dir(), 'tenacos-db-');
        if (! $credentials) {
            $this->error('Impossible de préparer les identifiants temporaires de la sauvegarde.');
            return false;
        }
        chmod($credentials, 0600);
        $escape = static fn ($value): string => '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $value).'"';
        file_put_contents($credentials, "[client]\nuser=".$escape($db['username'] ?? '')."\npassword=".$escape($db['password'] ?? '')."\nhost=".$escape($db['host'] ?? '127.0.0.1')."\nport=".$escape($db['port'] ?? '3306')."\n");
        $process = new Process([
            'mysqldump', '--defaults-extra-file='.$credentials, '--single-transaction', '--quick', '--routines', '--triggers', '--events',
            '--default-character-set=utf8mb4', '--result-file='.$output, (string) ($db['database'] ?? ''),
        ]);
        $process->setTimeout(900);
        try {
            $process->run();
        } catch (\Throwable $exception) {
            @unlink($credentials);
            $this->error('Impossible de lancer mysqldump : '.$exception->getMessage());
            return false;
        }
        @unlink($credentials);
        if (! $process->isSuccessful() || ! is_file($output) || filesize($output) === 0) {
            @unlink($output);
            $this->error('La sauvegarde MySQL a échoué. Vérifie la disponibilité de mysqldump et la configuration de la base.');
            return false;
        }
        chmod($output, 0600);
        return true;
    }

    private function dumpPostgres(array $db, string $output): bool
    {
        $env = [];
        if (! empty($db['password'])) $env['PGPASSWORD'] = $db['password'];
        $process = new Process([
            'pg_dump', '--format=plain', '--no-owner', '--no-privileges', '--file='.$output,
            '--host='.(string) ($db['host'] ?? '127.0.0.1'), '--port='.(string) ($db['port'] ?? '5432'),
            '--username='.(string) ($db['username'] ?? ''), (string) ($db['database'] ?? ''),
        ], null, $env);
        $process->setTimeout(900);
        try { $process->run(); } catch (\Throwable $exception) {
            $this->error('Impossible de lancer pg_dump : '.$exception->getMessage());
            return false;
        }
        if (! $process->isSuccessful() || ! is_file($output) || filesize($output) === 0) {
            @unlink($output);
            $this->error('La sauvegarde PostgreSQL a échoué. Vérifie la disponibilité de pg_dump et la configuration de la base.');
            return false;
        }
        chmod($output, 0600);
        return true;
    }

    private function compress(string $source, string $target): bool
    {
        $input = fopen($source, 'rb');
        $output = gzopen($target, 'wb9');
        if (! $input || ! $output) {
            if (is_resource($input)) fclose($input);
            if ($output) gzclose($output);
            return false;
        }
        while (! feof($input)) {
            $chunk = fread($input, 1024 * 1024);
            if ($chunk === false || gzwrite($output, $chunk) === false) {
                fclose($input); gzclose($output); @unlink($target); return false;
            }
        }
        fclose($input);
        gzclose($output);
        return is_file($target) && filesize($target) > 0;
    }

    private function archiveFinanceFiles(string $target): bool
    {
        if (! class_exists(ZipArchive::class)) {
            $this->error('L’extension PHP ZipArchive est requise pour archiver les justificatifs.');
            return false;
        }
        $zip = new ZipArchive();
        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return false;
        $root = storage_path('app/finance');
        if (is_dir($root)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->isFile()) $zip->addFile($file->getPathname(), 'finance/'.substr($file->getPathname(), strlen($root) + 1));
            }
        }
        $zip->addFromString('BACKUP-INFO.txt', 'TENACOS · pièces financières sauvegardées le '.now()->format('d/m/Y H:i:s')."\nRestaurer les fichiers uniquement dans un environnement de test avant toute remise en service.\n");
        return $zip->close();
    }

    private function prune(string $directory, int $retentionDays): void
    {
        $cutoff = now()->subDays(max(1, $retentionDays))->timestamp;
        foreach (glob($directory.'/tenacos-finance-*') ?: [] as $path) {
            if (is_file($path) && filemtime($path) < $cutoff) @unlink($path);
        }
    }
}
