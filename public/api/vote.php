<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\Core\AppFactory;
use App\Core\Csrf;
use App\Core\Http;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Http::badRequest('POST method is required.');
    }

    $rawBody = file_get_contents('php://input');
    $payload = [];

    if (is_string($rawBody) && $rawBody !== '') {
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            $payload = $decoded;
        }
    }

    if ($payload === []) {
        $payload = $_POST;
    }

    $csrfToken = isset($payload['csrf']) ? (string) $payload['csrf'] : null;
    if (!Csrf::validate($csrfToken)) {
        Http::json(['success' => false, 'error' => 'Invalid CSRF token.'], 419);
    }

    $categoryId = isset($payload['category_id']) ? (int) $payload['category_id'] : 0;
    $leftPokemonId = isset($payload['left_id']) ? (int) $payload['left_id'] : 0;
    $rightPokemonId = isset($payload['right_id']) ? (int) $payload['right_id'] : 0;
    $winnerPokemonId = isset($payload['winner_id']) ? (int) $payload['winner_id'] : 0;

    if ($categoryId <= 0 || $leftPokemonId <= 0 || $rightPokemonId <= 0 || $winnerPokemonId <= 0) {
        Http::badRequest('Invalid vote payload.');
    }

    $app = AppFactory::make();

    /** @var App\Services\VoteService $voteService */
    $voteService = $app['services']['vote'];

    $ip = Http::ipAddress();
    $userAgent = Http::userAgent();

    [$isAllowed, $limitResult] = $voteService->validateRateLimit(
        $ip,
        $userAgent,
        $categoryId,
        $leftPokemonId,
        $rightPokemonId
    );

    if (!$isAllowed) {
        Http::tooManyRequests((string) $limitResult);
    }

    /** @var array{ip_hash: string, user_agent_hash: string} $hashes */
    $hashes = $limitResult;

    $result = $voteService->castVote(
        $categoryId,
        $leftPokemonId,
        $rightPokemonId,
        $winnerPokemonId,
        $hashes['ip_hash'],
        $hashes['user_agent_hash']
    );

    Http::json([
        'success' => true,
        'result' => $result,
    ]);
} catch (RuntimeException $runtimeException) {
    Http::badRequest($runtimeException->getMessage());
} catch (Throwable $throwable) {
    Http::serverError($throwable->getMessage());
}
