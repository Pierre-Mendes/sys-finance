<?php

$isProd = (getenv('APP_ENV') === 'production');
if (!$isProd) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
    
    // Custom error handler to output JSON instead of HTML for API errors during bootstrap
    set_exception_handler(function (\Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Fatal Bootstrap Error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        exit;
    });
}

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

$notificationService = new \App\Services\NotificationService($db);
$userRepo = new UserRepository($db);
$authService = new AuthService($userRepo, $workspaceService, $notificationService);
$authController = new AuthController($authService);

$accountRepo = new \App\Repositories\AccountRepository($db);
$accountService = new \App\Services\AccountService($accountRepo);
$accountController = new App\Controllers\AccountController($accountService);

$statementService = new \App\Services\StatementService($db);
$statementController = new \App\Controllers\StatementController($statementService);

$categoryRepo = new \App\Repositories\CategoryRepository($db);
$categoryService = new \App\Services\CategoryService($categoryRepo);
$categoryController = new App\Controllers\CategoryController($categoryService);

// Middleware Instances
$authMiddleware = new \App\Middleware\AuthMiddleware();
$workspaceMiddleware = new \App\Middleware\WorkspaceMiddleware($db, $workspaceService);

// Routes
$app->get('/api/health', function ($request, $response) {
    $response->getBody()->write(json_encode(["status" => "ok", "message" => "O Gerenciador Financeiro Pessoal API está online."]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/auth/signup', [$authController, 'signup'])->add(new \App\Middleware\RateLimiterMiddleware($db, 10, 15));
$app->post('/api/auth/login', [$authController, 'login'])->add(new \App\Middleware\RateLimiterMiddleware($db, 5, 10));

$app->get('/api/auth/me', [$authController, 'me'])->add($authMiddleware);
$app->put('/api/auth/profile', [$authController, 'updateProfile'])->add($authMiddleware);
$app->get('/api/auth/recovery-question', [$authController, 'getRecoveryQuestion']);
$app->post('/api/auth/reset-password', [$authController, 'resetPassword']);

$app->group('/api/accounts', function (\Slim\Routing\RouteCollectorProxy $group) use ($accountController, $statementController) {
    $group->get('', [$accountController, 'index']);
    $group->post('', [$accountController, 'create'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('accounts'));
    $group->put('/{id}', [$accountController, 'update'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('accounts'));
    $group->delete('/{id}', [$accountController, 'delete'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('accounts'));
    $group->get('/{id}/statement', [$statementController, 'getAccountStatement']);
})->add($workspaceMiddleware)->add($authMiddleware);

$app->group('/api/categories', function (\Slim\Routing\RouteCollectorProxy $group) use ($categoryController) {
    $group->get('', [$categoryController, 'index']);
    $group->post('', [$categoryController, 'create'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('categories'));
    $group->put('/{id}', [$categoryController, 'update'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('categories'));
    $group->delete('/{id}', [$categoryController, 'delete'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('categories'));
})->add($workspaceMiddleware)->add($authMiddleware);

$assetRepo = new \App\Repositories\AssetRepository($db);
$billRepo = new \App\Repositories\BillRepository($db);
$txService = new \App\Services\TransactionService($assetRepo, $billRepo, $db);
$txController = new App\Controllers\TransactionController($txService);

$dashService = new \App\Services\DashboardService($db);
$dashController = new \App\Controllers\DashboardController($dashService);

$app->group('/api/transactions', function (\Slim\Routing\RouteCollectorProxy $group) use ($txController) {
    $group->get('', [$txController, 'index']);
    $group->post('', [$txController, 'create'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('transactions'));
    $group->put('/{id}', [$txController, 'update'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('transactions'));
    $group->delete('/{id}', [$txController, 'delete'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('transactions'));
    $group->post('/{id}/pay', [$txController, 'pay'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('transactions'));
})->add($workspaceMiddleware)->add($authMiddleware);

$app->get('/api/dashboard', [$dashController, 'index'])->add($workspaceMiddleware)->add($authMiddleware);

$reportController = new \App\Controllers\ReportController($txService);
$app->group('/api/reports', function (\Slim\Routing\RouteCollectorProxy $group) use ($reportController) {
    $group->get('/pdf', [$reportController, 'generatePdf']);
    $group->get('/csv', [$reportController, 'generateCsv']);
})->add($workspaceMiddleware)->add($authMiddleware);

$budgetRepo = new \App\Repositories\BudgetRepository($db);
$budgetController = new \App\Controllers\BudgetController($budgetRepo);

$app->group('/api/budgets', function (\Slim\Routing\RouteCollectorProxy $group) use ($budgetController) {
    $group->get('', [$budgetController, 'index']);
    $group->post('', [$budgetController, 'create'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('budgets'));
    $group->put('/{id}', [$budgetController, 'update'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('budgets'));
    $group->delete('/{id}', [$budgetController, 'delete'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('budgets'));
})->add($workspaceMiddleware)->add($authMiddleware);

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
})->add($workspaceMiddleware)->add($authMiddleware);

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
    $group->put('/members/{userId}/permissions', [$workspaceController, 'updateMemberPermissions']);
    
    $group->post('/invite', [$inviteController, 'generateInvite']);
    $group->get('/invite', [$inviteController, 'getInvites']);
    $group->delete('/invite/{id}', [$inviteController, 'deleteInvite']);
})->add($workspaceMiddleware)->add($authMiddleware);

$cardRepo = new \App\Repositories\CreditCardRepository($db);
$cardTxRepo = new \App\Repositories\CreditCardTransactionRepository($db);
$cardService = new \App\Services\CreditCardService($cardRepo, $cardTxRepo, $billRepo, $categoryRepo);
$cardController = new \App\Controllers\CreditCardController($cardService);

$app->group('/api/credit-cards', function (\Slim\Routing\RouteCollectorProxy $group) use ($cardController) {
    $group->get('', [$cardController, 'index']);
    $group->post('', [$cardController, 'create'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('credit_cards'));
    $group->put('/{id}', [$cardController, 'update'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('credit_cards'));
    $group->delete('/{id}', [$cardController, 'delete'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('credit_cards'));
    $group->post('/{id}/transactions', [$cardController, 'addTransaction'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('credit_cards'));
    $group->get('/{id}/transactions', [$cardController, 'transactions']);
    $group->put('/transactions/{id}', [$cardController, 'updateTransaction'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('credit_cards'));
    $group->delete('/transactions/{id}', [$cardController, 'deleteTransaction'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('credit_cards'));
    $group->post('/{id}/invoices/generate', [$cardController, 'generateInvoice'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('credit_cards'));
})->add($workspaceMiddleware)->add($authMiddleware);

$app->post('/api/workspaces/join', [$inviteController, 'joinWorkspace'])->add($authMiddleware);

$app->group('/api/system-invites', function ($group) use ($inviteController) {
    $group->get('', [$inviteController, 'getSystemInvites']);
    $group->post('/{id}/resolve', [$inviteController, 'resolveSystemInvite']);
})->add($authMiddleware);

$notificationController = new \App\Controllers\NotificationController($db);
$app->group('/api/notifications', function ($group) use ($notificationController) {
    $group->get('', [$notificationController, 'index']);
    $group->put('/read-all', [$notificationController, 'readAll']);
    $group->put('/{id}/read', [$notificationController, 'read']);
    $group->delete('', [$notificationController, 'deleteAll']);
    $group->delete('/{id}', [$notificationController, 'delete']);
})->add($authMiddleware);

$goalRepo = new \App\Repositories\GoalRepository($db);
$goalContributionRepo = new \App\Repositories\GoalContributionRepository($db);
$simulationService = new \App\Services\SimulationService($assetRepo, $billRepo, $goalRepo);
$goalService = new \App\Services\GoalService($goalRepo, $goalContributionRepo, $simulationService, $billRepo, $categoryRepo);
$goalController = new \App\Controllers\GoalController($goalService);

$app->group('/api/goals', function (\Slim\Routing\RouteCollectorProxy $group) use ($goalController) {
    $group->get('', [$goalController, 'list']);
    $group->post('', [$goalController, 'create'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('goals'));
    $group->put('/{id}', [$goalController, 'update'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('goals'));
    $group->delete('/{id}', [$goalController, 'delete'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('goals'));
    $group->post('/contributions', [$goalController, 'contribute'])->add(\App\Middleware\GatekeeperMiddleware::requireEditor('goals'));
    $group->get('/{id}/forecast', [$goalController, 'forecast']);
})->add($workspaceMiddleware)->add($authMiddleware);

// Add Error Middleware last
$app->addErrorMiddleware(true, true, true);

$app->run();
