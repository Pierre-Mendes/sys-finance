<?php

namespace App\Telegram;

/**
 * Lê um lançamento escrito em português livre, como se manda numa conversa:
 *   "mercado 120 nubank", "gastei 35,90 no almoço ontem", "recebi 3.000 de salário", "+50 pix do joão 05/10".
 *
 * Só interpreta valor, tipo (receita/despesa), data e o texto restante; conta e categoria são casadas
 * depois com os cadastros do espaço (TelegramBot), porque dependem do workspace.
 */
final class MessageParser {
    /** Palavras que indicam receita (o padrão é despesa). */
    private const INCOME_WORDS = [
        'recebi', 'receber', 'recebido', 'recebimento', 'receita', 'ganhei', 'ganho', 'salario', 'entrada', 'renda',
        'rendimento', 'reembolso', 'reembolsado', 'vendi', 'venda', 'freela', 'freelance', 'bonus', 'dividendos',
        'cashback', 'estorno', 'devolucao', 'pagaram', 'deposito',
    ];

    /** Removidas do título em qualquer posição. */
    private const NOISE_WORDS = [
        'reais', 'real', 'r$', 'rs', 'hoje', 'ontem', 'anteontem', 'gastei', 'paguei', 'comprei', 'recebi', 'ganhei',
    ];

    /** Palavras de ligação removidas das pontas do título. */
    private const FILLER_WORDS = [
        'gastei', 'gasto', 'gastos', 'paguei', 'pagar', 'pago', 'comprei', 'compra', 'despesa', 'debito', 'recebi', 'ganhei',
        'no', 'na', 'nos', 'nas', 'em', 'de', 'do', 'da', 'dos', 'das', 'com', 'pelo', 'pela', 'para', 'pra', 'pro',
        'o', 'a', 'os', 'as', 'um', 'uma', 'reais', 'real', 'r$', 'rs', 'hoje', 'ontem', 'anteontem', 'valor',
    ];

    /**
     * @return array{type: string, amount: float, date: string, text: string}|null null quando não há valor
     */
    public static function parse(string $message, \DateTimeImmutable $today, ?string $forcedType = null): ?array {
        $text = trim(preg_replace('/\s+/u', ' ', $message));
        if ($text === '') return null;

        $date = $today;
        // Datas por extenso ou dd/mm[/aaaa] (tiradas antes do valor para "05/10" não virar 5,10).
        if (preg_match('/(?<![\d\/])(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?(?![\d\/])/u', $text, $m)) {
            $year = isset($m[3]) ? (int) (strlen($m[3]) === 2 ? '20' . $m[3] : $m[3]) : (int) $today->format('Y');
            if (checkdate((int) $m[2], (int) $m[1], $year)) {
                $date = $today->setDate($year, (int) $m[2], (int) $m[1]);
                // "28/12" mandado em janeiro é do ano passado.
                if (!isset($m[3]) && $date > $today->modify('+1 day')) $date = $date->modify('-1 year');
                $text = trim(str_replace($m[0], ' ', $text));
            }
        }
        $normalized = self::normalize($text);
        if (preg_match('/\banteontem\b/u', $normalized)) $date = $today->modify('-2 days');
        elseif (preg_match('/\bontem\b/u', $normalized)) $date = $today->modify('-1 day');

        // Valor: 120 | 120,50 | 1.200,50 | 1200.50 | R$ 35 | +50. Com vários números ("uber 99 25") vale o que tem
        // R$ ou centavos; sem isso, o último (o valor costuma vir depois do nome: "uber 99 25", "netflix 55").
        preg_match_all('/(?<![\pL\d.,\/])([+-])?(r\$\s*)?(\d{1,3}(?:\.\d{3})+(?:,\d{1,2})?|\d+(?:[.,]\d{1,2})?)(?![\pL\d\/])/iu', $text, $all, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        if (!$all) return null;
        $m = end($all);
        foreach ($all as $candidate) {
            if (($candidate[2][0] ?? '') !== '' || preg_match('/[.,]\d{1,2}$/', $candidate[3][0])) { $m = $candidate; break; }
        }
        $amount = self::toFloat($m[3][0]);
        if ($amount <= 0) return null;
        $sign = $m[1][0] ?? '';
        $text = trim(substr($text, 0, $m[0][1]) . ' ' . substr($text, $m[0][1] + strlen($m[0][0])));

        $words = preg_split('/\s+/u', self::normalize($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $type = $forcedType;
        if ($type === null) {
            $type = ($sign === '+' || array_intersect($words, self::INCOME_WORDS)) ? 'asset' : 'bill';
        }

        return [
            'type' => $type,
            'amount' => round($amount, 2),
            'date' => $date->format('Y-m-d'),
            'text' => self::cleanTitle($text),
        ];
    }

    /** Minúsculas e sem acento, para comparar palavras ("Salário" == "salario"). */
    public static function normalize(string $text): string {
        $text = mb_strtolower($text, 'UTF-8');
        $map = ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e',
            'í' => 'i', 'î' => 'i', 'ì' => 'i', 'ï' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ò' => 'o', 'ö' => 'o',
            'ú' => 'u', 'û' => 'u', 'ù' => 'u', 'ü' => 'u', 'ç' => 'c'];
        return strtr($text, $map);
    }

    /** Tira do título as palavras de ligação nas pontas e um nome que já virou conta/categoria. */
    public static function cleanTitle(string $text, array $remove = []): string {
        foreach ($remove as $phrase) {
            if ($phrase === '') continue;
            $text = preg_replace('/(?<!\pL)' . preg_quote($phrase, '/') . '(?!\pL)/iu', ' ', $text) ?? $text;
        }
        $words = preg_split('/\s+/u', trim(preg_replace('/[^\pL\pN\s\-\'&\/.]/u', ' ', $text) ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        // Palavras que nunca fazem parte do nome ("reais", "ontem", "gastei") saem de qualquer posição.
        $words = array_values(array_filter($words, fn ($w) => preg_match('/[\pL\pN]/u', $w)
            && !in_array(self::normalize($w), self::NOISE_WORDS, true)));
        while ($words && in_array(self::normalize($words[0]), self::FILLER_WORDS, true)) array_shift($words);
        while ($words && in_array(self::normalize(end($words)), self::FILLER_WORDS, true)) array_pop($words);
        $title = implode(' ', $words);
        return $title === '' ? '' : mb_strtoupper(mb_substr($title, 0, 1)) . mb_substr($title, 1);
    }

    private static function toFloat(string $raw): float {
        if (str_contains($raw, ',')) {
            return (float) str_replace(['.', ','], ['', '.'], $raw); // 1.200,50
        }
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $raw)) {
            return (float) str_replace('.', '', $raw); // 1.200
        }
        return (float) $raw; // 120 | 120.50
    }
}
