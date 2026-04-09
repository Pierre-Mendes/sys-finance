<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Controllers\AuthController;
use Slim\Factory\AppFactory;

$app = AppFactory::create();

// Parse json, form data and xml
$app->addBodyParsingMiddleware();

// Add the Slim built-in routing middleware
$app->addRoutingMiddleware();

// CORS for Frontend
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// OWASP Global Security Headers
$app->add(new \App\Middleware\SecurityHeadersMiddleware());

// Dependency Injection Bootstrap
$db = Database::getConnection();

$workspaceService = new \App\Services\WorkspaceService($db);

$userRepo = new UserRepository($db);
$authService = new AuthService($userRepo, $workspaceService);
$authController = new AuthController($authService);

$accountRepo = new \App\Repositories\AccountRepository($db);
$accountService = new \App\Services\AccountService($accountRepo);
$accountController = new App\Controllers\AccountController($accountService);

$categoryRepo = new \App\Repositories\CategoryRepository($db);
$categoryService = new \App\Services\CategoryService($categoryRepo);
$categoryController = new App\Controllers\CategoryController($categoryService);

// Routes
$app->get('/api/health', function ($request, $response) {
    $response->getBody()->write(json_encode(["status" => "ok", "message" => "O Gerenciador Financeiro Pessoal API está online."]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/auth/signup', [$authController, 'signup'])->add(new \App\Middleware\RateLimiterMiddleware($db, 10, 15));
$app->post('/api/auth/login', [$authController, 'login'])->add(new \App\Middleware\RateLimiterMiddleware($db, 5, 10));

$app->get('/api/auth/me', [$authController, 'me'])->add(new \App\Middleware\AuthMiddleware());
$app->put('/api/auth/profile', [$authController, 'updateProfile'])->add(new \App\Middleware\AuthMiddleware());

$app->group('/api/accounts', function (\Slim\Routing\RouteCollectorProxy $group) use ($accountController) {
    $group->get('', [$accountController, 'index']);
    $group->post('', [$accountController, 'create']);
    $group->put('/{id}', [$accountController, 'update']);
    $group->delete('/{id}', [$accountController, 'delete']);
})->add(new \App\Middleware\WorkspaceMiddleware($db))->add(new \App\Middleware\AuthMiddleware());

$app->group('/api/categories', function (\Slim\Routing\RouteCollectorProxy $group) use ($categoryController) {
    $group->get('', [$categoryController, 'index']);
    $group->post('', [$categoryController, 'create']);
    $group->put('/{id}', [$categoryController, 'update']);
    $group->delete('/{id}', [$categoryController, 'delete']);
})->add(new \App\Middleware\WorkspaceMiddleware($db))->add(new \App\Middleware\AuthMiddleware());

$assetRepo = new \App\Repositories\AssetRepository($db);
$billRepo = new \App\Repositories\BillRepository($db);
$txService = new \App\Services\TransactionService($assetRepo, $billRepo, $db);
$txController = new App\Controllers\TransactionController($txService);

$dashService = new \App\Services\DashboardService($db);
$dashController = new \App\Controllers\DashboardController($dashService);

$app->group('/api/transactions', function (\Slim\Routing\RouteCollectorProxy $group) use ($txController) {
    $group->get('', [$txController, 'index']);
    $group->post('', [$txController, 'create']);
    $group->put('/{id}', [$txController, 'update']);
    $group->delete('/{id}', [$txController, 'delete']);
    $group->post('/{id}/pay', [$txController, 'pay']);
})->add(new \App\Middleware\WorkspaceMiddleware($db))->add(new \App\Middleware\AuthMiddleware());

$app->get('/api/dashboard', [$dashController, 'index'])->add(new \App\Middleware\WorkspaceMiddleware($db))->add(new \App\Middleware\AuthMiddleware());

$reportController = new \App\Controllers\ReportController($txService);
$app->group('/api/reports', function (\Slim\Routing\RouteCollectorProxy $group) use ($reportController) {
    $group->get('/pdf', [$reportController, 'generatePdf']);
    $group->get('/csv', [$reportController, 'generateCsv']);
})->add(new \App\Middleware\WorkspaceMiddleware($db))->add(new \App\Middleware\AuthMiddleware());

$budgetRepo = new \App\Repositories\BudgetRepository($db);
$budgetController = new \App\Controllers\BudgetController($budgetRepo);

$app->group('/api/budgets', function (\Slim\Routing\RouteCollectorProxy $group) use ($budgetController) {
    $group->get('', [$budgetController, 'index']);
    $group->post('', [$budgetController, 'create']);
    $group->put('/{id}', [$budgetController, 'update']);
    $group->delete('/{id}', [$budgetController, 'delete']);
})->add(new \App\Middleware\WorkspaceMiddleware($db))->add(new \App\Middleware\AuthMiddleware());

$quoteService = new \App\Services\InvestmentQuoteService([
    new \App\Strategies\BrapiQuoteStrategy(),
    new \App\Strategies\SicoobRDCStrategy()
]);
$investmentController = new \App\Controllers\InvestmentController($db, $quoteService);

$app->group('/api/investments', function (\Slim\Routing\RouteCollectorProxy $group) use ($investmentController) {
    $group->get('', [$investmentController, 'index']);
    $group->post('', [$investmentController, 'create']);
    $group->delete('/{id}', [$investmentController, 'destroy']);
    $group->post('/quotes/sync', [$investmentController, 'syncQuotes']);
    $group->post('/{id}/transactions', [$investmentController, 'addTransaction']);
    $group->put('/{id}', [$investmentController, 'manualQuote']);
})->add(new \App\Middleware\WorkspaceMiddleware($db))->add(new \App\Middleware\AuthMiddleware());

$workspaceController = new App\Controllers\WorkspaceController($db, $workspaceService);
$inviteController = new App\Controllers\InviteController($db);

$app->group('/api/workspaces', function ($group) use ($workspaceController, $inviteController) {
    $group->post('', [$workspaceController, 'createWorkspace']);
    $group->get('', [$workspaceController, 'getMyWorkspaces']);
    $group->put('/{id}', [$workspaceController, 'updateWorkspace']);
    $group->delete('/{id}', [$workspaceController, 'deleteWorkspace']);

    $group->get('/members', [$workspaceController, 'getMembers']);
    $group->delete('/members/leave', [$workspaceController, 'leaveWorkspace']);
    $group->delete('/members/{id}', [$workspaceController, 'revokeMember']);
    $group->put('/members/{userId}/transfer-owner', [$workspaceController, 'transferOwner']);
    
    $group->post('/invite', [$inviteController, 'generateInvite']);
    $group->get('/invite', [$inviteController, 'getInvites']);
    $group->delete('/invite/{id}', [$inviteController, 'deleteInvite']);
})->add(new \App\Middleware\WorkspaceMiddleware($db))->add(new \App\Middleware\AuthMiddleware());

$cardRepo = new \App\Repositories\CreditCardRepository($db);
$cardTxRepo = new \App\Repositories\CreditCardTransactionRepository($db);
$cardService = new \App\Services\CreditCardService($cardRepo, $cardTxRepo, $billRepo);
$cardController = new \App\Controllers\CreditCardController($cardService);

$app->group('/api/credit-cards', function (\Slim\Routing\RouteCollectorProxy $group) use ($cardController) {
    $group->get('', [$cardController, 'index']);
    $group->post('', [$cardController, 'create']);
    $group->put('/{id}', [$cardController, 'update']);
    $group->delete('/{id}', [$cardController, 'delete']);
    $group->post('/{id}/transactions', [$cardController, 'addTransaction']);
    $group->get('/{id}/transactions', [$cardController, 'transactions']);
    $group->put('/transactions/{id}', [$cardController, 'updateTransaction']);
    $group->delete('/transactions/{id}', [$cardController, 'deleteTransaction']);
    $group->post('/{id}/invoices/generate', [$cardController, 'generateInvoice']);
})->add(new \App\Middleware\WorkspaceMiddleware($db))->add(new \App\Middleware\AuthMiddleware());

$app->post('/api/workspaces/join', [$inviteController, 'joinWorkspace'])->add(new \App\Middleware\AuthMiddleware());

$app->group('/api/system-invites', function ($group) use ($inviteController) {
    $group->get('', [$inviteController, 'getSystemInvites']);
    $group->post('/{id}/resolve', [$inviteController, 'resolveSystemInvite']);
})->add(new \App\Middleware\AuthMiddleware());

$notificationController = new \App\Controllers\NotificationController($db);
$app->group('/api/notifications', function ($group) use ($notificationController) {
    $group->get('', [$notificationController, 'index']);
    $group->put('/read-all', [$notificationController, 'readAll']);
    $group->put('/{id}/read', [$notificationController, 'read']);
})->add(new \App\Middleware\AuthMiddleware());

$goalRepo = new \App\Repositories\GoalRepository();
$goalContributionRepo = new \App\Repositories\GoalContributionRepository();
$simulationService = new \App\Services\SimulationService($assetRepo, $billRepo, $goalRepo);
$goalService = new \App\Services\GoalService($goalRepo, $goalContributionRepo, $simulationService);
$goalController = new \App\Controllers\GoalController($goalService);

$app->group('/api/goals', function (\Slim\Routing\RouteCollectorProxy $group) use ($goalController) {
    $group->get('', [$goalController, 'list']);
    $group->post('', [$goalController, 'create']);
    $group->post('/contributions', [$goalController, 'contribute']);
    $group->get('/{id}/forecast', [$goalController, 'forecast']);
})->add(new \App\Middleware\WorkspaceMiddleware($db))->add(new \App\Middleware\AuthMiddleware());

// Add Error Middleware last
$app->addErrorMiddleware(true, true, true);

$app->run();
