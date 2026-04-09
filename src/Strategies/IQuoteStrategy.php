<?php

namespace App\Strategies;

interface IQuoteStrategy {
    /**
     * Determines if this strategy can handle the given ticker.
     */
    public function supports(string $ticker): bool;

    /**
     * Fetches quotes for an array of tickers.
     */
    public function getQuotes(array $tickers): array;
}
