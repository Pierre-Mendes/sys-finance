<?php

namespace App\Controllers;

use App\Services\GoalService;
use App\DTO\GoalDTO;
use App\DTO\GoalContributionDTO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GoalController {
    private GoalService $goalService;

    public function __construct(GoalService $goalService) {
        $this->goalService = $goalService;
    }

    public function list(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $goals = $this->goalService->getAllGoals($workspaceId);
        
        $response->getBody()->write(json_encode(['success' => true, 'data' => $goals]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $body = $request->getParsedBody();
        
        $dto = new GoalDTO($body);
        $goal = $this->goalService->createGoal($workspaceId, $dto);
        
        $response->getBody()->write(json_encode(['success' => true, 'data' => $goal]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int) $request->getAttribute('id');
        $body = $request->getParsedBody();
        
        $dto = new GoalDTO($body);
        try {
            $goal = $this->goalService->updateGoal($id, $workspaceId, $dto);
            $response->getBody()->write(json_encode(['success' => true, 'data' => $goal]));
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => \App\Security\PublicError::message($e)]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int) $request->getAttribute('id');
        
        try {
            $this->goalService->deleteGoal($id, $workspaceId);
            $response->getBody()->write(json_encode(['success' => true]));
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => \App\Security\PublicError::message($e)]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function contribute(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $body = $request->getParsedBody();
        
        $dto = new GoalContributionDTO($body);
        try {
            $contribution = $this->goalService->addContribution($workspaceId, $dto);
            $response->getBody()->write(json_encode(['success' => true, 'data' => $contribution]));
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => \App\Security\PublicError::message($e)]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function forecast(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $goalId = (int) $request->getAttribute('id');
        
        try {
            $forecast = $this->goalService->calculateForecast($goalId, $workspaceId);
            $response->getBody()->write(json_encode(['success' => true, 'data' => $forecast]));
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => \App\Security\PublicError::message($e)]));
            return $response->withStatus(400);
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}
