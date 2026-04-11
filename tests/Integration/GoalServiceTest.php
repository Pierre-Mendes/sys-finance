<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Services\GoalService;
use App\Services\SimulationService;
use App\Repositories\GoalRepository;
use App\Repositories\GoalContributionRepository;
use App\Models\Goal;
use App\DTO\GoalDTO;
use App\DTO\GoalContributionDTO;
use Exception;

class GoalServiceTest extends TestCase
{
    private GoalService $goalService;
    private int $workspaceId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        
        $goalRepo = new GoalRepository($this->db);
        $contributionRepo = new GoalContributionRepository($this->db);
        $assetRepo = new \App\Repositories\AssetRepository($this->db);
        $billRepo = new \App\Repositories\BillRepository($this->db);
        $categoryRepo = new \App\Repositories\CategoryRepository($this->db);
        $simulationService = new SimulationService($assetRepo, $billRepo, $goalRepo);
        
        $this->goalService = new GoalService($goalRepo, $contributionRepo, $simulationService, $billRepo, $categoryRepo);
    }

    public function test_create_and_retrieve_goal(): void
    {
        $dto = new GoalDTO([
            'title' => 'New Car',
            'targetAmount' => 50000.0,
            'targetDate' => '2026-12-31'
        ]);

        $goal = $this->goalService->createGoal($this->workspaceId, $dto);

        $this->assertNotNull($goal->getId());
        $this->assertEquals("New Car", $goal->getTitle());

        $allGoals = $this->goalService->getAllGoals($this->workspaceId);
        $this->assertCount(1, $allGoals);
    }

    public function test_add_contribution_updates_goal_state(): void
    {
        // 1. Create Goal
        $dto = new GoalDTO(['title' => 'Travel', 'targetAmount' => 10000.0]);
        $goal = $this->goalService->createGoal($this->workspaceId, $dto);

        // 2. Add Contribution
        $contribDto = new GoalContributionDTO([
            'goalId' => $goal->getId(),
            'amount' => 2500.0,
            'date' => '2026-04-10',
            'description' => 'Monthly savings'
        ]);

        $contribution = $this->goalService->addContribution($this->workspaceId, $contribDto);

        $this->assertNotNull($contribution->getId());
        $this->assertEquals(2500.0, $contribution->getAmount());
    }

    public function test_add_contribution_to_invalid_goal_throws_exception(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Goal not found.");

        $contribDto = new GoalContributionDTO([
            'goalId' => 999, // Non-existent
            'amount' => 100.0
        ]);

        $this->goalService->addContribution($this->workspaceId, $contribDto);
    }
}
