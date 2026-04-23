# PokeVote

PokeVote is a PHP + MySQL web app for pairwise Pokemon voting with Elo rankings per category and overall.

## Features

- Anonymous left-vs-right Pokemon voting
- Category-specific Elo + overall Elo updates after each vote
- Anti-repeat pair cooldown
- Basic abuse controls (IP hash rate limits + vote cooldown)
- Leaderboard page
- Admin category management (add/deactivate)
- Pokemon import script using PokeAPI sprites

## Stack

- PHP 8.1+
- MySQL 8+
- Vanilla JS and CSS

## Quick Start (Local)

1. Copy environment template:

```powershell
Copy-Item .env.example .env
```

2. Update DB credentials in `.env`.

3. Run migrations:

```powershell
php scripts/migrate.php
```

4. Seed categories:

```powershell
php scripts/seed_categories.php
```

5. Import Pokemon (this can take a while for all available Pokemon):

```powershell
php scripts/import_pokemon.php
```

6. Start PHP built-in server:

```powershell
php -S localhost:8000 -t public
```

7. Open:

- Voting page: http://localhost:8000/
- Rankings: http://localhost:8000/rankings.php
- Admin categories: http://localhost:8000/admin/categories.php

## Production Notes (InfinityFree)

- Use your InfinityFree MySQL host/database/user/pass in `.env`.
- Set `APP_DEBUG=0` in production.
- Set a strong `APP_SECRET` and `ADMIN_KEY`.
- Upload the project files, ensuring `public` is web root if possible.
- If web root cannot be `public`, move/adjust entrypoints carefully and keep `src` inaccessible from browser.

## Environment Variables

See `.env.example` for the full list.

Important values:

- `APP_SECRET` - used for irreversible hashing of IP/user-agent
- `ADMIN_KEY` - required on admin category page
- `ELO_BASE`, `ELO_K_FACTOR` - ranking behavior
- `VOTE_COOLDOWN_SECONDS`, `IP_HOURLY_VOTE_LIMIT`, `MAX_PAIR_VOTES_PER_IP_PER_DAY` - abuse controls

## Project Layout

- `public/` web entrypoints, API, assets
- `src/` core, repositories, services
- `db/migrations/` SQL migrations
- `scripts/` CLI scripts for setup/import

## Notes

- The app expects category `overall` to exist.
- Votes update both selected category Elo and overall Elo.
- Pokemon are loaded from PokeAPI and sprite URLs are cached in DB.
