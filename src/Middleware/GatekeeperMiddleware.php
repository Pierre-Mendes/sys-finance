<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GatekeeperMiddleware
{
    /**
     * Retorna um middleware que valida se o usuário tem permissão para editar um módulo.
     * 
     * @param string $module (accounts, categories, transactions, budgets, goals, credit_cards, investments, reports)
     */
    public static function requireEditor(string $module)
    {
        return function (Request $request, \Psr\Http\Server\RequestHandlerInterface $handler) use ($module): Response {
            $role = $request->getAttribute('workspaceRole');
            
            // O Owner sempre pode tudo
            if ($role === 'owner') {
                return $handler->handle($request);
            }

            $permissions = $request->getAttribute('workspacePermissions') ?? [];
            $modulePermission = $permissions[$module] ?? 'viewer';

            if ($modulePermission !== 'editor') {
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => "Você não tem permissão de edição para o módulo: {$module}"
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            return $handler->handle($request);
        };
    }
}
