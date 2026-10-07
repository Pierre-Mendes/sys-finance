<?php

namespace App\Telegram;

use App\DTO\TransactionDTO;
use App\Repositories\AccountRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ReportRepository;
use App\Repositories\TelegramLinkRepository;
use App\Security\PublicError;
use App\Services\CategoryService;
use App\Services\TransactionService;
use App\Services\WorkspaceService;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Conversa com o bot do Telegram: vincular a conta, lançar receitas/despesas em texto livre e consultar.
 *
 * handle() recebe o "update" do Telegram e devolve a mensagem de resposta (ou null), sem fazer chamadas de rede:
 * o webhook devolve a resposta no próprio corpo HTTP (método sendMessage), então o bot funciona mesmo sem
 * saída para a internet e é testável sem mocks de HTTP.
 */
class TelegramBot {
    public const LINK_CODE_TTL_MINUTES = 15;
    private const UNDO_WINDOW_HOURS = 24;

    /** Categoria sugerida por palavra-chave quando a mensagem não cita uma categoria existente. */
    private const EXPENSE_KEYWORDS = [
        'Alimentação' => ['mercado', 'supermercado', 'padaria', 'feira', 'acougue', 'hortifruti', 'ifood', 'restaurante', 'lanche',
            'almoco', 'jantar', 'cafe', 'pizza', 'hamburguer', 'delivery', 'rappi', 'sorvete', 'acai', 'marmita'],
        'Transporte' => ['uber', 'taxi', 'onibus', 'metro', 'gasolina', 'combustivel', 'posto', 'estacionamento', 'pedagio',
            'etanol', 'alcool', 'diesel', 'passagem', 'bilhete', 'oficina', 'mecanico', 'ipva'],
        'Moradia' => ['aluguel', 'condominio', 'luz', 'energia', 'agua', 'gas', 'internet', 'iptu', 'reforma', 'faxina', 'diarista'],
        'Saúde' => ['farmacia', 'remedio', 'medico', 'consulta', 'exame', 'dentista', 'hospital', 'academia', 'psicologo'],
        'Lazer' => ['cinema', 'netflix', 'spotify', 'show', 'viagem', 'bar', 'cerveja', 'streaming', 'jogo', 'hotel', 'disney', 'prime'],
        'Educação' => ['escola', 'curso', 'faculdade', 'livro', 'mensalidade', 'material', 'udemy'],
        'Compras' => ['roupa', 'shopping', 'amazon', 'shopee', 'magalu', 'presente', 'tenis', 'eletronico'],
    ];
    private const INCOME_KEYWORDS = [
        'Salário' => ['salario', 'pagamento', 'holerite', 'adiantamento', 'decimo'],
        'Freelance' => ['freela', 'freelance', 'servico', 'projeto', 'bico'],
        'Rendimentos' => ['rendimento', 'rendimentos', 'dividendos', 'juros', 'cdb', 'poupanca'],
        'Reembolsos' => ['reembolso', 'reembolsado', 'estorno', 'cashback', 'devolucao'],
        'Vendas' => ['venda', 'vendi'],
    ];

    /** Bancos/carteiras comuns: citados na mensagem viram a conta (criada na hora se ainda não existir). */
    private const KNOWN_ACCOUNTS = [
        'nubank' => 'Nubank', 'nu' => 'Nubank', 'inter' => 'Inter', 'itau' => 'Itaú', 'bradesco' => 'Bradesco',
        'santander' => 'Santander', 'caixa' => 'Caixa', 'banco do brasil' => 'Banco do Brasil', 'bb' => 'Banco do Brasil',
        'c6' => 'C6 Bank', 'picpay' => 'PicPay', 'mercado pago' => 'Mercado Pago', 'neon' => 'Neon', 'next' => 'Next',
        'sicoob' => 'Sicoob', 'sicredi' => 'Sicredi', 'pagbank' => 'PagBank', 'btg' => 'BTG', 'xp' => 'XP',
        'dinheiro' => 'Dinheiro', 'carteira' => 'Carteira',
    ];

