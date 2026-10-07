<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Security\SessionCookie;
use App\Security\TokenService;
use App\DTO\AuthDTO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class AuthController {
    private AuthService $authService;
    private TokenService $tokenService;

    public function __construct(AuthService $authService, ?TokenService $tokenService = null) {
        $this->tokenService = $tokenService ?? new TokenService();
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
                "error" => \App\Security\PublicError::message($e)
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
            
            // O token vai só no cookie HttpOnly: o JavaScript da página nunca o vê.
            $response = SessionCookie::attach($response, $request, $this->tokenService->issue($user->getId(), $user->getEmail()), $this->tokenService->ttl());
            $response->getBody()->write(json_encode([
                "success" => true,
                "message" => "Login successful.",
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
                "error" => \App\Security\PublicError::message($e)
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    }

    /** Encerra a sessão apagando os cookies (funciona mesmo com a sessão já expirada). */
    public function logout(Request $request, Response $response): Response {
        $response->getBody()->write(json_encode(["success" => true]));
        return SessionCookie::clear($response, $request)->withHeader('Content-Type', 'application/json');
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
             $response->getBody()->write(json_encode(["success" => false, "error" => \App\Security\PublicError::message($e)]));
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
             $response->getBody()->write(json_encode(["success" => false, "error" => \App\Security\PublicError::message($e)]));
             return $response->withHeader('Content-Type', 'application/json')->withStatus(400); 
        }
    }

    public function getRecoveryQuestion(Request $request, Response $response): Response {
        $params = $request->getQueryParams();
        $email = $params['email'] ?? '';
        
        try {
            $question = $this->authService->getRecoveryQuestion($email);
            $response->getBody()->write(json_encode(["success" => true, "question" => $question]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => \App\Security\PublicError::message($e)]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400); 
        }
    }

    public function resetPassword(Request $request, Response $response): Response {
        $input = (array) $request->getParsedBody();
        $email = $input['email'] ?? '';
        $answer = $input['answer'] ?? '';
        $newPassword = $input['newPassword'] ?? '';

        if (empty($email) || empty($answer) || empty($newPassword)) {
            $response->getBody()->write(json_encode(["success" => false, "error" => "Dados insuficientes."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $this->authService->resetPassword($email, $answer, $newPassword);
            $response->getBody()->write(json_encode(["success" => true, "message" => "Senha redefinida com sucesso!"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => \App\Security\PublicError::message($e)]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400); 
        }
    }
}
