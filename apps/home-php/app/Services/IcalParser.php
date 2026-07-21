<?php

declare(strict_types=1);

final class IcalParser
{
    /**
     * @return array<int, array{
     *     uid: string,
     *     start_date: string,
     *     end_date: string,
     *     summary: string,
     *     description: string,
     *     status: string,
     *     start_raw: string,
     *     end_raw: string,
     *     all_day: bool
     * }>
     */
    public static function parse(string $content): array
    {
        $content = trim($content);

        if ($content === '') {
            return [];
        }

        $lines = self::unfoldLines($content);

        $events = [];
        $currentEventLines = [];
        $insideEvent = false;

        foreach ($lines as $line) {
            $normalizedLine = strtoupper(
                trim($line)
            );

            if ($normalizedLine === 'BEGIN:VEVENT') {
                $insideEvent = true;
                $currentEventLines = [];

                continue;
            }

            if ($normalizedLine === 'END:VEVENT') {
                if ($insideEvent) {
                    $event = self::parseEvent(
                        $currentEventLines
                    );

                    if ($event !== null) {
                        $events[] = $event;
                    }
                }

                $insideEvent = false;
                $currentEventLines = [];

                continue;
            }

            if ($insideEvent) {
                $currentEventLines[] = $line;
            }
        }

        return $events;
    }

    /**
     * Zabezpiecza synchronizację przed cichym pominięciem
     * nieprawidłowych bloków VEVENT.
     *
     * Jeżeli źródłowy kalendarz zawiera więcej bloków VEVENT
     * niż parser zwrócił poprawnych wydarzeń, synchronizacja
     * powinna zostać przerwana przed dezaktywacją istniejących
     * blokad.
     *
     * @param array<int, array<string, mixed>> $events
     */
    public static function assertCompleteParse(
        string $content,
        array $events
    ): void {
        $rawEventCount = preg_match_all(
            '/^BEGIN:VEVENT\s*$/mi',
            $content
        );

        if ($rawEventCount === false) {
            $rawEventCount = 0;
        }

        $parsedEventCount = count(
            $events
        );

        if ($rawEventCount > $parsedEventCount) {
            throw new RuntimeException(
                'Kalendarz iCal zawiera '
                . $rawEventCount
                . ' bloków VEVENT, ale parser poprawnie odczytał tylko '
                . $parsedEventCount
                . '. Synchronizacja została przerwana, aby nie dezaktywować poprawnych blokad.'
            );
        }
    }

    /**
     * @return array<int, array{
     *     uid: string,
     *     start_date: string,
     *     end_date: string,
     *     summary: string,
     *     description: string,
     *     status: string,
     *     start_raw: string,
     *     end_raw: string,
     *     all_day: bool
     * }>
     */
    public static function parseFile(
        string $path
    ): array {
        if (
            $path === ''
            || !is_file($path)
            || !is_readable($path)
        ) {
            throw new RuntimeException(
                'Nie można odczytać pliku iCal.'
            );
        }

        $content = file_get_contents(
            $path
        );

        if ($content === false) {
            throw new RuntimeException(
                'Nie udało się wczytać pliku iCal.'
            );
        }

        return self::parse(
            $content
        );
    }

    /**
     * @return array<int, string>
     */
    private static function unfoldLines(
        string $content
    ): array {
        $lines = preg_split(
            '/\r\n|\n|\r/',
            $content
        );

        if (!is_array($lines)) {
            return [];
        }

        $unfolded = [];

        foreach ($lines as $line) {
            if (
                $line !== ''
                && (
                    str_starts_with(
                        $line,
                        ' '
                    )
                    || str_starts_with(
                        $line,
                        "\t"
                    )
                )
                && $unfolded !== []
            ) {
                $lastIndex = array_key_last(
                    $unfolded
                );

                if ($lastIndex !== null) {
                    $unfolded[$lastIndex] .= substr(
                        $line,
                        1
                    );
                }

                continue;
            }

            $unfolded[] = $line;
        }

        return $unfolded;
    }

