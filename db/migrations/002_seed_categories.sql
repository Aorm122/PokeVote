INSERT IGNORE INTO categories (slug, name, is_active, created_at, updated_at)
VALUES
    ('overall', 'Overall', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
    ('cuteness', 'Cuteness', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
    ('coolest', 'Coolest', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
    ('hottest', 'Hottest', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());
