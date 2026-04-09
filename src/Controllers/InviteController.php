<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class InviteController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function generateInvite(Request $request, Response $response): Response
    {
        $workspaceId = $request->getAttribute('workspaceId');
        $role = $request->getAttribute('workspaceRole');
        $userId = $request->getAttribute('userId');
        
        if ($role !== 'owner') {
            $response->getBody()->write(json_encode(['error' => 'Apenas o proprietário pode gerar convites de acesso.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $data = json_decode((string)$request->getBody(), true);
        if (empty($data['passcode']) || !isset($data['expiresDays'])) {
            $response->getBody()->write(json_encode(['error' => 'Palavra passe e Prazo de expiração são obrigatórios.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $inviteCode = strtoupper(substr(uniqid('FW-'), -8) . '-' . substr(bin2hex(random_bytes(2)), 0, 4));
        $passHash = password_hash($data['passcode'], PASSWORD_BCRYPT);
        
        $expires = null;
        if ($data['expiresDays'] > 0) {
            $expires = date('Y-m-d H:i:s', strtotime("+{$data['expiresDays']} days"));
        }

        // Cleanup past expired invites to keep db clean
        $this->pdo->exec("DELETE FROM workspace_invites WHERE ExpiresAt IS NOT NULL AND ExpiresAt < NOW()");

        $stmt = $this->pdo->prepare("INSERT INTO workspace_invites (WorkspaceId, InviteCode, Passcode, ExpiresAt, CreatedBy) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$workspaceId, $inviteCode, $passHash, $expires, $userId]);

        $response->getBody()->write(json_encode([
            'message' => 'Código de convite gerado.',
            'inviteCode' => $inviteCode,
            'expiresAt' => $expires
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function getInvites(Request $request, Response $response): Response
    {
        $workspaceId = $request->getAttribute('workspaceId');
        
        $stmt = $this->pdo->prepare("
            SELECT i.InviteId as id, i.InviteCode as code, i.ExpiresAt as expiresAt, i.CreatedAt as createdAt,
                   (SELECT COUNT(*) FROM workspace_users wu WHERE wu.UsedInviteCode = i.InviteCode AND wu.WorkspaceId = i.WorkspaceId) as usages 
            FROM workspace_invites i 
            WHERE i.WorkspaceId = ?
        ");
        $stmt->execute([$workspaceId]);
        $invites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode(['data' => $invites]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function deleteInvite(Request $request, Response $response, array $args): Response
    {
        $workspaceId = $request->getAttribute('workspaceId');
        $role = $request->getAttribute('workspaceRole');
        $inviteId = (int) $args['id'];
        
        if ($role !== 'owner') {
            $response->getBody()->write(json_encode(['error' => 'Apenas o proprietário do Workspace pode gerenciar convites.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $stmtDel = $this->pdo->prepare("DELETE FROM workspace_invites WHERE WorkspaceId = ? AND InviteId = ?");
        $stmtDel->execute([$workspaceId, $inviteId]);

        return $response->withStatus(204);
    }

    public function joinWorkspace(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('userId'); // from AuthMiddleware, notice we bypass WorkspaceMiddleware for this route!
        
        $data = json_decode((string)$request->getBody(), true);
        if (empty($data['inviteCode']) || empty($data['passcode'])) {
            $response->getBody()->write(json_encode(['error' => 'Código e Palavra-passe são obrigatórios.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmt = $this->pdo->prepare("SELECT * FROM workspace_invites WHERE InviteCode = ?");
        $stmt->execute([$data['inviteCode']]);
        $invite = $stmt->fetch();

        if (!$invite) {
            $response->getBody()->write(json_encode(['error' => 'Código de Visão inválido ou inexistente.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        if ($invite['ExpiresAt'] !== null && strtotime($invite['ExpiresAt']) < time()) {
            $response->getBody()->write(json_encode(['error' => 'O código de vinculação expirou.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (!password_verify($data['passcode'], $invite['Passcode'])) {
            $response->getBody()->write(json_encode(['error' => 'A Palavra-passe de segurança informada está incorreta.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Checks if already in
        $chk = $this->pdo->prepare("SELECT 1 FROM workspace_users WHERE WorkspaceId = ? AND UserId = ?");
        $chk->execute([$invite['WorkspaceId'], $userId]);
        if ($chk->fetch()) {
             $response->getBody()->write(json_encode(['error' => 'Você já pertence a este Workspace financeiro.']));
             return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Insert membership
        $ins = $this->pdo->prepare("INSERT INTO workspace_users (WorkspaceId, UserId, Role, UsedInviteCode) VALUES (?, ?, 'viewer', ?)");
        $ins->execute([$invite['WorkspaceId'], $userId, $invite['InviteCode']]);

        $response->getBody()->write(json_encode(['message' => 'Parabéns! Você se conectou à nova Visão com sucesso!']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function getSystemInvites(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('userId');
        
        $stmt = $this->pdo->prepare("
            SELECT i.InvitationId as id, i.Status as status, i.CreatedAt as createdAt,
                   w.WorkspaceId as workspaceId, w.WorkspaceName as workspaceName,
                   u.FirstName as senderFirstName, u.LastName as senderLastName
            FROM system_invitations i
            INNER JOIN workspaces w ON i.WorkspaceId = w.WorkspaceId
            INNER JOIN user u ON i.SenderId = u.UserId
            WHERE i.TargetUserId = ? AND i.Status = 'pending'
            ORDER BY i.CreatedAt DESC
        ");
        $stmt->execute([$userId]);
        $invites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode(['data' => $invites]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function resolveSystemInvite(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('userId');
        $invitationId = (int) $args['id'];
        $input = (array) $request->getParsedBody();
        $action = $input['action'] ?? 'reject'; // accept | reject

        $stmt = $this->pdo->prepare("SELECT * FROM system_invitations WHERE InvitationId = ? AND TargetUserId = ? LIMIT 1");
        $stmt->execute([$invitationId, $userId]);
        $invitation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invitation) {
            $response->getBody()->write(json_encode(['error' => 'Convite não encontrado ou sem permissão.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        if ($invitation['Status'] !== 'pending') {
            $response->getBody()->write(json_encode(['error' => 'Convite já processado.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if ($action === 'accept') {
            $stmtRole = $this->pdo->prepare("SELECT COUNT(*) FROM workspace_users WHERE WorkspaceId = ? AND UserId = ?");
            $stmtRole->execute([$invitation['WorkspaceId'], $userId]);
            if ($stmtRole->fetchColumn() == 0) {
                $ins = $this->pdo->prepare("INSERT INTO workspace_users (WorkspaceId, UserId, Role, Affinity) VALUES (?, ?, 'viewer', 'Time')");
                $ins->execute([$invitation['WorkspaceId'], $userId]);
            }
        }

        $stmtUpt = $this->pdo->prepare("UPDATE system_invitations SET Status = ? WHERE InvitationId = ?");
        $stmtUpt->execute([$action === 'accept' ? 'accepted' : 'rejected', $invitationId]);

        $response->getBody()->write(json_encode(['message' => 'Status do convite alterado.']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
