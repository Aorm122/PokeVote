<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;
use App\Core\Env;
use App\Repositories\CategoryRepository;
use App\Repositories\PokemonRepository;
use App\Repositories\RatingRepository;

$pdo = Database::connection();
$pokemonRepository = new PokemonRepository($pdo);
$categoryRepository = new CategoryRepository($pdo);
$ratingRepository = new RatingRepository($pdo);

$baseElo = Env::getFloat('ELO_BASE', 1500.0);
$apiBase = 'https://pokeapi.co/api/v2';
$pageSize = 200;
$offset = 0;
$totalImported = 0;

while (true) {
    $url = sprintf('%s/pokemon?limit=%d&offset=%d', $apiBase, $pageSize, $offset);
    $page = fetchJson($url);

    if (!isset($page['results']) || !is_array($page['results']) || $page['results'] === []) {
        break;
    }

    foreach ($page['results'] as $row) {
        if (!isset($row['url']) || !is_string($row['url'])) {
            continue;
        }

        $details = fetchJson($row['url']);

        $pokedexId = isset($details['id']) ? (int) $details['id'] : extractPokedexIdFromUrl($row['url']);
        if ($pokedexId <= 0) {
            continue;
        }

        $rawName = isset($details['name']) ? (string) $details['name'] : (string) ($row['name'] ?? 'pokemon-' . $pokedexId);
        $name = ucfirst(str_replace('-', ' ', $rawName));

        $sprite = firstNonEmptyString([
            getNestedString($details, ['sprites', 'front_default']),
            getNestedString($details, ['sprites', 'other', 'showdown', 'front_default']),
            getNestedString($details, ['sprites', 'other', 'home', 'front_default']),
            sprintf('https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/%d.png', $pokedexId),
        ]);

        $artwork = firstNonEmptyString([
            getNestedString($details, ['sprites', 'other', 'official-artwork', 'front_default']),
            getNestedString($details, ['sprites', 'other', 'home', 'front_default']),
            sprintf('https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/home/%d.png', $pokedexId),
            $sprite,
        ]);

        $pokemonRepository->upsertByPokedexId($pokedexId, $name, $sprite, $artwork);
        $totalImported++;
    }

    echo "Imported batch offset={$offset}, running total={$totalImported}\n";

    if (!isset($page['next']) || $page['next'] === null) {
        break;
    }

    $offset += $pageSize;
}

$categories = $categoryRepository->getAll();
foreach ($categories as $category) {
    $ratingRepository->ensureRatingsForCategory((int) $category['id'], $baseElo);
}

echo "Pokemon import complete. Total processed={$totalImported}\n";

function fetchJson(string $url): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n",
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        throw new RuntimeException('Failed to fetch URL: ' . $url);
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        throw new RuntimeException('Failed to decode JSON from URL: ' . $url);
    }

    return $decoded;
}

function extractPokedexIdFromUrl(string $url): int
{
    $trimmed = trim($url);
    if ($trimmed === '') {
        return 0;
    }

    if (preg_match('#/pokemon/(\d+)/?$#', $trimmed, $matches) !== 1) {
        return 0;
    }

    return (int) $matches[1];
}

function getNestedString(array $source, array $keys): string
{
    $current = $source;

    foreach ($keys as $key) {
        if (!is_array($current) || !array_key_exists($key, $current)) {
            return '';
        }

        $current = $current[$key];
    }

    return is_string($current) ? trim($current) : '';
}

function firstNonEmptyString(array $values): string
{
    foreach ($values as $value) {
        if (!is_string($value)) {
            continue;
        }

        $normalized = trim($value);
        if ($normalized !== '') {
            return $normalized;
        }
    }

    return '';
}
