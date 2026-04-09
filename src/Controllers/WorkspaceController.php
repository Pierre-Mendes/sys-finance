<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class WorkspaceController
{
    private PDO $pdo;
    private \App\Services\WorkspaceService $workspaceService;

    public function __construct(PDO $pdo, \App\Services\WorkspaceService $workspaceService)
    {
        $this->pdo = $pdo;
        $this->workspaceService = $workspaceService;
    }

    public function getMyWorkspaces(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('userId');
        
        $stmt = $this->pdo->prepare("
            SELECT w.WorkspaceId as id, w.WorkspaceName as name, w.OwnerId as ownerId, wu.Role as role 
            FROM workspaces w 
            INNER JOIN workspace_users wu ON w.WorkspaceId = wu.WorkspaceId 
            WHERE wu.UserId = ?
        ");
        $stmt->execute([$userId]);
        $workspaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode(['data' => $workspaces]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function createWorkspace(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('userId');
        $input = (array) $request->getParsedBody();
        $name = $input['name'] ?? null;
        $invites = $input['invites'] ?? []; 

        if (!$name) {
            $response->getBody()->write(json_encode(['error' => 'O nome do workspace é obrigatório.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $workspaceId = $this->workspaceService->createDefaultWorkspace($userId, $name);

            // Handle invitations if any
            if (!empty($invites)) {
                foreach ($invites as $userCode) {
                    $userCode = trim($userCode);
                    if (empty($userCode)) continue;
                    $stmtU = $this->pdo->prepare("SELECT UserId FROM user WHERE UserCode = ? LIMIT 1");
                    $stmtU->execute([$userCode]);
                    $targetUserId = $stmtU->fetchColumn();

                    if ($targetUserId && $targetUserId != $userId) {
                        $insInv = $this->pdo->prepare("INSERT INTO system_invitations (SenderId, TargetUserId, WorkspaceId, Status) VALUES (?, ?, ?, 'pending')");
                        $insInv->execute([$userId, $targetUserId, $workspaceId]);
                    }
                }
            }

            $response->getBody()->write(json_encode(['message' => 'Workspace criado com sucesso!', 'workspaceId' => $workspaceId]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getMembers(Request $request, Response $response): Response
    {
        $workspaceId = $request->getAttribute('workspaceId');
        
        $stmt = $this->pdo->prepare("
            SELECT u.UserId as id, u.FirstName as firstName, u.LastName as lastName, wu.Role as role, wu.JoinedAt as joinedAt, wu.UsedInviteCode as usedInviteCode 
            FROM workspace_users wu 
            INNER JOIN user u ON u.UserId = wu.UserId 
            WHERE wu.WorkspaceId = ?
        ");
        $stmt->execute([$workspaceId]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode(['data' => $members]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function revokeMember(Request $request, Response $response, array $args): Response
    {
        $workspaceId = $request->getAttribute('workspaceId');
        $role = $request->getAttribute('workspaceRole');
        $targetUserId = (int) $args['id'];
        
        if ($role !== 'owner') {
            $response->getBody()->write(json_encode(['error' => 'Apenas o proprietário do Workspace pode revogar acessos.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $stmt = $this->pdo->prepare("SELECT OwnerId FROM workspaces WHERE WorkspaceId = ?");
        $stmt->execute([$workspaceId]);
        $owner = $stmt->fetchColumn();

        if ($owner == $targetUserId) {
            $response->getBody()->write(json_encode(['error' => 'Não é possível revogar o acesso do criador raiz.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmtDel = $this->pdo->prepare("DELETE FROM workspace_users WHERE WorkspaceId = ? AND UserId = ?");
        $stmtDel->execute([$workspaceId, $targetUserId]);

        return $response->withStatus(204);
    }

    public function updateWorkspace(Request $request, Response $response, array $args): Response
    {
        $workspaceId = (int) $args['id'];
        $role = $request->getAttribute('workspaceRole');
        
        if ($role !== 'owner') {
            $response->getBody()->write(json_encode(['error' => 'Apenas o proprietário pode editar o Workspace.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $input = (array) $request->getParsedBody();
        $name = $input['name'] ?? null;
        if (!$name) {
            $response->getBody()->write(json_encode(['error' => 'Nome é obrigatório']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmt = $this->pdo->prepare("UPDATE workspaces SET WorkspaceName = ? WHERE WorkspaceId = ?");
        $stmt->execute([$name, $workspaceId]);

        return $response->withStatus(200);
    }

    public function deleteWorkspace(Request $request, Response $response, array $args): Response
    {
        $workspaceId = (int) $args['id'];
        $role = $request->getAttribute('workspaceRole');
        
        if ($role !== 'owner') {
            $response->getBody()->write(json_encode(['error' => 'Apenas o proprietário pode excluir o Workspace.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        try {
            $this->pdo->beginTransaction();
            // Delete dependent records
            $this->pdo->prepare("DELETE FROM totals WHERE WorkspaceId = ?")->execute([$workspaceId]);
            $this->pdo->prepare("DELETE FROM bills WHERE WorkspaceId = ?")->execute([$workspaceId]);
            $this->pdo->prepare("DELETE FROM assets WHERE WorkspaceId = ?")->execute([$workspaceId]);
            $this->pdo->prepare("DELETE FROM category WHERE WorkspaceId = ?")->execute([$workspaceId]);
            $this->pdo->prepare("DELETE FROM account WHERE WorkspaceId = ?")->execute([$workspaceId]);
            $this->pdo->prepare("DELETE FROM workspace_users WHERE WorkspaceId = ?")->execute([$workspaceId]);
            $this->pdo->prepare("DELETE FROM system_invitations WHERE WorkspaceId = ?")->execute([$workspaceId]);
            
            $stmt = $this->pdo->prepare("DELETE FROM workspaces WHERE WorkspaceId = ?");
            $stmt->execute([$workspaceId]);
            
            $this->pdo->commit();
            return $response->withStatus(200);
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $response->getBody()->write(json_encode(['error' => 'Erro ao excluir o workspace: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function transferOwner(Request $request, Response $response, array $args): Response
    {
        $workspaceId = $request->getAttribute('workspaceId');
        $userId = $request->getAttribute('userId');
        $role = $request->getAttribute('workspaceRole');
        $targetUserId = (int) $args['userId'];
        
        if ($role !== 'owner') {
            $response->getBody()->write(json_encode(['error' => 'Apenas o proprietário pode transferir a liderança.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $stmtCheck = $this->pdo->prepare("SELECT Role FROM workspace_users WHERE WorkspaceId = ? AND UserId = ?");
        $stmtCheck->execute([$workspaceId, $targetUserId]);
        if (!$stmtCheck->fetchColumn()) {
            $response->getBody()->write(json_encode(['error' => 'Usuário de destino não é membro deste Workspace.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $this->pdo->beginTransaction();

            $this->pdo->prepare("UPDATE workspace_users SET Role = 'member' WHERE WorkspaceId = ? AND UserId = ?")->execute([$workspaceId, $userId]);
            $this->pdo->prepare("UPDATE workspace_users SET Role = 'owner' WHERE WorkspaceId = ? AND UserId = ?")->execute([$workspaceId, $targetUserId]);
            $this->pdo->prepare("UPDATE workspaces SET OwnerId = ? WHERE WorkspaceId = ?")->execute([$targetUserId, $workspaceId]);

            $this->pdo->commit();
            return $response->withStatus(200);
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $response->getBody()->write(json_encode(['error' => 'Erro ao transferir: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function leaveWorkspace(Request $request, Response $response, array $args): Response
    {
        $workspaceId = $request->getAttribute('workspaceId');
        $userId = $request->getAttribute('userId');
        $role = $request->getAttribute('workspaceRole');
        
        if ($role === 'owner') {
            $response->getBody()->write(json_encode(['error' => 'Para sair do Workspace, transfira a liderança (Owner) primeiro. ou caso não queira, apague-o.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmtDel = $this->pdo->prepare("DELETE FROM workspace_users WHERE WorkspaceId = ? AND UserId = ?");
        $stmtDel->execute([$workspaceId, $userId]);

        return $response->withStatus(200);
    }
}
