<?php

namespace Tests\Unit;

use App\Controllers\ReportController;
use PHPUnit\Framework\TestCase;

class ReportControllerTest extends TestCase
{
    /** @dataProvider formulaPayloads */
    public function test_csv_cells_starting_with_formula_chars_are_neutralized(string $payload): void
    {
        $this->assertSame("'" . $payload, ReportController::csvCell($payload));
    }

    public static function formulaPayloads(): array
    {
        return [['=HYPERLINK("http://evil","x")'], ['+1+1'], ['-2+3'], ['@SUM(A1)'], ["\tcmd"]];
    }

    public function test_regular_and_encoded_values_are_kept_readable(): void
    {
        $this->assertSame('Mercado', ReportController::csvCell('Mercado'));
        $this->assertSame('Pão & Leite', ReportController::csvCell('Pão &amp; Leite'));
    }
}
