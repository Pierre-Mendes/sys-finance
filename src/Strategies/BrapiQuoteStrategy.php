<?php

namespace App\Strategies;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class BrapiQuoteStrategy implements IQuoteStrategy {
    private const BASE_URL = 'https://brapi.dev/api/quote/';
    private Client $client;

    public function __construct(?Client $client = null) {
        $this->client = $client ?: new Client();
    }

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

        try {
            $response = $this->client->get($url, [
                'timeout' => 10,
                'http_errors' => false
            ]);

            $httpCode = $response->getStatusCode();
            $body = (string)$response->getBody();

            $result = [];
            
            if ($httpCode === 200 && $body) {
                $data = json_decode($body, true);
                if (isset($data['results']) && is_array($data['results'])) {
                    foreach ($data['results'] as $item) {
                        if (isset($item['symbol']) && isset($item['regularMarketPrice'])) {
                            $result[$item['symbol']] = (float)$item['regularMarketPrice'];
                        }
                    }
                }
            }

            return $result;
        } catch (GuzzleException $e) {
            return [];
        }
    }
}
