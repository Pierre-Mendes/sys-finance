<?php

namespace Tests\Integration;

use App\Controllers\WorkspaceController;
use App\Services\WorkspaceService;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

class WorkspaceOwnershipTest extends TestCase
{
    public function test_owner_of_one_workspace_cannot_delete_or_rename_another(): void
    {
        $service = new WorkspaceService($this->db);
        $attackerWs = $service->createDefaultWorkspace(10, 'Atacante');
        $victimWs = $service->createDefaultWorkspace(20, 'Vítima');
        $controller = new WorkspaceController($this->db, $service);

        // O atacante é owner do workspace ativo (header), mas aponta a URL para o da vítima.
        $request = (new ServerRequestFactory())->createServerRequest('DELETE', "/api/workspaces/{$victimWs}")
            ->withAttribute('userId', 10)
            ->withAttribute('workspaceId', $attackerWs)
            ->withAttribute('workspaceRole', 'owner')
            ->withParsedBody(['name' => 'hackeado']);

        $this->assertSame(403, $controller->deleteWorkspace($request, new Response(), ['id' => (string) $victimWs])->getStatusCode());
        $this->assertSame(403, $controller->updateWorkspace($request, new Response(), ['id' => (string) $victimWs])->getStatusCode());

        $stmt = $this->db->prepare("SELECT WorkspaceName FROM workspaces WHERE WorkspaceId = ?");
        $stmt->execute([$victimWs]);
        $this->assertSame('Meu Workspace (Vítima)', $stmt->fetchColumn());

        $own = $request->withAttribute('workspaceId', $attackerWs)->withParsedBody(['name' => 'Novo nome']);
        $this->assertSame(200, $controller->updateWorkspace($own, new Response(), ['id' => (string) $attackerWs])->getStatusCode());
    }
}
