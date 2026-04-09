<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\DTO\AuthDTO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class AuthController {
    private AuthService $authService;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    public function signup(Request $request, Response $response): Response {
        $input = (array) $request->getParsedBody();
        $dto = new AuthDTO($input);

        if (!$dto->isValidForSignup()) {
            $response->getBody()->write(json_encode(["success" => false, "error" => "Dados inválidos para cadastro."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $user = $this->authService->register([
                'email' => $dto->email,
                'password' => $dto->password,
                'firstName' => $dto->firstName,
                'lastName' => $dto->lastName
            ]);
            $response->getBody()->write(json_encode([
                "success" => true,
                "message" => "User registered successfully.",
                "user" => [
                    "id" => $user->getId(),
                    "email" => $user->getEmail(),
                    "firstName" => $user->getFirstName(),
                    "userCode" => $user->getUserCode()
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                "success" => false,
                "error" => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function login(Request $request, Response $response): Response {
        $input = (array) $request->getParsedBody();
        $dto = new AuthDTO($input);

        if (!$dto->isValidForLogin()) {
            $response->getBody()->write(json_encode(["success" => false, "error" => "Credenciais inválidas."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $user = $this->authService->login($dto->email, $dto->password);
            
            $response->getBody()->write(json_encode([
                "success" => true,
                "message" => "Login successful.",
                "token" => base64_encode($user->getId() . ':' . $user->getEmail()),
                "user" => [
                    "id" => $user->getId(),
                    "email" => $user->getEmail(),
                    "firstName" => $user->getFirstName(),
                    "currency" => $user->getCurrency(),
                    "userCode" => $user->getUserCode()
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                "success" => false,
                "error" => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    }

    public function me(Request $request, Response $response): Response {
        $userId = $request->getAttribute('userId');
        try {
             $user = $this->authService->getUser($userId);
             $response->getBody()->write(json_encode(["success" => true, "data" => [
                 "id" => $user->getId(), 
                 "email" => $user->getEmail(),
                 "firstName" => $user->getFirstName(), 
                 "lastName" => $user->getLastName(),
                 "currency" => $user->getCurrency(),
                 "userCode" => $user->getUserCode()
             ]]));
             return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
             $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
             return $response->withHeader('Content-Type', 'application/json')->withStatus(400); 
        }
    }
    
    public function updateProfile(Request $request, Response $response): Response {
        $userId = $request->getAttribute('userId');
        $input = (array) $request->getParsedBody();
        try {
             $user = $this->authService->updateProfile($userId, $input);
             $response->getBody()->write(json_encode(["success" => true, "message" => "Perfil atualizado!"]));
             return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
             $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
             return $response->withHeader('Content-Type', 'application/json')->withStatus(400); 
        }
    }
}
