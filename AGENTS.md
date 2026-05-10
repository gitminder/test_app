# AGENTS.md

## Cursor Cloud specific instructions

This is a single-file PHP reviews/guestbook application (`index.php`). No frameworks, no package managers, no external dependencies.

### Prerequisites

- **PHP 8.x CLI** with the `mbstring` extension (installed via `sudo apt-get install -y php-cli php-mbstring`).
- A writable `data/` directory in the project root (the app auto-creates `data/reviews.json` on first request).

### Running the dev server

```bash
mkdir -p data
php -S 0.0.0.0:8000 -t /workspace
```

The app is then available at `http://localhost:8000/`.

### Lint

```bash
php -l index.php
```

There is no test suite, linter config, or build step — `php -l` (syntax check) is the only available lint.

### Notes

- The app stores reviews in `data/reviews.json` (flat-file, no database).
- `data/` is not tracked by git; create it before starting the server.
- The UI is in Russian. "Отзывы" = Reviews, "Имя" = Name, "Добавить отзыв" = Add review.
