<?php

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class NotificationController {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function index(Request $request, Response $response): Response {
        $userId = $request->getAttribute('userId');
        
        try {
            $stmt = $this->db->prepare("
                SELECT id, title, message, type, related_id, action_url, read_at, created_at 
                FROM notifications 
                WHERE user_id = :uid 
                ORDER BY created_at DESC 
                LIMIT 50
            ");
            $stmt->execute(['uid' => $userId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                "success" => true,
                "data" => $notifications
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function read(Request $request, Response $response, array $args): Response {
        $userId = $request->getAttribute('userId');
        $id = $args['id'];
        
        try {
            $stmt = $this->db->prepare("UPDATE notifications SET read_at = NOW() WHERE id = :id AND user_id = :uid");
            $stmt->execute(['id' => $id, 'uid' => $userId]);

            $response->getBody()->write(json_encode([
                "success" => true,
                "message" => "Notification marked as read"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function readAll(Request $request, Response $response): Response {
        $userId = $request->getAttribute('userId');
        
        try {
            $stmt = $this->db->prepare("UPDATE notifications SET read_at = NOW() WHERE user_id = :uid AND read_at IS NULL");
            $stmt->execute(['uid' => $userId]);

            $response->getBody()->write(json_encode([
                "success" => true,
                "message" => "All notifications marked as read"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function deleteAll(Request $request, Response $response): Response {
        $userId = $request->getAttribute('userId');
        
        try {
            $stmt = $this->db->prepare("DELETE FROM notifications WHERE user_id = :uid");
            $stmt->execute(['uid' => $userId]);

            $response->getBody()->write(json_encode([
                "success" => true,
                "message" => "All notifications deleted"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $userId = $request->getAttribute('userId');
        $id = $args['id'];
        
        try {
            $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = :id AND user_id = :uid");
            $stmt->execute(['id' => $id, 'uid' => $userId]);

            $response->getBody()->write(json_encode([
                "success" => true,
                "message" => "Notification deleted successfully"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
}
