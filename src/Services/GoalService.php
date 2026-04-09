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

    public function __construct(IGoalRepository $goalRepo, GoalContributionRepository $contributionRepo, SimulationService $simulationService) {
        $this->goalRepo = $goalRepo;
        $this->contributionRepo = $contributionRepo;
        $this->simulationService = $simulationService;
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
            $dto->sharedWithWorkspaceId
        );
        return $this->goalRepo->save($goal);
    }

    public function addContribution(int $workspaceId, GoalContributionDTO $dto): GoalContribution {
        $goal = $this->goalRepo->findByIdAndWorkspaceId($dto->goalId, $workspaceId);
        if (!$goal) throw new Exception("Goal not found.");

        $contribution = new GoalContribution(
            $dto->goalId,
            $workspaceId,
            $dto->amount,
            $dto->date,
            $dto->description
        );
        
        return $this->contributionRepo->save($contribution);
    }

    public function calculateForecast(int $goalId, int $workspaceId): array {
        return $this->simulationService->estimateCompletion($goalId, $workspaceId);
    }
}
