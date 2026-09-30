<?php

namespace Tests\Unit;

use App\Services\StatementImportService;
use PHPUnit\Framework\TestCase;

class StatementImportServiceTest extends TestCase
{
    public function test_csv_statement_is_parsed_by_csv_adapter(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'stmt_') . '.csv';
        file_put_contents($file, "Data;Descricao;Valor\n10/09/2026;Padaria Central;-25,50\n13/09/2026;Salario;3000,00\n");

        try {
            $rows = (new StatementImportService())->extractFromPdf($file);
        } finally {
            @unlink($file);
        }

        $this->assertCount(2, $rows);
        $this->assertSame('Padaria Central', $rows[0]['description']);
        $this->assertSame('2026-09-10', $rows[0]['date']);
    }
}
