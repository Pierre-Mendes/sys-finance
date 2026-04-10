<?php

namespace App\Services;

use App\Contracts\IGoalRepository;
use App\Repositories\GoalContributionRepository;
use App\Models\Goal;
use App\Models\GoalContribution;
use App\DTO\GoalDTO;
use App\DTO\GoalContributionDTO;
use Exception;

class GoalService {
    private IGoalRepository $goalRepo;
    private GoalContributionRepository $contributionRepo;
    private SimulationService $simulationService;
    private \App\Repositories\BillRepository $billRepo;
    private \App\Repositories\CategoryRepository $categoryRepo;

    public function __construct(
        IGoalRepository $goalRepo, 
        GoalContributionRepository $contributionRepo, 
        SimulationService $simulationService,
        \App\Repositories\BillRepository $billRepo,
        \App\Repositories\CategoryRepository $categoryRepo
    ) {
        $this->goalRepo = $goalRepo;
        $this->contributionRepo = $contributionRepo;
        $this->simulationService = $simulationService;
        $this->billRepo = $billRepo;
        $this->categoryRepo = $categoryRepo;
    }

    public function getAllGoals(int $workspaceId): array {
        return $this->goalRepo->findAllByWorkspaceId($workspaceId);
    }

    public function createGoal(int $workspaceId, GoalDTO $dto): Goal {
        $goal = new Goal(
            $workspaceId,
            $dto->title,
            $dto->targetAmount,
            0.0,
            $dto->targetDate,
            $dto->sharedWithWorkspaceId,
            null,
            $dto->accountId
        );
        return $this->goalRepo->save($goal);
    }

    public function updateGoal(int $id, int $workspaceId, GoalDTO $dto): Goal {
        $existing = $this->goalRepo->findByIdAndWorkspaceId($id, $workspaceId);
        if (!$existing) throw new Exception("Goal not found.");

        $goal = new Goal(
            $workspaceId,
            $dto->title,
            $dto->targetAmount,
            $existing->getAccumulatedAmount(),
            $dto->targetDate,
            $dto->sharedWithWorkspaceId,
            $id,
            $dto->accountId,
            $dto->isFavorite
        );
        return $this->goalRepo->save($goal);
    }

    public function deleteGoal(int $id, int $workspaceId): void {
        $success = $this->goalRepo->delete($id, $workspaceId);
        if (!$success) throw new Exception("Goal not found or could not be deleted.");
    }

    public function addContribution(int $workspaceId, GoalContributionDTO $dto): GoalContribution {
        $goal = $this->goalRepo->findByIdAndWorkspaceId($dto->goalId, $workspaceId);
        if (!$goal) throw new Exception("Goal not found.");

        $contribution = new GoalContribution(
            $dto->goalId,
            $workspaceId,
            $dto->amount,
            $dto->date,
            $dto->description,
            null,
            $dto->accountId
        );
        
        $saved = $this->contributionRepo->save($contribution);

        // Logic: Use provided accountId or fallback to goal's default
        $targetAccountId = $dto->accountId ?: $goal->getAccountId();

        if ($targetAccountId) {
            // Find or create "Aporte em Metas" category (level 2 = expense)
            $catName = "Aporte em Metas";
            $cats = $this->categoryRepo->findAllByWorkspaceIdAndLevel($workspaceId, 2);
            $foundCat = null;
            foreach ($cats as $c) {
                if (strcasecmp($c->getCategoryName(), $catName) === 0) {
                    $foundCat = $c;
                    break;
                }
            }
            if (!$foundCat) {
                $newCat = new \App\Models\Category($workspaceId, $catName, 2);
                $foundCat = $this->categoryRepo->save($newCat);
            }

            $bill = new \App\Models\Transaction(
                $workspaceId,
                'bill',
                "Meta: " . $goal->getTitle(),
                $dto->date ?: date('Y-m-d'),
                $foundCat->getId(),
                $targetAccountId,
                $dto->amount,
                $dto->description ?: "Aporte automático via módulo de metas.",
                null,
                null,
                $dto->date ?: date('Y-m-d'),
                'PAID', // Contribution is immediate
                'MEDIUM',
                'NONE'
            );
            $this->billRepo->save($bill);
        }

        return $saved;
    }

    public function calculateForecast(int $goalId, int $workspaceId): array {
        return $this->simulationService->estimateCompletion($goalId, $workspaceId);
    }
}
