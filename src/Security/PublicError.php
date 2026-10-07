<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Decide o que de uma exceção pode ir para o cliente.
 *
 * Exceções de domínio (mensagens escritas para o usuário, ex.: "Saldo insuficiente") passam como estão.
 * Falhas internas (banco, HTTP externo, erros de PHP) viram uma mensagem genérica e são registradas
 * no log/Sentry com um código de referência, que também vai para o cliente para facilitar o suporte.
 */
final class PublicError {
    public const GENERIC = 'Erro interno ao processar a solicitação. Tente novamente em instantes.';

    private static ?LoggerInterface $logger = null;

    public static function setLogger(?LoggerInterface $logger): void {
        self::$logger = $logger;
    }

    public static function message(Throwable $e): string {
        if (!self::isInternal($e)) {
            return $e->getMessage();
        }

        $reference = bin2hex(random_bytes(4));
        self::report($e, $reference);
        return self::GENERIC . " (ref. $reference)";
    }

    public static function isInternal(Throwable $e): bool {
        return $e instanceof \PDOException
            || $e instanceof \Error
            || $e instanceof \JsonException
            || $e instanceof \Psr\Http\Client\ClientExceptionInterface
            || $e instanceof \GuzzleHttp\Exception\GuzzleException
            || str_contains($e->getMessage(), 'SQLSTATE');
    }

    private static function report(Throwable $e, string $reference): void {
        $context = ['ref' => $reference, 'exception' => $e];
        if (self::$logger !== null) {
            // O logger da aplicação já encaminha para o Sentry quando SENTRY_DSN está configurado.
            self::$logger->error($e->getMessage(), $context);
            return;
        }
        error_log(sprintf('[ref %s] %s: %s', $reference, get_class($e), $e->getMessage()));
    }
}
