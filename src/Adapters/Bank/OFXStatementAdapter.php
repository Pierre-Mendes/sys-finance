<?php

namespace App\Adapters\Bank;

use App\Domain\Interfaces\BankStatementAdapterInterface;

class OFXStatementAdapter implements BankStatementAdapterInterface {
    
    public static function matches(string $text): bool {
        return stripos($text, '<OFX>') !== false || stripos($text, 'OFXHEADER') !== false;
    }

    public function parse(string $text): array {
        $transactions = [];
        
        // Basic OFX transaction extraction
        // Look for <STMTTRN> blocks
        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/is', $text, $matches);

        foreach ($matches[1] as $block) {
            $date = $this->getTagValue($block, 'DTPOSTED');
            $amount = (float) $this->getTagValue($block, 'TRNAMT');
            $memo = $this->getTagValue($block, 'MEMO');
            $name = $this->getTagValue($block, 'NAME');

            if ($date && $amount !== null) {
                $transactions[] = [
                    'date' => \DateTime::createFromFormat('Ymd', substr($date, 0, 8))->format('Y-m-d'),
                    'description' => trim($name . ' ' . $memo),
                    'amount' => abs($amount),
                    'type' => $amount < 0 ? 'bill' : 'asset'
                ];
            }
        }

        return $transactions;
    }

    private function getTagValue(string $block, string $tag): ?string {
        // Handle both <TAG>VALUE and <TAG>VALUE</TAG> styles
        if (preg_match("/<$tag>(.*?)(?:<\/$tag>|\r|\n)/i", $block, $match)) {
            return trim($match[1]);
        }
        return null;
    }
}
