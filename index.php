<?php
declare(strict_types=1);

$storagePath = __DIR__ . '/data/reviews.json';
$errors = [];
$old = [
    'name' => '',
    'message' => '',
    'rating' => null,
];

if (!is_file($storagePath)) {
    file_put_contents($storagePath, "[]\n", LOCK_EX);
}

$reviews = [];
$rawJson = @file_get_contents($storagePath);
if ($rawJson !== false) {
    $decoded = json_decode($rawJson, true);
    if (is_array($decoded)) {
        $reviews = $decoded;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    $ratingRaw = $_POST['rating'] ?? null;
    $rating = ($ratingRaw !== null && $ratingRaw !== '') ? (int)$ratingRaw : null;
    $old['name'] = $name;
    $old['message'] = $message;
    $old['rating'] = $rating;

    if ($name === '') {
        $errors[] = 'Введите имя.';
    } elseif (mb_strlen($name) > 100) {
        $errors[] = 'Имя не должно быть длиннее 100 символов.';
    }

    if ($message === '') {
        $errors[] = 'Введите текст отзыва.';
    } elseif (mb_strlen($message) > 1000) {
        $errors[] = 'Отзыв не должен быть длиннее 1000 символов.';
    }

    if ($rating === null) {
        $errors[] = 'Выберите оценку.';
    } elseif ($rating < -2 || $rating > 2) {
        $errors[] = 'Оценка должна быть от −2 до +2.';
    }

    if ($errors === []) {
        array_unshift($reviews, [
            'name' => $name,
            'message' => $message,
            'rating' => $rating,
            'created_at' => date('c'),
        ]);

        $encoded = json_encode($reviews, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            $errors[] = 'Не удалось подготовить данные для сохранения.';
        } else {
            $saved = @file_put_contents($storagePath, $encoded . PHP_EOL, LOCK_EX);
            if ($saved === false) {
                $errors[] = 'Не удалось сохранить отзыв.';
            } else {
                header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?ok=1');
                exit;
            }
        }
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отзывы</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
            line-height: 1.4;
        }
        form, .review {
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #fff;
        }
        label {
            display: block;
            margin-bottom: 0.75rem;
        }
        input[type="text"], textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 0.5rem;
            margin-top: 0.25rem;
        }
        button {
            padding: 0.55rem 1rem;
            border: 0;
            border-radius: 6px;
            background: #1f6feb;
            color: #fff;
            cursor: pointer;
        }
        .errors {
            border: 1px solid #f5c2c7;
            background: #f8d7da;
            color: #842029;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }
        .success {
            border: 1px solid #badbcc;
            background: #d1e7dd;
            color: #0f5132;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }
        .rating-group {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.25rem;
        }
        .rating-group label {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-bottom: 0;
            cursor: pointer;
        }
        .rating-badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .rating-neg2 { background: #f8d7da; color: #842029; }
        .rating-neg1 { background: #ffe5d0; color: #984c0c; }
        .rating-0    { background: #e2e3e5; color: #41464b; }
        .rating-pos1 { background: #d1e7dd; color: #0f5132; }
        .rating-pos2 { background: #a3cfbb; color: #0a3622; }
        .meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <h1>Отзывы</h1>

    <?php if (isset($_GET['ok'])): ?>
        <div class="success">Отзыв успешно добавлен.</div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div class="errors">
            <strong>Проверьте форму:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <label>
            Имя
            <input type="text" name="name" maxlength="100" required value="<?= e($old['name']) ?>">
        </label>
        <label>
            Отзыв
            <textarea name="message" rows="5" maxlength="1000" required><?= e($old['message']) ?></textarea>
        </label>
        <div style="margin-bottom: 0.75rem;">
            <span>Оценка</span>
            <div class="rating-group">
                <?php foreach ([-2, -1, 0, 1, 2] as $val): ?>
                    <label>
                        <input type="radio" name="rating" value="<?= $val ?>"<?= $old['rating'] === $val ? ' checked' : '' ?>>
                        <?= $val > 0 ? '+' . $val : $val ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <button type="submit">Добавить отзыв</button>
    </form>

    <?php if ($reviews === []): ?>
        <p>Пока отзывов нет. Оставьте первый.</p>
    <?php else: ?>
        <?php foreach ($reviews as $review): ?>
            <article class="review">
                <div class="meta">
                    <strong><?= e((string)($review['name'] ?? 'Аноним')) ?></strong>
                    <?php if (isset($review['rating'])): ?>
                        <?php
                            $r = (int)$review['rating'];
                            $cls = $r < -1 ? 'neg2' : ($r < 0 ? 'neg1' : ($r === 0 ? '0' : ($r === 1 ? 'pos1' : 'pos2')));
                        ?>
                        <span class="rating-badge rating-<?= $cls ?>"><?= $r > 0 ? '+' . $r : $r ?></span>
                    <?php endif; ?>
                    <?php if (!empty($review['created_at'])): ?>
                        — <?= e((string)$review['created_at']) ?>
                    <?php endif; ?>
                </div>
                <div><?= nl2br(e((string)($review['message'] ?? ''))) ?></div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
