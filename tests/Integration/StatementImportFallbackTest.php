<?php

namespace Tests\Integration;

use App\Services\StatementImportService;
use Tests\TestCase;

class StatementImportFallbackTest extends TestCase
{
    public function test_unknown_layout_falls_back_to_hybrid_engine_without_fatal_error(): void
    {
        // Layout que nenhum adapter fixo reconhece: cai no HybridAIEngine, que não pode dar erro fatal.
        $text = "MEU BANCO DIGITAL - EXTRATO\n05/09/2026  PADARIA CENTRAL   -25,50\n06/09/2026  PIX RECEBIDO   1.200,00\n";

        try {
            $rows = (new StatementImportService())->extractFromContent($text, 'txt');
            $this->assertIsArray($rows);
        } catch (\Exception $e) {
            // Sem dados seguros a heurística pode recusar, mas com erro de domínio.
            $this->assertStringContainsString('Heurística', $e->getMessage());
        }
    }
}
