<?php

namespace App\Services;

use App\Repositories\AssetRepository;
use App\Repositories\BillRepository;
use App\Contracts\IGoalRepository;
use App\Models\Goal;

class SimulationService {
    private AssetRepository $assetRepo;
    private BillRepository $billRepo;
    private IGoalRepository $goalRepo;

    public function __construct(AssetRepository $assetRepo, BillRepository $billRepo, IGoalRepository $goalRepo) {
        $this->assetRepo = $assetRepo;
        $this->billRepo = $billRepo;
        $this->goalRepo = $goalRepo;
    }

    /**
     * Calculates the estimated date to achieve a goal based on average monthly surplus.
     */
    public function estimateCompletion(int $goalId, int $workspaceId): array {
        $goal = $this->goalRepo->findByIdAndWorkspaceId($goalId, $workspaceId);
        if (!$goal) return ['error' => 'Goal not found'];

        $surplus = $this->calculateMonthlySurplus($workspaceId);
        
        $remaining = $goal->getTargetAmount() - $goal->getAccumulatedAmount();
        if ($remaining <= 0) return ['months' => 0, 'date' => date('Y-m-d')];

        if ($surplus <= 0) {
            return [
                'months' => null,
                'message' => 'Based on your current budget, you have no surplus to save towards this goal.'
            ];
        }

        $months = ceil($remaining / $surplus);
        $completionDate = date('Y-m-d', strtotime("+$months months"));

        return [
            'months' => (int) $months,
            'date' => $completionDate,
            'monthlySurplus' => $surplus,
            'remainingAmount' => $remaining
        ];
    }

    private function calculateMonthlySurplus(int $workspaceId): float {
        // This is a simplified logic. In a real scenario, we'd average the last 3-6 months.
        // For now, let's take all active income and subtract all active (unpaid) bills for the current month.
        
        $incomes = $this->assetRepo->findAllByWorkspaceId($workspaceId);
        $totalIncome = 0;
        foreach($incomes as $inc) {
            // Assuming we only count monthly recurring or current month incomes
            $totalIncome += $inc->getAmount();
        }

        $bills = $this->billRepo->findAllByWorkspaceId($workspaceId);
        $totalExpenses = 0;
        foreach($bills as $bill) {
            // Assuming we count all bills due this month
            // Simplified: just sum all bills for the sake of demo
            $totalExpenses += $bill->getAmount();
        }

        return (float) ($totalIncome - $totalExpenses);
    }
}
