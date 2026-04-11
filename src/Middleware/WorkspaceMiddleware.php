<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class WorkspaceMiddleware
{
    private PDO $pdo;
    private \App\Services\WorkspaceService $workspaceService;

    public function __construct(PDO $pdo, \App\Services\WorkspaceService $workspaceService)
    {
        $this->pdo = $pdo;
        $this->workspaceService = $workspaceService;
    }

    public function __invoke(Request $request, \Psr\Http\Server\RequestHandlerInterface $handler): Response
    {
        $userId = $request->getAttribute('userId'); // from AuthMiddleware
        
        if (!$userId) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Unauthenticated']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $workspaceId = $request->getHeaderLine('X-Workspace-Id');

        if (empty($workspaceId)) {
            $stmt = $this->pdo->prepare("SELECT WorkspaceId FROM workspace_users WHERE UserId = ? ORDER BY JoinedAt ASC LIMIT 1");
            $stmt->execute([$userId]);
            $w = $stmt->fetch();
            if ($w) {
                $workspaceId = $w['WorkspaceId'];
            } else {
                // Auto-create workspace if none exists (Safety Net)
                // We need the user code or name for the default name. 
                // We'll just fetch the first name from the user table.
                $stmtUser = $this->pdo->prepare("SELECT FirstName FROM user WHERE UserId = ?");
                $stmtUser->execute([$userId]);
                $userName = $stmtUser->fetchColumn() ?: "User";
                
                $workspaceId = $this->workspaceService->createDefaultWorkspace($userId, $userName);
            }
        }

        $stmt = $this->pdo->prepare("SELECT Role, Permissions FROM workspace_users WHERE WorkspaceId = ? AND UserId = ?");
        $stmt->execute([$workspaceId, $userId]);
        $pivot = $stmt->fetch();

        if (!$pivot) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Not a member of this workspace.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $request = $request->withAttribute('workspaceId', (int)$workspaceId);
        $request = $request->withAttribute('workspaceRole', $pivot['Role']);
        $request = $request->withAttribute('workspacePermissions', json_decode($pivot['Permissions'] ?? '{}', true));

        return $handler->handle($request);
    }
}
