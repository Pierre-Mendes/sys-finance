<?php

namespace Tests\Unit;

use App\Telegram\MessageParser;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TelegramMessageParserTest extends TestCase
{
    public static function messages(): array
    {
        return [
            'valor no meio'           => ['mercado 120 nubank', 'bill', 120.0, '2026-10-07', 'Mercado nubank'],
            'centavos com vírgula'    => ['gastei 35,90 no almoço ontem', 'bill', 35.9, '2026-10-06', 'Almoço'],
            'milhar com ponto'        => ['recebi 3.000 de salário', 'asset', 3000.0, '2026-10-07', 'Salário'],
            'sinal de mais = receita' => ['+50 pix do joão 05/10', 'asset', 50.0, '2026-10-05', 'Pix do joão'],
            'último número é o valor' => ['uber 99 25', 'bill', 25.0, '2026-10-07', 'Uber 99'],
            'R$ e milhar com centavos'=> ['R$ 1.200,50 aluguel', 'bill', 1200.5, '2026-10-07', 'Aluguel'],
            'centavos com ponto'      => ['netflix 55.90', 'bill', 55.9, '2026-10-07', 'Netflix'],
            'data de dezembro em out' => ['café 4,5 28/12', 'bill', 4.5, '2025-12-28', 'Café'],
            'ruído removido'          => ['gasolina 200 reais no posto', 'bill', 200.0, '2026-10-07', 'Gasolina no posto'],
            'anteontem'               => ['farmácia 42 anteontem', 'bill', 42.0, '2026-10-05', 'Farmácia'],
        ];
    }

    #[DataProvider('messages')]
    public function test_parses_free_text(string $message, string $type, float $amount, string $date, string $text): void
    {
        $parsed = MessageParser::parse($message, new DateTimeImmutable('2026-10-07'));

        $this->assertSame(['type' => $type, 'amount' => $amount, 'date' => $date, 'text' => $text], $parsed);
    }

    public function test_without_amount_returns_null(): void
    {
        $this->assertNull(MessageParser::parse('oi, tudo bem?', new DateTimeImmutable('2026-10-07')));
        $this->assertNull(MessageParser::parse('mercado 0', new DateTimeImmutable('2026-10-07')));
    }

    public function test_forced_type_wins(): void
    {
        $this->assertSame('asset', MessageParser::parse('pix 30', new DateTimeImmutable('2026-10-07'), 'asset')['type']);
    }

    public function test_invalid_date_is_not_used(): void
    {
        $parsed = MessageParser::parse('lanche 20 31/02', new DateTimeImmutable('2026-10-07'));
        $this->assertSame('2026-10-07', $parsed['date']);
        $this->assertSame(20.0, $parsed['amount'], 'Partes de uma data não podem virar o valor');
    }
}
