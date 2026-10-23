<?php

namespace App\Support;

/**
 * Parses the hand-maintained guest list into structured rows.
 *
 * The source file is written by hand, so the trailing invitation marker is
 * inconsistent: it may be `[D]`, `[ D]`, `[S]`, `(D)`, `(S`, a tab followed
 * by `D`, or a plain space followed by `S`, sometimes with a trailing period.
 * A single guest per non-empty line is expected.
 *
 * Marker semantics: D = double (2 scans), S = single (1 scan).
 */
class GuestListParser
{
    /**
     * @return array{
     *     guests: list<array{name: string, type: string, line: int, raw: string}>,
     *     errors: list<string>
     * }
     */
    public function parse(string $contents): array
    {
        $guests = [];
        $errors = [];

        $lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];

        foreach ($lines as $index => $raw) {
            $lineNo = $index + 1;
            // Strip a UTF-8 BOM (only ever present on the first line) before
            // trimming; never pass \x{FEFF} to trim(), which would be read as
            // the literal characters {, F, E and } and corrupt names.
            $line = trim(str_replace("\xEF\xBB\xBF", '', $raw));

            if ($line === '') {
                continue;
            }

            // Drop a hand-written list number ("1. ", "2) "), if present.
            $line = trim(preg_replace('/^\d+[.)]\s+/u', '', $line) ?? $line);

            // name ... (optional bracket/paren) + D|S + (optional close + period)
            if (! preg_match('/^(?<name>.+?)(?:\s*[\[(]\s*|\s+)(?<type>[DS])\s*[\])]?\s*\.?\s*$/iu', $line, $m)) {
                $errors[] = sprintf('Line %d: no single/double marker found in "%s".', $lineNo, $line);

                continue;
            }

            $name = trim(preg_replace('/\s+/u', ' ', $m['name']) ?? $m['name']);
            $name = rtrim($name, " .\t([");

            if ($name === '') {
                $errors[] = sprintf('Line %d: a marker was found but the name is empty.', $lineNo);

                continue;
            }

            $type = strtolower($m['type']) === 'd' ? 'double' : 'single';

            $guests[] = [
                'name' => $name,
                'type' => $type,
                'line' => $lineNo,
                'raw' => trim($raw),
            ];
        }

        return ['guests' => $guests, 'errors' => $errors];
    }
}
