<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class WorkspaceMiddleware
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode(['error' => 'No Workspace available.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
        }

        $stmt = $this->pdo->prepare("SELECT Role FROM workspace_users WHERE WorkspaceId = ? AND UserId = ?");
        $stmt->execute([$workspaceId, $userId]);
        $pivot = $stmt->fetch();

        if (!$pivot) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Not a member of this workspace.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $request = $request->withAttribute('workspaceId', (int)$workspaceId);
        $request = $request->withAttribute('workspaceRole', $pivot['Role']);

        return $handler->handle($request);
    }
}
