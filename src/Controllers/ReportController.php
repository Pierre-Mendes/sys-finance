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
            if ($tx->getType() === 'income') {
                $totalIncome += $tx->getAmount();
                $color = 'green';
            } else {
                $totalExpense += $tx->getAmount();
                $color = 'red';
            }

            $dateFormated = date('d/m/Y', strtotime($tx->getDate()));
            $amountFormated = number_format($tx->getAmount(), 2, ',', '.');
            
            $html .= "<tr>
                        <td>{$dateFormated}</td>
                        <td>{$tx->getTitle()}</td>
                        <td>{$tx->getCategoryName()}</td>
                        <td>{$tx->getAccountName()}</td>
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
                $tx->getType() === 'income' ? 'Receita' : 'Despesa',
                $tx->getTitle(),
                $tx->getCategoryName(),
                $tx->getAccountName(),
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
}
