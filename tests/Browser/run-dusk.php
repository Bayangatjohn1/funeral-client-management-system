<?php

use Symfony\Component\Process\Process;

require __DIR__ . '/../../vendor/autoload.php';

$basePath = realpath(__DIR__ . '/../..');
$databasePath = $basePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database_dusk.sqlite';
$port = (int) ($_SERVER['DUSK_QA_PORT'] ?? getenv('DUSK_QA_PORT') ?: 8005);
while ($port < 8025 && @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1)) {
    $port++;
}

$serverUrl = "http://127.0.0.1:{$port}";
$driverUrl = 'http://127.0.0.1:9515';
$duskArguments = array_slice($_SERVER['argv'], 1);
$duskEnvFile = $basePath . DIRECTORY_SEPARATOR . '.env.dusk.local';
$duskEnv = [];

if (file_exists($duskEnvFile)) {
    foreach (file($duskEnvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $duskEnv[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }
}

$duskEnv['APP_URL'] = $serverUrl;
$duskEnv['APP_ENV'] = 'local';
$duskEnv['DB_CONNECTION'] = 'sqlite';
$duskEnv['DB_DATABASE'] = $databasePath;
$duskEnv['DUSK_DRIVER_URL'] = $driverUrl;

foreach ($duskEnv as $key => $value) {
    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

if (! file_exists($databasePath)) {
    touch($databasePath);
}

$run = function (array $command, ?string $label = null, array $env = []) use ($basePath): int {
    if ($label) {
        fwrite(STDOUT, PHP_EOL . "==> {$label}" . PHP_EOL);
    }

    $process = new Process($command, $basePath);
    if ($env !== []) {
        $process->setEnv($env);
    }
    $process->setTimeout(null);

    return $process->run(function ($type, $buffer) {
        fwrite($type === Process::ERR ? STDERR : STDOUT, $buffer);
    });
};

$stopProcessTree = function (Process $process): void {
    if (! $process->isRunning()) {
        return;
    }

    $pid = $process->getPid();

    if ($pid && PHP_OS_FAMILY === 'Windows') {
        pclose(popen('start /B "" taskkill /F /T /PID ' . (int) $pid . ' >NUL 2>NUL', 'r'));
        return;
    }

    $process->stop(3);
};

$clearExit = $run([
    PHP_BINARY,
    'artisan',
    'optimize:clear',
], 'Clearing cached Laravel state', $duskEnv);

if ($clearExit !== 0) {
    exit($clearExit);
}

$migrateExit = $run([
    PHP_BINARY,
    'artisan',
    'migrate:fresh',
    '--seed',
    '--force',
], 'Preparing isolated Dusk database', $duskEnv);

if ($migrateExit !== 0) {
    exit($migrateExit);
}

$chromeDriver = null;

$isDriverListening = static fn (): bool => (bool) @fsockopen('127.0.0.1', 9515, $errno, $errstr, 0.1);

if (! $isDriverListening()) {
    $chromeDriverPath = $basePath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'laravel' . DIRECTORY_SEPARATOR . 'dusk' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'chromedriver-win.exe';
    $chromeDriver = new Process([$chromeDriverPath, '--port=9515'], $basePath);
    $chromeDriver->disableOutput();
    $chromeDriver->setTimeout(null);
    $chromeDriver->start();

    $driverReady = false;
    $driverDeadline = time() + 15;

    while (time() < $driverDeadline) {
        if ($isDriverListening()) {
            $driverReady = true;
            break;
        }

        usleep(250000);
    }

    if (! $driverReady) {
        fwrite(STDERR, 'ChromeDriver did not become ready at ' . $driverUrl . PHP_EOL);
        exit(1);
    }
}

$server = new Process([
    PHP_BINARY,
    '-S',
    "127.0.0.1:{$port}",
    $basePath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'laravel' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Illuminate' . DIRECTORY_SEPARATOR . 'Foundation' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'server.php',
], $basePath . DIRECTORY_SEPARATOR . 'public');
$server->disableOutput();
$server->setEnv($duskEnv);
$server->setTimeout(null);
$server->start();

try {
    $ready = false;
    $deadline = time() + 30;

    while (time() < $deadline) {
        $headers = @get_headers($serverUrl . '/up');

        if (is_array($headers) && str_contains($headers[0] ?? '', '200')) {
            $ready = true;
            break;
        }

        usleep(250000);
    }

    if (! $ready) {
        fwrite(STDERR, "Dusk server did not become ready at {$serverUrl}." . PHP_EOL);
        exit(1);
    }

    $duskCommand = array_merge([
        PHP_BINARY,
        'vendor/phpunit/phpunit/phpunit',
        '-c',
        'phpunit.dusk.xml.dist',
    ], $duskArguments);

    fwrite(STDOUT, PHP_EOL . "==> Running browser QA suite at {$serverUrl}" . PHP_EOL);

    passthru(implode(' ', array_map(static fn ($part) => escapeshellarg($part), $duskCommand)), $duskExit);

    exit($duskExit);
} finally {
    // Windows can block indefinitely when stopping these child processes from
    // inside Symfony Process. The QA command returns its PHPUnit result first;
    // leftover runner-owned processes can be cleaned externally if needed.
}
