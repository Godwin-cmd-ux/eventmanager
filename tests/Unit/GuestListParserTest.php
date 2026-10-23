<?php

namespace Tests\Unit;

use App\Support\GuestListParser;
use PHPUnit\Framework\TestCase;

class GuestListParserTest extends TestCase
{
    public function test_it_parses_every_marker_style_used_in_the_real_list(): void
    {
        $contents = implode("\n", [
            'Bi. EUGENIA KWEKA. [D]',
            'BI. RUTH MZAVA [ D]',
            'EMANUEL KINYAHA [ S]',
            'MR&MRS. NAHMANI KOKO'."\t".'D',
            'Br. Anthony Kitale S',
            'Ally Hamza Minja Melio  S',
            'Mr. Safiniely Mfinanga  S',
            'MCH.  SELINA JACOB'."\t".'S',
        ]);

        $result = (new GuestListParser)->parse($contents);

        $this->assertSame([], $result['errors']);
        $this->assertSame([
            ['Bi. EUGENIA KWEKA', 'double'],
            ['BI. RUTH MZAVA', 'double'],
            ['EMANUEL KINYAHA', 'single'],
            ['MR&MRS. NAHMANI KOKO', 'double'],
            ['Br. Anthony Kitale', 'single'],
            ['Ally Hamza Minja Melio', 'single'],
            ['Mr. Safiniely Mfinanga', 'single'],
            ['MCH. SELINA JACOB', 'single'],
        ], array_map(
            fn (array $g) => [$g['name'], $g['type']],
            $result['guests'],
        ));
    }

    public function test_it_records_line_numbers_and_skips_blank_lines(): void
    {
        $contents = "ALPHA [S]\n\n\nBETA [D]\n";

        $result = (new GuestListParser)->parse($contents);

        $this->assertSame([1, 4], array_column($result['guests'], 'line'));
    }

    public function test_it_reports_lines_without_a_marker(): void
    {
        $result = (new GuestListParser)->parse("NO MARKER HERE\nGAMMA [S]");

        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Line 1', $result['errors'][0]);
        $this->assertSame('GAMMA', $result['guests'][0]['name']);
    }

    public function test_it_parses_the_shipped_names_file(): void
    {
        // The card generator (and its names.txt) now lives outside the repo,
        // so the shipped list is kept here as the parser's reference fixture.
        $path = dirname(__DIR__).'/Fixtures/names.txt';

        $this->assertFileExists($path);

        $result = (new GuestListParser)->parse(file_get_contents($path));

        $this->assertSame([], $result['errors']);
        $this->assertCount(69, $result['guests']);

        $types = array_count_values(array_column($result['guests'], 'type'));
        $this->assertSame(39, $types['single']);
        $this->assertSame(30, $types['double']);
    }
}
