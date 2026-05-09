<?php
declare(strict_types=1);

$storagePath = __DIR__ . '/data/reviews.json';
$errors = [];
$old = [
    'name' => '',
    'message' => '',
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
    $old['name'] = $name;
    $old['message'] = $message;

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

    if ($errors === []) {
        array_unshift($reviews, [
            'name' => $name,
            'message' => $message,
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
        <button type="submit">Добавить отзыв</button>
    </form>

    <?php if ($reviews === []): ?>
        <p>Пока отзывов нет. Оставьте первый.</p>
    <?php else: ?>
        <?php foreach ($reviews as $review): ?>
            <article class="review">
                <div class="meta">
                    <strong><?= e((string)($review['name'] ?? 'Аноним')) ?></strong>
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
