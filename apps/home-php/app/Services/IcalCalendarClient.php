<?php

declare(strict_types=1);

final class IcalCalendarClient
{
    public static function fetch(
        string $url
    ): string {
        $url = trim($url);

        self::validateUrl(
            $url
        );

        if (function_exists('curl_init')) {
            return self::fetchWithCurl(
                $url
            );
        }

        return self::fetchWithStreams(
            $url
        );
    }

    private static function fetchWithCurl(
        string $url
    ): string {
        $handle = curl_init(
            $url
        );

        if ($handle === false) {
            throw new RuntimeException(
                'Nie udało się uruchomić klienta HTTP.'
            );
        }

        curl_setopt_array(
            $handle,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT =>
                    'DomkiSztabinkiPMS/1.0 iCal',
            ]
        );

        $content = curl_exec(
            $handle
        );

        $httpCode = (int) curl_getinfo(
            $handle,
            CURLINFO_RESPONSE_CODE
        );

        $error = curl_error(
            $handle
        );

        curl_close(
            $handle
        );

        if ($content === false) {
            throw new RuntimeException(
                'Nie udało się pobrać kalendarza iCal'
                . (
                    $error !== ''
                        ? ': ' . $error
                        : '.'
                )
            );
        }

        if (
            $httpCode >= 400
        ) {
            throw new RuntimeException(
                'Serwer kalendarza iCal zwrócił błąd HTTP '
                . $httpCode
                . '.'
            );
        }

        return self::validateContent(
            (string) $content
        );
    }

    private static function fetchWithStreams(
        string $url
    ): string {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'follow_location' => 1,
                'max_redirects' => 5,
                'ignore_errors' => true,
                'header' =>
                    "User-Agent: DomkiSztabinkiPMS/1.0 iCal\r\n",
            ],
            'https' => [
                'method' => 'GET',
                'timeout' => 30,
                'follow_location' => 1,
                'max_redirects' => 5,
                'ignore_errors' => true,
                'header' =>
                    "User-Agent: DomkiSztabinkiPMS/1.0 iCal\r\n",
            ],
        ]);

        $content = @file_get_contents(
            $url,
            false,
            $context
        );

        if ($content === false) {
            throw new RuntimeException(
                'Nie udało się pobrać kalendarza iCal.'
            );
        }

        return self::validateContent(
            $content
        );
    }

    private static function validateUrl(
        string $url
    ): void {
        if ($url === '') {
            throw new InvalidArgumentException(
                'Nie podano adresu URL kalendarza iCal.'
            );
        }

        if (
            filter_var(
                $url,
                FILTER_VALIDATE_URL
            ) === false
        ) {
            throw new InvalidArgumentException(
                'Adres URL kalendarza iCal jest nieprawidłowy.'
            );
        }

        $scheme = strtolower(
            (string) parse_url(
                $url,
                PHP_URL_SCHEME
            )
        );

        if (
            !in_array(
                $scheme,
                [
                    'http',
                    'https',
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Kalendarz iCal musi korzystać z adresu HTTP lub HTTPS.'
            );
        }
    }

    private static function validateContent(
        string $content
    ): string {
        $content = trim(
            $content
        );

        if ($content === '') {
            throw new RuntimeException(
                'Pobrany kalendarz iCal jest pusty.'
            );
        }

        if (
            stripos(
                $content,
                'BEGIN:VCALENDAR'
            ) === false
        ) {
            throw new RuntimeException(
                'Pobrana zawartość nie wygląda jak prawidłowy kalendarz iCal.'
            );
        }

        return $content;
    }
}