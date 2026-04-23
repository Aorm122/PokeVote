<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\AppFactory;

$app = AppFactory::make();
$config = $app['config'];

/** @var App\Repositories\CategoryRepository $categoryRepository */
$categoryRepository = $app['repositories']['category'];
/** @var App\Repositories\RatingRepository $ratingRepository */
$ratingRepository = $app['repositories']['rating'];

$categories = $categoryRepository->getActive();
$categoryId = isset($_GET['category']) ? (int) $_GET['category'] : 0;
if ($categoryId <= 0 && $categories !== []) {
    $categoryId = (int) $categories[0]['id'];
}

$selectedCategory = $categoryRepository->findById($categoryId);
if ($selectedCategory === null && $categories !== []) {
    $selectedCategory = $categories[0];
    $categoryId = (int) $selectedCategory['id'];
}

$rows = $categoryId > 0 ? $ratingRepository->getLeaderboard($categoryId, (int) $config['leaderboard_limit']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PokeVote Rankings</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
    <div class="ambient"></div>
    <main class="layout rankings-layout">
        <header class="hero">
            <h1>Leaderboard</h1>
            <p>Current Elo standings per category.</p>
            <div class="hero-links">
                <a href="/">Back To Voting</a>
                <a href="/admin/categories.php">Admin Categories</a>
            </div>
        </header>

        <section class="panel categories">
            <h2>Category</h2>
            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                    <?php $isActive = (int) $category['id'] === $categoryId; ?>
                    <a class="category-chip <?php echo $isActive ? 'active' : ''; ?>" href="/rankings.php?category=<?php echo (int) $category['id']; ?>">
                        <?php echo htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel ranking-table-wrap">
            <h2><?php echo htmlspecialchars((string) ($selectedCategory['name'] ?? 'Rankings'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <div class="table-scroll">
                <table class="ranking-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Pokemon</th>
                            <th>Elo</th>
                            <th>Votes</th>
                            <th>W</th>
                            <th>L</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($rows === []): ?>
                        <tr>
                            <td colspan="6">No ranking data yet. Import pokemon and start voting.</td>
                        </tr>
                    <?php else: ?>
                        <?php $rank = 1; ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?php echo $rank; ?></td>
                                <td class="poke-cell">
                                    <img
                                        src="<?php echo htmlspecialchars((string) $row['sprite_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-artwork-url="<?php echo htmlspecialchars((string) ($row['official_artwork_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-pokedex-id="<?php echo (int) ($row['pokedex_id'] ?? 0); ?>"
                                        alt="<?php echo htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        loading="lazy"
                                    >
                                    <span><?php echo htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td><?php echo number_format((float) $row['elo'], 2); ?></td>
                                <td><?php echo (int) $row['votes_count']; ?></td>
                                <td><?php echo (int) $row['wins_count']; ?></td>
                                <td><?php echo (int) $row['losses_count']; ?></td>
                            </tr>
                            <?php $rank++; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        (() => {
            const rows = document.querySelectorAll('.poke-cell img[data-pokedex-id]');

            rows.forEach((img) => {
                const pokedexId = Number(img.dataset.pokedexId || 0);
                const sources = [
                    img.dataset.artworkUrl || '',
                    img.getAttribute('src') || '',
                    pokedexId > 0
                        ? `https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/home/${pokedexId}.png`
                        : '',
                ].filter(Boolean);

                let index = 0;
                img.onerror = () => {
                    index += 1;
                    if (index >= sources.length) {
                        img.onerror = null;
                        img.src = '';
                        return;
                    }

                    img.src = sources[index];
                };
            });
        })();
    </script>
</body>
</html>