    private TelegramLinkRepository $links;
    private TransactionService $transactions;
    private AccountRepository $accounts;
    private CategoryRepository $categories;
    private ReportRepository $reports;
    private WorkspaceService $workspaces;
    private DateTimeZone $timezone;
    /** @var callable(): DateTimeImmutable */
    private $clock;

    public function __construct(
        TelegramLinkRepository $links,
        TransactionService $transactions,
        AccountRepository $accounts,
        CategoryRepository $categories,
        ReportRepository $reports,
        WorkspaceService $workspaces,
        ?DateTimeZone $timezone = null,
        ?callable $clock = null
    ) {
        $this->links = $links;
        $this->transactions = $transactions;
        $this->accounts = $accounts;
        $this->categories = $categories;
        $this->reports = $reports;
        $this->workspaces = $workspaces;
        $this->timezone = $timezone ?? new DateTimeZone(getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo');
        $this->clock = $clock ?? fn () => new DateTimeImmutable('now', $this->timezone);
    }

    /** Código de uso único para vincular o Telegram (Configurações → Telegram). */
    public function createLinkCode(int $userId, int $workspaceId): array {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sem 0/O/1/I para dar para digitar
        $code = '';
        for ($i = 0; $i < 10; $i++) $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        $expires = $this->now()->modify('+' . self::LINK_CODE_TTL_MINUTES . ' minutes');
        $this->links->saveLinkCode($userId, $workspaceId, $code, $expires->format('Y-m-d H:i:s'));
        return ['code' => $code, 'expiresAt' => $expires->format(DATE_ATOM)];
    }

    /** @return array{chat_id: int, text: string, parse_mode: string}|null */
    public function handle(array $update): ?array {
        $message = $update['message'] ?? null;
        if (!is_array($message) || !isset($message['chat']['id'])) return null;

        $chatId = (int) $message['chat']['id'];
        $text = trim((string) ($message['text'] ?? ''));
        if (($message['chat']['type'] ?? 'private') !== 'private') {
            return $this->reply($chatId, 'Por segurança eu só converso em chat privado. Abra uma conversa direta comigo.');
        }
        if ($text === '') {
            return $this->reply($chatId, 'Por enquanto eu entendo só mensagens de texto. Ex.: <code>mercado 120 nubank</code>');
        }

        [$command, $args] = self::splitCommand($text);

        if ($command === '/start' && $args !== '') {
            return $this->linkChat($chatId, strtoupper($args), $message['from']['username'] ?? null);
        }

        $link = $this->links->findByChatId($chatId);
        if (!$link) {
            return $this->reply($chatId,
                "Olá! Para usar o bot, vincule sua conta:\n\n" .
                "1. Abra o sistema → <b>Configurações</b> → <b>Telegram</b>\n" .
                "2. Toque em <b>Conectar Telegram</b> (ou envie aqui o código mostrado: <code>/start CÓDIGO</code>)."
            );
        }

        $userId = (int) $link['user_id'];
        $workspace = $this->currentWorkspace($link);
        if ($workspace === null) {
            return $this->reply($chatId, 'Você não participa de nenhum espaço no momento. Entre no sistema para criar um.');
        }

        try {
            return match ($command) {
                '/start', '/ajuda', '/help' => $this->reply($chatId, $this->helpText($workspace['name'])),
                '/saldo' => $this->balances($chatId, $workspace),
                '/mes', '/resumo' => $this->monthSummary($chatId, $workspace),
                '/contas' => $this->upcomingBills($chatId, $workspace),
                '/ultimos' => $this->recent($chatId, $workspace),
                '/desfazer' => $this->undo($chatId, $userId, $link, $workspace),
                '/espaco', '/espacos' => $this->switchWorkspace($chatId, $userId, $workspace, $args),
                '/desvincular' => $this->unlink($chatId, $userId),
                '/gasto', '/despesa' => $this->addTransaction($chatId, $userId, $workspace, $args, 'bill'),
                '/receita' => $this->addTransaction($chatId, $userId, $workspace, $args, 'asset'),
                null => $this->addTransaction($chatId, $userId, $workspace, $text, null),
                default => $this->reply($chatId, "Não conheço esse comando. Envie /ajuda para ver o que eu sei fazer."),
            };
        } catch (\Throwable $e) {
            return $this->reply($chatId, '⚠️ Não consegui concluir agora: ' . self::e(PublicError::message($e)));
        }
    }

    private function linkChat(int $chatId, string $code, ?string $username): array {
        $link = preg_match('/^[A-Z0-9]{6,16}$/', $code) ? $this->links->findByValidCode($code, $this->now()->format('Y-m-d H:i:s')) : null;
        if (!$link) {
            return $this->reply($chatId, 'Código inválido ou expirado. Gere outro em <b>Configurações → Telegram</b> no sistema.');
        }
        $userId = (int) $link['user_id'];
        $this->links->link($userId, $chatId, $username ? substr($username, 0, 64) : null, $this->now()->format('Y-m-d H:i:s'));
        $workspace = $this->currentWorkspace($this->links->findByUserId($userId) ?? $link);
        $name = $this->links->firstName($userId);

        return $this->reply($chatId,
            '✅ Pronto' . ($name !== '' ? ', ' . self::e($name) : '') . "! Telegram vinculado.\n\n" .
            $this->helpText($workspace['name'] ?? '')
        );
    }

    private function helpText(string $workspaceName): string {
        return "<b>Lançar</b> — escreva como numa conversa:\n" .
            "• <code>mercado 120 nubank</code>\n" .
            "• <code>gastei 35,90 no almoço ontem</code>\n" .
            "• <code>recebi 3000 salário</code>  (ou <code>+3000 salário</code>)\n" .
            "• <code>uber 25 05/10</code>  (data dd/mm)\n" .
            "Cite o nome da conta para escolher onde lançar.\n\n" .
            "<b>Consultar</b>\n" .
            "/saldo — saldo das contas\n" .
            "/mes — receitas, despesas e maiores gastos do mês\n" .
            "/contas — contas a pagar nos próximos 7 dias\n" .
            "/ultimos — últimos lançamentos\n\n" .
            "<b>Outros</b>\n" .
            "/desfazer — apaga o último lançamento feito aqui\n" .
            "/espaco — trocar de espaço (workspace)\n" .
            "/desvincular — desconectar este Telegram" .
            ($workspaceName !== '' ? "\n\nEspaço atual: <b>" . self::e($workspaceName) . '</b>' : '');
    }

    private function addTransaction(int $chatId, int $userId, array $workspace, string $text, ?string $forcedType): array {
        $parsed = MessageParser::parse($text, $this->today(), $forcedType);
        if ($parsed === null) {
            return $this->reply($chatId, "Não encontrei um valor nessa mensagem. Tente assim: <code>mercado 120</code> ou <code>recebi 3000 salário</code>.\n/ajuda mostra tudo que eu sei fazer.");
        }
        if (!$this->workspaces->canEdit($userId, $workspace['id'], 'transactions')) {
            return $this->reply($chatId, 'Você só tem permissão de leitura de lançamentos em <b>' . self::e($workspace['name']) . '</b>.');
        }

        $normalizedText = ' ' . MessageParser::normalize($text) . ' ';
        $accountName = $this->matchAccount($workspace['id'], $normalizedText);
        $categoryName = $this->matchCategory($workspace['id'], $normalizedText, $parsed['type']);
        $title = MessageParser::cleanTitle($parsed['text'], isset($accountName['mentioned']) ? [$accountName['mentioned']] : []);
        if ($title === '') $title = $categoryName;

        $created = $this->transactions->create($workspace['id'], new TransactionDTO([
            'type' => $parsed['type'],
            'title' => mb_substr($title, 0, 100),
            'amount' => $parsed['amount'],
            'date' => $parsed['date'],
            'accountName' => $accountName['name'],
            'categoryName' => $categoryName,
            'status' => 'PAID',
            'description' => 'Lançado pelo Telegram',
        ]), $userId);
        $this->links->setLastTransaction($userId, $parsed['type'], (int) $created->getId(), $this->now()->format('Y-m-d H:i:s'));

        $isIncome = $parsed['type'] === 'asset';
        $date = $parsed['date'] === $this->today()->format('Y-m-d') ? 'hoje' : date('d/m/Y', strtotime($parsed['date']));
        $hint = $accountName['matched'] || count($this->accounts->findAllByWorkspaceId($workspace['id'])) <= 1
            ? '' : "\n<i>Para outra conta, inclua o nome dela na mensagem.</i>";

        return $this->reply($chatId,
            ($isIncome ? '💰 <b>Receita registrada</b>' : '💸 <b>Despesa registrada</b>') . "\n" .
            self::e($title) . ' — <b>' . self::money($parsed['amount']) . "</b>\n" .
            'Conta: ' . self::e($accountName['name']) . ' · Categoria: ' . self::e($categoryName) . ' · ' . $date . "\n\n" .
            'Errou? /desfazer' . $hint
        );
    }

    /** @return array{name: string, matched: bool, mentioned?: string} conta citada no texto (nome mais longo) ou a padrão */
    private function matchAccount(int $workspaceId, string $normalizedText): array {
        $accounts = $this->accounts->findAllByWorkspaceId($workspaceId);
        $best = null;
        foreach ($accounts as $account) {
            $name = $account->getAccountName();
            if (self::containsWords($normalizedText, $name) && ($best === null || mb_strlen($name) > mb_strlen($best))) {
                $best = $name;
            }
        }
        if ($best !== null) return ['name' => $best, 'matched' => true, 'mentioned' => $best];

        $known = null;
        foreach (self::KNOWN_ACCOUNTS as $alias => $display) {
            if (self::containsWords($normalizedText, $alias) && ($known === null || mb_strlen($alias) > mb_strlen($known[0]))) {
                $known = [$alias, $display];
            }
        }
        if ($known !== null) return ['name' => $known[1], 'matched' => true, 'mentioned' => $known[0]];
        if (!$accounts) return ['name' => 'Carteira', 'matched' => false];

        // Sem conta citada: a de dinheiro/carteira se houver, senão a primeira em ordem alfabética.
        foreach ($accounts as $account) {
            if (preg_match('/^(carteira|dinheiro|caixa)/', MessageParser::normalize($account->getAccountName()))) {
                return ['name' => $account->getAccountName(), 'matched' => false];
            }
        }
        return ['name' => $accounts[0]->getAccountName(), 'matched' => false];
    }

    private function matchCategory(int $workspaceId, string $normalizedText, string $type): string {
        $level = CategoryService::levelForType($type);
        $best = null;
        foreach ($this->categories->findAllByWorkspaceIdAndLevel($workspaceId, $level) as $category) {
            $name = $category->getCategoryName();
            if (self::containsWords($normalizedText, $name) && ($best === null || mb_strlen($name) > mb_strlen($best))) {
                $best = $name;
            }
        }
        if ($best !== null) return $best;

        foreach ($type === 'asset' ? self::INCOME_KEYWORDS : self::EXPENSE_KEYWORDS as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalizedText, ' ' . $keyword . ' ')) return $category;
            }
        }
        return $type === 'asset' ? 'Outras receitas' : 'Outros';
    }

    private static function containsWords(string $normalizedText, string $name): bool {
        $needle = trim(MessageParser::normalize($name));
        if ($needle === '') return false;
        return (bool) preg_match('/(?<![\pL\pN])' . preg_quote($needle, '/') . '(?![\pL\pN])/u', $normalizedText);
    }

    private function balances(int $chatId, array $workspace): array {
        $rows = $this->links->accountBalances($workspace['id']);
        if (!$rows) return $this->reply($chatId, 'Nenhuma conta cadastrada ainda. Lance algo (ex.: <code>mercado 50</code>) e eu crio a conta "Carteira".');

        $lines = array_map(fn ($r) => '• ' . self::e($r['name']) . ': <b>' . self::money($r['balance']) . '</b>', $rows);
        $total = array_sum(array_column($rows, 'balance'));
        return $this->reply($chatId, '🏦 <b>Saldo — ' . self::e($workspace['name']) . "</b>\n" . implode("\n", $lines) . "\n\nTotal: <b>" . self::money($total) . '</b>');
    }

    private function monthSummary(int $chatId, array $workspace): array {
        $today = $this->today();
        $rows = $this->reports->paidBetween($workspace['id'], $today->format('Y-m-01'), $today->format('Y-m-d'));
        $income = 0.0;
        $expense = 0.0;
        $byCategory = [];
        foreach ($rows as $r) {
            if ($r['type'] === 'asset') {
                $income += $r['amount'];
            } else {
                $expense += $r['amount'];
                $byCategory[$r['category']] = ($byCategory[$r['category']] ?? 0) + $r['amount'];
            }
        }
        arsort($byCategory);
        $top = array_slice($byCategory, 0, 5, true);

        $months = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
        $text = '📊 <b>' . ucfirst($months[(int) $today->format('n') - 1]) . ' até hoje — ' . self::e($workspace['name']) . "</b>\n" .
            'Receitas: <b>' . self::money($income) . "</b>\n" .
            'Despesas: <b>' . self::money($expense) . "</b>\n" .
            'Resultado: <b>' . self::money($income - $expense) . '</b>';
        if ($top) {
            $text .= "\n\n<b>Maiores gastos</b>\n";
            foreach ($top as $category => $amount) {
                $share = $expense > 0 ? round($amount / $expense * 100) : 0;
                $text .= '• ' . self::e($category) . ': ' . self::money($amount) . " ({$share}%)\n";
            }
        }
        return $this->reply($chatId, rtrim($text));
    }

    private function upcomingBills(int $chatId, array $workspace): array {
        $today = $this->today()->format('Y-m-d');
        $bills = array_filter(
            $this->reports->pendingUntil($workspace['id'], $this->today()->modify('+7 days')->format('Y-m-d')),
            fn ($r) => $r['type'] === 'bill'
        );
        if (!$bills) return $this->reply($chatId, '🎉 Nenhuma conta a pagar nos próximos 7 dias.');

        usort($bills, fn ($a, $b) => $a['date'] <=> $b['date']);
        $lines = [];
        foreach (array_slice($bills, 0, 15) as $b) {
            $when = $b['date'] < $today ? '⚠️ atrasada desde ' . date('d/m', strtotime($b['date']))
                : ($b['date'] === $today ? '⏰ vence hoje' : 'vence ' . date('d/m', strtotime($b['date'])));
            $lines[] = '• ' . self::e($b['title']) . ' — <b>' . self::money($b['amount']) . '</b> (' . $when . ')';
        }
        $total = array_sum(array_column($bills, 'amount'));
        return $this->reply($chatId, "🧾 <b>Contas a pagar (7 dias)</b>\n" . implode("\n", $lines) . "\n\nTotal: <b>" . self::money($total) . "</b>\nPague pelo app para atualizar o saldo.");
    }

    private function recent(int $chatId, array $workspace): array {
        $rows = $this->links->recentTransactions($workspace['id'], 5);
        if (!$rows) return $this->reply($chatId, 'Nenhum lançamento ainda.');
        $lines = array_map(fn ($r) => ($r['type'] === 'asset' ? '🟢 +' : '🔴 -') . self::money($r['amount']) . ' · ' .
            self::e(html_entity_decode($r['title'], ENT_QUOTES)) . ' · ' . date('d/m', strtotime($r['date'])) .
            ($r['status'] === 'PENDING' ? ' (pendente)' : ''), $rows);
        return $this->reply($chatId, "🕒 <b>Últimos lançamentos</b>\n" . implode("\n", $lines));
    }

    private function undo(int $chatId, int $userId, array $link, array $workspace): array {
        $at = $link['last_tx_at'] ? new DateTimeImmutable((string) $link['last_tx_at'], $this->timezone) : null;
        if (!$link['last_tx_id'] || !$at || $at < $this->now()->modify('-' . self::UNDO_WINDOW_HOURS . ' hours')) {
            return $this->reply($chatId, 'Não há lançamento recente feito pelo Telegram para desfazer (vale por 24h). Corrija pelo app.');
        }
        if (!$this->workspaces->canEdit($userId, $workspace['id'], 'transactions')) {
            return $this->reply($chatId, 'Você não tem permissão para apagar lançamentos neste espaço.');
        }
        try {
            $this->transactions->delete((int) $link['last_tx_id'], $workspace['id'], (string) $link['last_tx_type']);
        } catch (\Exception $e) {
            $this->links->setLastTransaction($userId, null, null, null);
            return $this->reply($chatId, 'Esse lançamento não existe mais (ou é de outro espaço). Nada foi apagado.');
        }
        $this->links->setLastTransaction($userId, null, null, null);
        return $this->reply($chatId, '↩️ Último lançamento apagado. O saldo da conta já foi atualizado.');
    }

    private function switchWorkspace(int $chatId, int $userId, array $current, string $args): array {
        $all = $this->links->workspacesOf($userId);
        if ($args !== '' && ctype_digit($args) && isset($all[(int) $args - 1])) {
            $target = $all[(int) $args - 1];
            $this->links->setWorkspace($userId, $target['id']);
            return $this->reply($chatId, '🔁 Agora estou usando o espaço <b>' . self::e($target['name']) . '</b>.');
        }
        $lines = [];
        foreach ($all as $i => $w) {
            $lines[] = ($i + 1) . '. ' . self::e($w['name']) . ($w['id'] === $current['id'] ? ' ✅' : '');
        }
        return $this->reply($chatId, "<b>Seus espaços</b>\n" . implode("\n", $lines) . "\n\nPara trocar envie <code>/espaco NÚMERO</code>, ex.: <code>/espaco 2</code>");
    }

    private function unlink(int $chatId, int $userId): array {
        $this->links->unlink($userId);
        return $this->reply($chatId, 'Telegram desvinculado. Para usar de novo, gere um código em Configurações → Telegram.');
    }

    /** Espaço salvo no vínculo, se o usuário ainda participa dele; senão o primeiro dele. */
    private function currentWorkspace(array $link): ?array {
        $all = $this->links->workspacesOf((int) $link['user_id']);
        foreach ($all as $w) {
            if ($w['id'] === (int) $link['workspace_id']) return $w;
        }
        if (!$all) return null;
        $this->links->setWorkspace((int) $link['user_id'], $all[0]['id']);
        return $all[0];
    }

    /** @return array{0: ?string, 1: string} ["/comando", "argumentos"] ou [null, texto] */
    public static function splitCommand(string $text): array {
        if (!str_starts_with($text, '/')) return [null, $text];
        $parts = preg_split('/\s+/', $text, 2);
        $command = strtolower(preg_replace('/@\w+$/', '', $parts[0])); // "/saldo@MeuBot" em grupos/menus
        return [$command, trim($parts[1] ?? '')];
    }

    private function reply(int $chatId, string $html): array {
        return ['chat_id' => $chatId, 'text' => $html, 'parse_mode' => 'HTML', 'disable_web_page_preview' => true];
    }

    private function now(): DateTimeImmutable {
        return ($this->clock)();
    }

    private function today(): DateTimeImmutable {
        return $this->now()->setTime(0, 0);
    }

    public static function money(float $value): string {
        return ($value < 0 ? '-' : '') . 'R$ ' . number_format(abs($value), 2, ',', '.');
    }

    private static function e(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