    /**
     * @param array<int, string> $lines
     * @return array{
     *     uid: string,
     *     start_date: string,
     *     end_date: string,
     *     summary: string,
     *     description: string,
     *     status: string,
     *     start_raw: string,
     *     end_raw: string,
     *     all_day: bool
     * }|null
     */
    private static function parseEvent(
        array $lines
    ): ?array {
        $properties = [];

        foreach ($lines as $line) {
            $property = self::parseProperty(
                $line
            );

            if ($property === null) {
                continue;
            }

            $name = $property['name'];

            if (!isset($properties[$name])) {
                $properties[$name] = [];
            }

            $properties[$name][] = $property;
        }

        $uid = self::firstValue(
            $properties,
            'UID'
        );

        $startRaw = self::firstValue(
            $properties,
            'DTSTART'
        );

        $endRaw = self::firstValue(
            $properties,
            'DTEND'
        );

        if (
            $uid === ''
            || $startRaw === ''
        ) {
            return null;
        }

        $startDate = self::dateFromIcalValue(
            $startRaw
        );

        if ($startDate === null) {
            return null;
        }

        $endDate = self::dateFromIcalValue(
            $endRaw
        );

        if ($endDate === null) {
            $endDate = (
                new DateTimeImmutable(
                    $startDate
                )
            )
                ->modify('+1 day')
                ->format('Y-m-d');

            $endRaw = '';
        }

        $startProperty = self::firstProperty(
            $properties,
            'DTSTART'
        );

        $allDay = self::isAllDayProperty(
            $startProperty,
            $startRaw
        );

        return [
            'uid' => trim(
                $uid
            ),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'summary' => self::decodeText(
                self::firstValue(
                    $properties,
                    'SUMMARY'
                )
            ),
            'description' => self::decodeText(
                self::firstValue(
                    $properties,
                    'DESCRIPTION'
                )
            ),
            'status' => strtoupper(
                trim(
                    self::firstValue(
                        $properties,
                        'STATUS'
                    )
                )
            ),
            'start_raw' => $startRaw,
            'end_raw' => $endRaw,
            'all_day' => $allDay,
        ];
    }

    /**
     * @return array{
     *     name: string,
     *     value: string,
     *     params: array<string, string>
     * }|null
     */
    private static function parseProperty(
        string $line
    ): ?array {
        $colonPosition = strpos(
            $line,
            ':'
        );

        if ($colonPosition === false) {
            return null;
        }

        $definition = substr(
            $line,
            0,
            $colonPosition
        );

        $value = substr(
            $line,
            $colonPosition + 1
        );

        $parts = explode(
            ';',
            $definition
        );

        $name = strtoupper(
            trim(
                (string) array_shift(
                    $parts
                )
            )
        );

        if ($name === '') {
            return null;
        }

        $params = [];

        foreach ($parts as $part) {
            $equalPosition = strpos(
                $part,
                '='
            );

            if ($equalPosition === false) {
                continue;
            }

            $paramName = strtoupper(
                trim(
                    substr(
                        $part,
                        0,
                        $equalPosition
                    )
                )
            );

            $paramValue = trim(
                substr(
                    $part,
                    $equalPosition + 1
                ),
                "\"' "
            );

            if ($paramName !== '') {
                $params[$paramName] = $paramValue;
            }
        }

        return [
            'name' => $name,
            'value' => trim(
                $value
            ),
            'params' => $params,
        ];
    }

    /**
     * @param array<string, array<int, array{
     *     name: string,
     *     value: string,
     *     params: array<string, string>
     * }>> $properties
     */
    private static function firstValue(
        array $properties,
        string $name
    ): string {
        $property = self::firstProperty(
            $properties,
            $name
        );

        if ($property === null) {
            return '';
        }

        return $property['value'];
    }

    /**
     * @param array<string, array<int, array{
     *     name: string,
     *     value: string,
     *     params: array<string, string>
     * }>> $properties
     * @return array{
     *     name: string,
     *     value: string,
     *     params: array<string, string>
     * }|null
     */
    private static function firstProperty(
        array $properties,
        string $name
    ): ?array {
        $name = strtoupper(
            $name
        );

        if (
            !isset($properties[$name])
            || $properties[$name] === []
        ) {
            return null;
        }

        $property = $properties[$name][0];

        return is_array($property)
            ? $property
            : null;
    }

    private static function dateFromIcalValue(
        string $value
    ): ?string {
        $value = trim(
            $value
        );

        if (
            preg_match(
                '/^(\d{4})(\d{2})(\d{2})/',
                $value,
                $matches
            ) !== 1
        ) {
            return null;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        if (
            !checkdate(
                $month,
                $day,
                $year
            )
        ) {
            return null;
        }

        return sprintf(
            '%04d-%02d-%02d',
            $year,
            $month,
            $day
        );
    }

    /**
     * @param array{
     *     name: string,
     *     value: string,
     *     params: array<string, string>
     * }|null $property
     */
    private static function isAllDayProperty(
        ?array $property,
        string $rawValue
    ): bool {
        if ($property !== null) {
            $valueType = strtoupper(
                $property['params']['VALUE']
                ?? ''
            );

            if ($valueType === 'DATE') {
                return true;
            }
        }

        return preg_match(
            '/^\d{8}$/',
            trim(
                $rawValue
            )
        ) === 1;
    }

    private static function decodeText(
        string $value
    ): string {
        return str_replace(
            [
                '\\n',
                '\\N',
                '\\,',
                '\\;',
                '\\\\',
            ],
            [
                "\n",
                "\n",
                ',',
                ';',
                '\\',
            ],
            $value
        );
    }
}