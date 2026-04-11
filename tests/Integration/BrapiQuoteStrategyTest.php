<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Strategies\BrapiQuoteStrategy;
use VCR\VCR;

class BrapiQuoteStrategyTest extends TestCase
{
    private BrapiQuoteStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new BrapiQuoteStrategy();
    }

    public function test_get_quotes_with_vcr_recording(): void
    {
        // Use a cassette to record or replay the request
        VCR::insertCassette('brapi_quotes.yml');

        $tickers = ['PETR4', 'VALE3'];
        $results = $this->strategy->getQuotes($tickers);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('PETR4', $results);
        $this->assertArrayHasKey('VALE3', $results);
        $this->assertIsFloat($results['PETR4']);

        VCR::eject();
    }

    public function test_get_quotes_handles_empty_tickers(): void
    {
        $results = $this->strategy->getQuotes([]);
        $this->assertEquals([], $results);
    }
}
