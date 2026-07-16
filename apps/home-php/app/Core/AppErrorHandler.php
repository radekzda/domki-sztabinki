<?php

declare(strict_types=1);

final class AppErrorHandler
{
    private static bool $debug = false;
    private static string $logFile = '';

    public static function register(array $config, string $basePath): void
    {
        self::$debug = (bool) ($config['debug'] ?? false);

        $logDirectory = rtrim($basePath, '/\\') . '/storage/logs';

        if (!is_dir($logDirectory)) {
            @mkdir($logDirectory, 0775, true);
        }

        self::$logFile = $logDirectory . '/app.log';

        error_reporting(E_ALL);

        ini_set('display_errors', self::$debug ? '1' : '0');
        ini_set('display_startup_errors', self::$debug ? '1' : '0');
        ini_set('log_errors', '1');

        if (self::$logFile !== '') {
            ini_set('error_log', self::$logFile);
        }

        set_exception_handler(
            static function (Throwable $exception): void {
                self::handleException($exception);
            }
        );

        register_shutdown_function(
            static function (): void {
                self::handleShutdown();
            }
        );
    }

    public static function isDebug(): bool
    {
        return self::$debug;
    }

    public static function safeMessage(Throwable $exception): string
    {
        self::log($exception);

        if (self::$debug) {
            return $exception->getMessage();
        }

        return 'Wystąpił błąd techniczny. Spróbuj ponownie później.';
    }

    public static function log(Throwable $exception): void
    {
        $message = sprintf(
            "[%s] %s: %s\nPlik: %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        self::writeLog($message);
    }

    private static function handleException(Throwable $exception): void
    {
        self::log($exception);

        $message = self::$debug
            ? $exception::class
                . ': '
                . $exception->getMessage()
                . ' w '
                . $exception->getFile()
                . ':'
                . $exception->getLine()
            : 'Wystąpił nieoczekiwany błąd techniczny. Spróbuj ponownie później.';

        if (headers_sent()) {
            echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

            return;
        }

        Response::html(
            View::render('pages/error', [
                'title' => 'Błąd aplikacji',
                'message' => $message,
            ]),
            500
        );
    }

    private static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        $fatalTypes = [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
        ];

        if (!in_array($error['type'], $fatalTypes, true)) {
            return;
        }

        $message = sprintf(
            "[%s] FATAL: %s\nPlik: %s:%d\n\n",
            date('Y-m-d H:i:s'),
            (string) ($error['message'] ?? 'Nieznany błąd'),
            (string) ($error['file'] ?? 'nieznany'),
            (int) ($error['line'] ?? 0)
        );

        self::writeLog($message);
    }

    private static function writeLog(string $message): void
    {
        if (self::$logFile === '') {
            error_log($message);

            return;
        }

        @file_put_contents(
            self::$logFile,
            $message,
            FILE_APPEND | LOCK_EX
        );
    }
}
