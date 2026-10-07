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

    public function test_pdf_is_converted_to_text_via_stdin_without_temp_file(): void
    {
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml('<p>Extrato Padaria Central 25,50</p>');
        $dompdf->render();

        $text = (new StatementImportService())->pdfToText($dompdf->output());

        $this->assertStringContainsString('Padaria Central', $text);
    }

    public function test_invalid_pdf_throws_friendly_error(): void
    {
        $this->expectExceptionMessage('Não foi possível ler o PDF');

        (new StatementImportService())->pdfToText('isto não é um pdf');
    }

    public function test_csv_content_is_parsed_in_memory(): void
    {
        $rows = (new StatementImportService())->extractFromContent(
            "Data;Descricao;Valor\n10/09/2026;Padaria Central;-25,50\n",
            'csv'
        );

        $this->assertCount(1, $rows);
        $this->assertSame('Padaria Central', $rows[0]['description']);
    }
}
