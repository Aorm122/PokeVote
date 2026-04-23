<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\AppFactory;
use App\Core\Csrf;

$app = AppFactory::make();

/** @var App\Repositories\CategoryRepository $categoryRepository */
$categoryRepository = $app['repositories']['category'];
$categories = $categoryRepository->getActive();

$selectedCategoryId = isset($_GET['category']) ? (int) $_GET['category'] : 0;
if ($selectedCategoryId <= 0 && $categories !== []) {
    $selectedCategoryId = (int) $categories[0]['id'];
}

$selectedCategoryName = 'Category';
foreach ($categories as $category) {
    if ((int) $category['id'] === $selectedCategoryId) {
        $selectedCategoryName = (string) $category['name'];
        break;
    }
}

$csrfToken = Csrf::token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PokeVote</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
    <div class="ambient"></div>
    
    <div class="top-bar">
        <header class="top-bar-heading">
            <h1>PokeVote Arena</h1>
            <a href="/rankings.php?category=<?php echo $selectedCategoryId; ?>" class="rankings-link">View Rankings</a>
        </header>

        <section class="top-bar-categories">
            <span class="category-label">Voting in:</span>
            <div class="top-category-select">
                <?php foreach ($categories as $category): ?>
                    <?php $isActive = (int) $category['id'] === $selectedCategoryId; ?>
                    <a class="category-chip <?php echo $isActive ? 'active' : ''; ?>" href="/?category=<?php echo (int) $category['id']; ?>">
                        <?php echo htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <main class="layout-fullscreen">
        <div id="duel-feedback" class="duel-feedback" aria-live="polite"></div>

        <div id="duel-board" class="duel-board-fullscreen" data-category-id="<?php echo $selectedCategoryId; ?>" data-csrf="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <article class="poke-card fullscreen-card" data-side="left" id="left-card" tabindex="0" role="button">
                <div class="card-bg" id="left-bg"></div>
                <div class="card-overlay"></div>
                <div class="card-content">
                    <div class="sprite-shell">
                        <img id="left-sprite" src="" alt="Left Pokemon" loading="lazy">
                    </div>
                    <h3 id="left-name">Loading...</h3>
                    <div class="elo-reveal" id="left-elo-reveal">
                        <span class="elo-label">Elo Rating</span>
                        <span class="elo-score" id="left-elo">0</span>
                    </div>
                </div>
            </article>

            <div class="versus-circle">VS</div>

            <article class="poke-card fullscreen-card" data-side="right" id="right-card" tabindex="0" role="button">
                <div class="card-bg" id="right-bg"></div>
                <div class="card-overlay"></div>
                <div class="card-content">
                    <div class="sprite-shell">
                        <img id="right-sprite" src="" alt="Right Pokemon" loading="lazy">
                    </div>
                    <h3 id="right-name">Loading...</h3>
                    <div class="elo-reveal" id="right-elo-reveal">
                        <span class="elo-label">Elo Rating</span>
                        <span class="elo-score" id="right-elo">0</span>
                    </div>
                </div>
            </article>
        </div>
    </main>

    <script src="/assets/app.js" defer></script>
</body>
</html>
