<?php

namespace App\Strategies;

class SicoobRDCStrategy implements IQuoteStrategy {

    public function supports(string $ticker): bool {
        return strtoupper($ticker) === 'RDC';
    }

    public function getQuotes(array $tickers): array {
        $result = [];
        foreach ($tickers as $ticker) {
            // Placeholder: Em um cenário real, poderia calcular o RDC diário com base em uma curva CDI.
            // Aqui, retornaremos 1.0 como cota base. O rendimento é calculado no banco.
            $result[$ticker] = 1.0;
        }
        return $result;
    }
}
