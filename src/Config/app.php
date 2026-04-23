<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'app_name' => Env::get('APP_NAME', 'PokeVote'),
    'app_env' => Env::get('APP_ENV', 'development'),
    'app_debug' => Env::get('APP_DEBUG', '1') === '1',
    'admin_key' => Env::get('ADMIN_KEY', 'change-this-admin-key'),
    'elo_base' => Env::getFloat('ELO_BASE', 1500.0),
    'elo_k' => Env::getFloat('ELO_K_FACTOR', 32.0),
    'overall_slug' => Env::get('OVERALL_CATEGORY_SLUG', 'overall'),
    'vote_cooldown_seconds' => Env::getInt('VOTE_COOLDOWN_SECONDS', 2),
    'pair_cooldown_seconds' => Env::getInt('PAIR_COOLDOWN_SECONDS', 900),
    'ip_hourly_vote_limit' => Env::getInt('IP_HOURLY_VOTE_LIMIT', 120),
    'max_pair_votes_per_ip_per_day' => Env::getInt('MAX_PAIR_VOTES_PER_IP_PER_DAY', 4),
    'leaderboard_limit' => Env::getInt('LEADERBOARD_LIMIT', 200),
];
