<?php

namespace App\Controllers;

use App\Services\TransactionService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReportController {
    private TransactionService $txService;

    public function __construct(TransactionService $txService) {
        $this->txService = $txService;
    }

    public function generatePdf(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $params = $request->getQueryParams();
        
        $filters = [
            'from_date' => $params['from_date'] ?? null,
            'to_date' => $params['to_date'] ?? null,
            'account_id' => $params['account_id'] ?? null,
            'category_id' => $params['category_id'] ?? null,
            'type' => $params['type'] ?? null
        ];

        $transactions = $this->txService->getFilteredForUser($workspaceId, $filters);
        
        // Dompdf setup
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        // Nada de recursos remotos/arquivos locais, PHP ou JS embutidos no PDF (SSRF / LFI / XSS no documento).
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('chroot', [realpath(__DIR__ . '/../../public') ?: __DIR__]);
        $dompdf = new Dompdf($options);

        // Build HTML Report
        $html = '<h1 style="text-align:center; font-family:Helvetica;">Relatório de Transações</h1>';
        $html .= '<p style="text-align:center; font-family:Helvetica;">Gerenciador Financeiro Pessoal</p>';
        $html .= '<table border="1" cellpadding="8" cellspacing="0" style="width:100%; font-family:Helvetica; font-size: 12px; border-collapse: collapse;">';
        $html .= '<thead style="background-color: #f3f4f6;"><tr>
                    <th align="left">Data</th>
                    <th align="left">Título</th>
                    <th align="left">Categoria</th>
                    <th align="left">Conta</th>
                    <th align="right">Valor (R$)</th>
                  </tr></thead><tbody>';

        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($transactions as $tx) {
            if (in_array($tx->getType(), ['asset', 'income'], true)) {
                $totalIncome += $tx->getAmount();
                $color = 'green';
            } else {
                $totalExpense += $tx->getAmount();
                $color = 'red';
            }

            $dateFormated = date('d/m/Y', strtotime($tx->getDate()));
            $amountFormated = number_format($tx->getAmount(), 2, ',', '.');
            
            $title = self::html($tx->getTitle());
            $category = self::html($tx->getCategoryName());
            $account = self::html($tx->getAccountName());

            $html .= "<tr>
                        <td>{$dateFormated}</td>
                        <td>{$title}</td>
                        <td>{$category}</td>
                        <td>{$account}</td>
                        <td align='right' style='color:{$color};'>{$amountFormated}</td>
                      </tr>";
        }

        $balance = $totalIncome - $totalExpense;
        $balColor = $balance >= 0 ? 'green' : 'red';
        $balFormated = number_format($balance, 2, ',', '.');

        $html .= '</tbody></table>';
        $html .= "<br><h3 style=\"text-align:right; font-family:Helvetica;\">Saldo Final do Relatório: <span style=\"color:{$balColor};\">R$ {$balFormated}</span></h3>";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfOutput = $dompdf->output();

        $response->getBody()->write($pdfOutput);
        return $response->withHeader('Content-Type', 'application/pdf')
                        ->withHeader('Content-Disposition', 'attachment; filename="relatorio_transacoes.pdf"')
                        ->withStatus(200);
    }

    public function generateCsv(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $params = $request->getQueryParams();
        
        $filters = [
            'from_date' => $params['from_date'] ?? null,
            'to_date' => $params['to_date'] ?? null,
            'account_id' => $params['account_id'] ?? null,
            'category_id' => $params['category_id'] ?? null,
            'type' => $params['type'] ?? null
        ];

        $transactions = $this->txService->getFilteredForUser($workspaceId, $filters);

        $out = fopen('php://temp', 'w');
        fputcsv($out, ['Data', 'Tipo', 'Titulo', 'Categoria', 'Conta', 'Valor (R$)']);

        foreach ($transactions as $tx) {
            fputcsv($out, [
                date('d/m/Y', strtotime($tx->getDate())),
                in_array($tx->getType(), ['asset', 'income'], true) ? 'Receita' : 'Despesa',
                self::csvCell($tx->getTitle()),
                self::csvCell($tx->getCategoryName()),
                self::csvCell($tx->getAccountName()),
                number_format($tx->getAmount(), 2, ',', '')
            ]);
        }

        rewind($out);
        $csvOutput = stream_get_contents($out);
        fclose($out);

        $response->getBody()->write($csvOutput);
        return $response->withHeader('Content-Type', 'text/csv')
                        ->withHeader('Content-Disposition', 'attachment; filename="relatorio_transacoes.csv"')
                        ->withStatus(200);
    }

    /**
     * Escapa texto do usuário para HTML (OWASP A03 - XSS/HTML injection no PDF).
     * Alguns campos já chegam com entidades (DTOs antigos usam htmlspecialchars na entrada),
     * então decodificamos antes para não exibir "&amp;amp;".
     */
    private static function html(?string $value): string {
        $plain = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return htmlspecialchars($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Neutraliza CSV/Formula Injection: células iniciadas por = + - @ TAB ou CR viram texto no Excel/Sheets.
     */
    public static function csvCell(?string $value): string {
        $plain = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return preg_match('/^[=+\-@\t\r]/', $plain) ? "'" . $plain : $plain;
    }
}
