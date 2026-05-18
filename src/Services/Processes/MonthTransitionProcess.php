<?php

namespace App\Services\Processes;

use App\Database;
use App\Domain\Enums\TransactionStatus;
use App\Domain\Enums\RecurrenceType;
use App\Repositories\BillRepository;
use App\Models\Transaction;
use PDO;

class MonthTransitionProcess {
    private PDO $db;
    private BillRepository $billRepository;

    public function __construct(BillRepository $billRepository) {
        $this->db = Database::getConnection();
        $this->billRepository = $billRepository;
    }

    /**
     * Projeta o novo mês baseado em gastos recorrentes e pendências do mês anterior.
     */
    public function execute(int $workspaceId): array {
        $results = [
            'recurring_created' => 0,
            'pending_from_past' => 0,
            'total_projected' => 0.0
        ];

        $this->db->beginTransaction();
        try {
            // 1. Geração de Recorrência
            $results['recurring_created'] = $this->generateRecurringBills($workspaceId);
            
            // 2. Verificação de Pendências
            $results['pending_from_past'] = $this->countPendingFromPast($workspaceId);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $results;
    }

    private function generateRecurringBills(int $workspaceId): int {
        // Encontra contas recorrentes do mês ATUAL ou ANTERIOR que precisam ser clonadas para o novo mês
        // Para simplificar, buscamos contas marcadas como recorrentes que ainda não foram projetadas para o mês atual
        
        $currentMonth = date('Y-m');
        $prevMonth = date('Y-m', strtotime('-1 month'));
        
        $stmt = $this->db->prepare("
            SELECT * FROM bills 
            WHERE WorkspaceId = :ws 
            AND recurrence_type != 'NONE'
            AND DATE_FORMAT(Dates, '%Y-%m') = :prev
            AND Title NOT IN (
                SELECT Title FROM bills 
                WHERE WorkspaceId = :ws2 AND DATE_FORMAT(Dates, '%Y-%m') = :curr
            )
        ");
        
        $stmt->execute(['ws' => $workspaceId, 'ws2' => $workspaceId, 'prev' => $prevMonth, 'curr' => $currentMonth]);
        $toClone = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $count = 0;
        foreach ($toClone as $row) {
            $newDate = date('Y-m-d', strtotime($row['Dates'] . ' +1 month'));
            
            $newBill = new Transaction(
                $workspaceId, 'bill', $row['Title'], $newDate,
                $row['CategoryId'], $row['AccountId'], (float)$row['Amount'], 
                "[Projeção Automática] " . $row['Description'],
                null, $row['BillsId'], $newDate, TransactionStatus::PENDING->value,
                $row['priority'], $row['recurrence_type']
            );
            
            $this->billRepository->save($newBill);
            $count++;
        }
        
        return $count;
    }

    private function countPendingFromPast(int $workspaceId): int {
        $prevMonthLimit = date('Y-m-01');
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM bills 
            WHERE WorkspaceId = ? AND status = 'PENDING' AND Dates < ?
        ");
        $stmt->execute([$workspaceId, $prevMonthLimit]);
        return (int) $stmt->fetchColumn();
    }
}
