<?php

namespace App\Services;

use App\Strategies\IQuoteStrategy;

class InvestmentQuoteService
{
    /** @var IQuoteStrategy[] */
    private array $strategies;

    public function __construct(array $strategies = [])
    {
        $this->strategies = $strategies;
    }

    public function addStrategy(IQuoteStrategy $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    /**
     * Fetch the current quote for a given ticker or comma-separated list of tickers.
     * Groups tickers by strategy and fetches them, combining the results.
     */
    public function getQuotes(array $tickers): array
    {
        if (empty($tickers)) {
            return [];
        }

        $tickersByStrategy = [];
        $unhandledTickers = [];

        foreach ($tickers as $ticker) {
            $handled = false;
            foreach ($this->strategies as $strategy) {
                if ($strategy->supports($ticker)) {
                    $hash = spl_object_hash($strategy);
                    $tickersByStrategy[$hash]['strategy'] = $strategy;
                    $tickersByStrategy[$hash]['tickers'][] = $ticker;
                    $handled = true;
                    break;
                }
            }
            if (!$handled) {
                $unhandledTickers[] = $ticker;
            }
        }

        $results = [];
        foreach ($tickersByStrategy as $group) {
            /** @var IQuoteStrategy $strategy */
            $strategy = $group['strategy'];
            $quotes = $strategy->getQuotes($group['tickers']);
            $results = array_merge($results, $quotes);
        }

        return $results;
    }
}
