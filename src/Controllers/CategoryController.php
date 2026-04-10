<?php

namespace App\Controllers;

use App\Services\CategoryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class CategoryController {
    private CategoryService $categoryService;

    public function __construct(CategoryService $categoryService) {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $params = $request->getQueryParams();
        $type = $params['type'] ?? 'income';

        try {
            $categories = $this->categoryService->getAllForUser($workspaceId, $type);
            $data = array_map(function($cat) {
                return [
                    "id" => $cat->getId(),
                    "name" => $cat->getCategoryName(),
                    "level" => $cat->getLevel()
                ];
            }, $categories);

            $response->getBody()->write(json_encode(["success" => true, "data" => $data]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function create(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $input = (array) $request->getParsedBody();
        $name = $input['name'] ?? '';
        $type = $input['type'] ?? 'income';

        try {
            $result = $this->categoryService->create($workspaceId, $name, $type);
            
            $data = is_array($result) 
                ? array_map(fn($cat) => ["id" => $cat->getId(), "name" => $cat->getCategoryName(), "type" => $type], $result)
                : ["id" => $result->getId(), "name" => $result->getCategoryName(), "type" => $type];

            $message = is_array($result) && count($result) > 1 
                ? count($result) . " categorias criadas com sucesso." 
                : "Category created successfully.";

            $response->getBody()->write(json_encode([
                "success" => true, 
                "message" => $message,
                "data" => $data
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function update(Request $request, Response $response, array $args): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int) ($args['id'] ?? 0);
        $input = (array) $request->getParsedBody();
        $name = $input['name'] ?? '';

        try {
            $cat = $this->categoryService->update($id, $workspaceId, $name);
            $response->getBody()->write(json_encode([
                "success" => true, 
                "message" => "Category updated successfully.",
                "data" => ["id" => $cat->getId(), "name" => $cat->getCategoryName()]
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int) ($args['id'] ?? 0);

        try {
            $this->categoryService->delete($id, $workspaceId);
            $response->getBody()->write(json_encode(["success" => true, "message" => "Category deleted."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
}
