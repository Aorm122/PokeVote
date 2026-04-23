CREATE TABLE IF NOT EXISTS pokemon (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pokedex_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    sprite_url VARCHAR(255) NOT NULL,
    official_artwork_url VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_pokemon_pokedex_id (pokedex_id),
    UNIQUE KEY uq_pokemon_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL,
    name VARCHAR(120) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_categories_slug (slug),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ratings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pokemon_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    elo DECIMAL(10,4) NOT NULL DEFAULT 1500.0000,
    votes_count INT UNSIGNED NOT NULL DEFAULT 0,
    wins_count INT UNSIGNED NOT NULL DEFAULT 0,
    losses_count INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_ratings_pokemon_category (pokemon_id, category_id),
    KEY idx_ratings_category_elo (category_id, elo DESC),
    KEY idx_ratings_category_votes (category_id, votes_count DESC),
    CONSTRAINT fk_ratings_pokemon FOREIGN KEY (pokemon_id) REFERENCES pokemon(id) ON DELETE CASCADE,
    CONSTRAINT fk_ratings_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vote_pairs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    pokemon_a_id INT UNSIGNED NOT NULL,
    pokemon_b_id INT UNSIGNED NOT NULL,
    pair_key VARCHAR(50) NOT NULL,
    show_count INT UNSIGNED NOT NULL DEFAULT 0,
    vote_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_shown_at DATETIME NULL,
    last_voted_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_vote_pairs_category_pair (category_id, pair_key),
    KEY idx_vote_pairs_category_shown (category_id, last_shown_at),
    KEY idx_vote_pairs_category_votes (category_id, vote_count),
    CONSTRAINT fk_vote_pairs_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_vote_pairs_pokemon_a FOREIGN KEY (pokemon_a_id) REFERENCES pokemon(id) ON DELETE CASCADE,
    CONSTRAINT fk_vote_pairs_pokemon_b FOREIGN KEY (pokemon_b_id) REFERENCES pokemon(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS votes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    left_pokemon_id INT UNSIGNED NOT NULL,
    right_pokemon_id INT UNSIGNED NOT NULL,
    winner_pokemon_id INT UNSIGNED NOT NULL,
    loser_pokemon_id INT UNSIGNED NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    user_agent_hash CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_votes_category_created (category_id, created_at),
    KEY idx_votes_ip_created (ip_hash, created_at),
    KEY idx_votes_winner_category (winner_pokemon_id, category_id),
    CONSTRAINT fk_votes_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_votes_left_pokemon FOREIGN KEY (left_pokemon_id) REFERENCES pokemon(id) ON DELETE CASCADE,
    CONSTRAINT fk_votes_right_pokemon FOREIGN KEY (right_pokemon_id) REFERENCES pokemon(id) ON DELETE CASCADE,
    CONSTRAINT fk_votes_winner_pokemon FOREIGN KEY (winner_pokemon_id) REFERENCES pokemon(id) ON DELETE CASCADE,
    CONSTRAINT fk_votes_loser_pokemon FOREIGN KEY (loser_pokemon_id) REFERENCES pokemon(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limit_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_hash CHAR(64) NOT NULL,
    event_type VARCHAR(40) NOT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_rate_limit_ip_created (ip_hash, created_at),
    KEY idx_rate_limit_event_created (event_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
