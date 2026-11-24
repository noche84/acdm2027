<?php

namespace App\Support;

use RuntimeException;

/**
 * Lit les lignes d'un export phpMyAdmin (INSERT INTO `table` (...) VALUES (...), (...);)
 * sans avoir besoin de l'importer dans MySQL.
 */
class SqlDumpReader
{
    /**
     * @return array<int, array<string, string|int|float|null>>
     */
    public static function rows(string $path, string $table): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Fichier introuvable : {$path}");
        }

        $sql = file_get_contents($path);
        if (! mb_check_encoding($sql, 'UTF-8')) {
            $sql = mb_convert_encoding($sql, 'UTF-8', 'Windows-1252');
        }

        $rows = [];
        $pattern = '/INSERT INTO `'.preg_quote($table, '/').'` \(([^)]*)\) VALUES\s*/i';
        $offset = 0;

        while (preg_match($pattern, $sql, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $columns = array_map(fn ($c) => trim($c, " `\n\r\t"), explode(',', $m[1][0]));
            $pos = $m[0][1] + strlen($m[0][0]);
            [$tuples, $pos] = self::parseTuples($sql, $pos);

            foreach ($tuples as $values) {
                if (count($values) !== count($columns)) {
                    throw new RuntimeException('Ligne mal formée près de la position '.$pos);
                }
                $rows[] = array_combine($columns, $values);
            }
            $offset = $pos;
        }

        return $rows;
    }

    /** @return array{0: array<int, array<int, mixed>>, 1: int} */
    private static function parseTuples(string $sql, int $pos): array
    {
        $len = strlen($sql);
        $tuples = [];

        while ($pos < $len) {
            $pos = self::skipSpaces($sql, $pos);
            if ($sql[$pos] !== '(') {
                throw new RuntimeException("« ( » attendu à la position {$pos}");
            }
            $pos++;
            $values = [];

            while (true) {
                $pos = self::skipSpaces($sql, $pos);
                $char = $sql[$pos];

                if ($char === "'") {
                    [$value, $pos] = self::parseString($sql, $pos + 1);
                    $values[] = $value;
                } else {
                    $end = strcspn($sql, ',)', $pos);
                    $raw = trim(substr($sql, $pos, $end));
                    $values[] = strtoupper($raw) === 'NULL' ? null : (is_numeric($raw) ? $raw + 0 : $raw);
                    $pos += $end;
                }

                $pos = self::skipSpaces($sql, $pos);
                if ($sql[$pos] === ',') {
                    $pos++;

                    continue;
                }
                if ($sql[$pos] === ')') {
                    $pos++;
                    break;
                }
                throw new RuntimeException("« , » ou « ) » attendu à la position {$pos}");
            }

            $tuples[] = $values;
            $pos = self::skipSpaces($sql, $pos);

            if ($sql[$pos] === ',') {
                $pos++;

                continue;
            }
            if ($sql[$pos] === ';') {
                return [$tuples, $pos + 1];
            }
            throw new RuntimeException("« , » ou « ; » attendu à la position {$pos}");
        }

        return [$tuples, $pos];
    }

    /** @return array{0: string, 1: int} */
    private static function parseString(string $sql, int $pos): array
    {
        $out = '';
        $len = strlen($sql);

        while ($pos < $len) {
            $char = $sql[$pos];

            if ($char === '\\') {
                $next = $sql[$pos + 1];
                $out .= match ($next) {
                    'n' => "\n", 'r' => "\r", 't' => "\t", '0' => "\0", 'Z' => "\x1A",
                    default => $next,
                };
                $pos += 2;

                continue;
            }

            if ($char === "'") {
                if (($sql[$pos + 1] ?? '') === "'") {
                    $out .= "'";
                    $pos += 2;

                    continue;
                }

                return [$out, $pos + 1];
            }

            $out .= $char;
            $pos++;
        }

        throw new RuntimeException('Chaîne non terminée');
    }

    private static function skipSpaces(string $sql, int $pos): int
    {
        return $pos + strspn($sql, " \n\r\t", $pos);
    }
}
