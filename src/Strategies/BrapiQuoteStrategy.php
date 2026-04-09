<?php

namespace App\Strategies;

class BrapiQuoteStrategy implements IQuoteStrategy {
    private const BASE_URL = 'https://brapi.dev/api/quote/';

    public function supports(string $ticker): bool {
        // Brapi handles standard B3 symbols (mostly ends with number like PETR4, or typical 4-letter + number)
        // Also BDRs (ending in 34, 39, etc) or FIIs (ending in 11)
        // We assume anything NOT specifically manual is B3.
        return !in_array(strtoupper($ticker), ['RDC', 'CDB', 'TESOURO_DIRETO']);
    }

    public function getQuotes(array $tickers): array {
        if (empty($tickers)) {
            return [];
        }

        $symbols = implode(',', $tickers);
        $url = self::BASE_URL . $symbols;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = [];
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['results']) && is_array($data['results'])) {
                foreach ($data['results'] as $item) {
                    if (isset($item['symbol']) && isset($item['regularMarketPrice'])) {
                        $result[$item['symbol']] = (float)$item['regularMarketPrice'];
                    }
                }
            }
        }

        return $result;
    }
}
