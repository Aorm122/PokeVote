<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

use App\Core\AppFactory;
use App\Core\Csrf;

$app = AppFactory::make();
$config = $app['config'];

/** @var App\Repositories\CategoryRepository $categoryRepository */
$categoryRepository = $app['repositories']['category'];
/** @var App\Services\CategoryService $categoryService */
$categoryService = $app['services']['category'];

$flashMessage = null;
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = isset($_POST['csrf']) ? (string) $_POST['csrf'] : null;

    if (!Csrf::validate($csrf)) {
        $errorMessage = 'Invalid CSRF token.';
    } else {
        $adminKey = isset($_POST['admin_key']) ? trim((string) $_POST['admin_key']) : '';

        if ($adminKey !== (string) $config['admin_key']) {
            $errorMessage = 'Invalid admin key.';
        } else {
            $action = (string) ($_POST['action'] ?? '');

            if ($action === 'create') {
                $name = trim((string) ($_POST['name'] ?? ''));
                $slug = trim((string) ($_POST['slug'] ?? ''));

                if ($name === '') {
                    $errorMessage = 'Category name is required.';
                } else {
                    $categoryService->addCategory($name, $slug !== '' ? $slug : null);
                    $flashMessage = 'Category created and ratings initialized.';
                }
            }

            if ($action === 'toggle') {
                $categoryId = (int) ($_POST['category_id'] ?? 0);
                $nextState = (int) ($_POST['next_state'] ?? 0) === 1;

                if ($categoryId <= 0) {
                    $errorMessage = 'Invalid category.';
                } else {
                    $categoryRepository->setActive($categoryId, $nextState);
                    $flashMessage = 'Category state updated.';
                }
            }
        }
    }
}

$categories = $categoryRepository->getAll();
$csrfToken = Csrf::token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PokeVote Admin Categories</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
    <div class="ambient"></div>
    <main class="layout admin-layout">
        <header class="hero">
            <h1>Category Admin</h1>
            <p>Create and manage vote categories.</p>
            <div class="hero-links">
                <a href="/">Back To Voting</a>
                <a href="/rankings.php">View Rankings</a>
            </div>
        </header>

        <?php if ($flashMessage !== null): ?>
            <div class="panel notice success"><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($errorMessage !== null): ?>
            <div class="panel notice error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="panel admin-form-wrap">
            <h2>Create Category</h2>
            <form method="post" class="admin-form">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="create">

                <label>
                    Admin Key
                    <input type="password" name="admin_key" required>
                </label>

                <label>
                    Name
                    <input type="text" name="name" placeholder="Most Memeable" required>
                </label>

                <label>
                    Slug (optional)
                    <input type="text" name="slug" placeholder="most-memeable">
                </label>

                <button type="submit">Create Category</button>
            </form>
        </section>

        <section class="panel ranking-table-wrap">
            <h2>Existing Categories</h2>
            <div class="table-scroll">
                <table class="ranking-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo (int) $category['id']; ?></td>
                            <td><?php echo htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $category['slug'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int) $category['is_active'] === 1 ? 'Active' : 'Inactive'; ?></td>
                            <td>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="category_id" value="<?php echo (int) $category['id']; ?>">
                                    <input type="hidden" name="next_state" value="<?php echo (int) $category['is_active'] === 1 ? '0' : '1'; ?>">

                                    <input type="password" name="admin_key" placeholder="Admin key" required>
                                    <button type="submit">
                                        <?php echo (int) $category['is_active'] === 1 ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
