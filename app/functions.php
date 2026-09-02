<?php

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim($GLOBALS['config']['base_url'] ?? '', '/');
    $path = ltrim($path, '/');
    return $path === '' ? ($base ?: '/') : $base . '/' . $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!$token || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Phiên làm việc không hợp lệ. Hãy tải lại trang và thử lại.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $items;
}

function money(int|float|string|null $amount): string
{
    return '$' . number_format((int)$amount, 0, ',', '.');
}

function skin_url(int|string|null $skin): string
{
    $id = max(0, min(311, (int)$skin));
    return 'https://assets.open.mp/assets/images/skins/' . $id . '.png';
}

function samp_skin_options(): array
{
    static $options = null;
    if (is_array($options)) return $options;

    $excludedIds = array_fill_keys([
        0, 265, 266, 267,
        277, 278, 279, 280, 281, 282, 283, 284, 285, 286, 287, 288,
        300, 301, 302, 303, 304, 305, 306, 307, 308, 309, 310, 311,
    ], true);

    $options = [];
    for ($id = 0; $id <= 311; $id++) {
        if (isset($excludedIds[$id])) continue;
        $options[$id] = '#' . $id;
    }

    // Giữ tên nhận diện cho các mẫu nổi bật đang có trong form.
    foreach ([
        12 => 'City Profile',
        26 => 'LSRP Classic',
        30 => 'Street Profile',
        105 => 'Gang Profile',
        169 => 'Urban Profile',
    ] as $id => $name) {
        $options[$id] = $name;
    }

    return $options;
}

function samp_vehicle_image_url(int|string|null $model): string
{
    $id = max(400, min(611, (int)$model));
    return 'https://assets.open.mp/assets/images/vehiclePictures/Vehicle_' . $id . '.jpg';
}

function character_age(array $character): ?int
{
    $year = (int)($character['birth_year'] ?? 0);
    if ($year <= 0) return null;
    return (int)$GLOBALS['config']['game_year'] - $year;
}

function gender_name(int|string|null $gender): string
{
    return (int)$gender === 1 ? 'Nữ' : 'Nam';
}

function skin_tone_name(int|string|null $tone): string
{
    return ['Da trắng', 'Da vàng', 'Da đen'][(int)$tone] ?? 'Chưa xác định';
}

function birthplace_name(int|string|null $place): string
{
    return ['Los Santos', 'San Fierro', 'Las Venturas', 'Nơi khác'][(int)$place] ?? 'Chưa xác định';
}

function job_name(int|string|null $job): string
{
    return match ((int)$job) {
        1 => 'Pizza Boy',
        default => 'Không có',
    };
}

function application_status_name(string $status): string
{
    return match ($status) {
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối',
        default => 'Đang chờ',
    };
}

function application_status_class(string $status): string
{
    return match ($status) {
        'approved' => 'success',
        'rejected' => 'danger',
        default => 'warning',
    };
}

function valid_character_name(string $name): bool
{
    if (strlen($name) < 5 || strlen($name) > 24) return false;
    return (bool)preg_match('/^[A-Za-z]{2,}_[A-Za-z]{2,}$/', $name);
}

function ucp_ensure_character_applications_table(): bool
{
    static $ready = null;
    if ($ready !== null) return $ready;

    try {
        $pdo = db();
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS character_applications (
                application_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                account_id INT UNSIGNED NOT NULL,
                slot TINYINT UNSIGNED NOT NULL,
                character_name VARCHAR(25) NOT NULL,
                gender TINYINT UNSIGNED NOT NULL DEFAULT 0,
                birth_day TINYINT UNSIGNED NOT NULL DEFAULT 1,
                birth_month TINYINT UNSIGNED NOT NULL DEFAULT 1,
                birth_year SMALLINT UNSIGNED NOT NULL DEFAULT 1970,
                birth_place TINYINT UNSIGNED NOT NULL DEFAULT 0,
                nationality VARCHAR(80) NULL,
                skin_tone TINYINT UNSIGNED NOT NULL DEFAULT 0,
                skin SMALLINT UNSIGNED NOT NULL DEFAULT 26,
                height_cm SMALLINT UNSIGNED NULL,
                weight_kg SMALLINT UNSIGNED NULL,
                occupation VARCHAR(80) NULL,
                personality TEXT NULL,
                strengths TEXT NULL,
                weaknesses TEXT NULL,
                concept VARCHAR(1000) NOT NULL,
                background TEXT NOT NULL,
                roleplay_goal TEXT NOT NULL,
                rules_agreed TINYINT(1) NOT NULL DEFAULT 0,
                status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                admin_note TEXT NULL,
                reviewed_by INT UNSIGNED NULL,
                reviewed_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (application_id),
                KEY idx_character_applications_account (account_id),
                KEY idx_character_applications_status (status),
                KEY idx_character_applications_name (character_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        // Repair only missing columns from older UCP installations.
        $columns = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM character_applications");
        foreach ($stmt->fetchAll() as $column) {
            $columns[(string)$column['Field']] = true;
        }

        $missingColumns = [
            'account_id' => "ALTER TABLE character_applications ADD COLUMN account_id INT UNSIGNED NULL",
            'slot' => "ALTER TABLE character_applications ADD COLUMN slot TINYINT UNSIGNED NULL",
            'character_name' => "ALTER TABLE character_applications ADD COLUMN character_name VARCHAR(25) NULL",
            'gender' => "ALTER TABLE character_applications ADD COLUMN gender TINYINT UNSIGNED NULL",
            'birth_day' => "ALTER TABLE character_applications ADD COLUMN birth_day TINYINT UNSIGNED NULL",
            'birth_month' => "ALTER TABLE character_applications ADD COLUMN birth_month TINYINT UNSIGNED NULL",
            'birth_year' => "ALTER TABLE character_applications ADD COLUMN birth_year SMALLINT UNSIGNED NULL",
            'birth_place' => "ALTER TABLE character_applications ADD COLUMN birth_place TINYINT UNSIGNED NULL",
            'nationality' => "ALTER TABLE character_applications ADD COLUMN nationality VARCHAR(80) NULL",
            'skin_tone' => "ALTER TABLE character_applications ADD COLUMN skin_tone TINYINT UNSIGNED NULL",
            'skin' => "ALTER TABLE character_applications ADD COLUMN skin SMALLINT UNSIGNED NULL",
            'height_cm' => "ALTER TABLE character_applications ADD COLUMN height_cm SMALLINT UNSIGNED NULL",
            'weight_kg' => "ALTER TABLE character_applications ADD COLUMN weight_kg SMALLINT UNSIGNED NULL",
            'occupation' => "ALTER TABLE character_applications ADD COLUMN occupation VARCHAR(80) NULL",
            'personality' => "ALTER TABLE character_applications ADD COLUMN personality TEXT NULL",
            'strengths' => "ALTER TABLE character_applications ADD COLUMN strengths TEXT NULL",
            'weaknesses' => "ALTER TABLE character_applications ADD COLUMN weaknesses TEXT NULL",
            'concept' => "ALTER TABLE character_applications ADD COLUMN concept VARCHAR(1000) NULL",
            'background' => "ALTER TABLE character_applications ADD COLUMN background TEXT NULL",
            'roleplay_goal' => "ALTER TABLE character_applications ADD COLUMN roleplay_goal TEXT NULL",
            'rules_agreed' => "ALTER TABLE character_applications ADD COLUMN rules_agreed TINYINT(1) NOT NULL DEFAULT 0",
            'status' => "ALTER TABLE character_applications ADD COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'",
            'admin_note' => "ALTER TABLE character_applications ADD COLUMN admin_note TEXT NULL",
            'reviewed_by' => "ALTER TABLE character_applications ADD COLUMN reviewed_by INT UNSIGNED NULL",
            'reviewed_at' => "ALTER TABLE character_applications ADD COLUMN reviewed_at DATETIME NULL",
            'created_at' => "ALTER TABLE character_applications ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "ALTER TABLE character_applications ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        ];

        foreach ($missingColumns as $column => $sql) {
            if (!isset($columns[$column])) {
                $pdo->exec($sql);
            }
        }

        $ready = true;
    } catch (Throwable $e) {
        error_log('[LSRP UCP] character application schema check failed: ' . $e->getMessage());
        $ready = false;
    }

    return $ready;
}


function latest_hot_news(): ?array
{
    try {
        $stmt = db()->query(
            "SELECT * FROM ucp_news
             WHERE is_published = 1 AND is_hot = 1
             AND (published_at IS NULL OR published_at <= NOW())
             ORDER BY COALESCE(published_at, created_at) DESC, news_id DESC
             LIMIT 1"
        );
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function admin_log(string $action, string $targetType, ?int $targetId = null, array $details = []): void
{
    try {
        $stmt = db()->prepare(
            "INSERT INTO ucp_admin_logs
             (admin_account_id, action, target_type, target_id, details)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            current_account_id(),
            $action,
            $targetType,
            $targetId,
            json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ]);
    } catch (Throwable $e) {
        // Logging must never break the actual admin action.
    }
}

function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}


function twoyears_enabled(): bool
{
    return (bool)($GLOBALS['config']['twoyears']['enabled'] ?? true);
}

function twoyears_vehicle_model(): int
{
    return (int)($GLOBALS['config']['twoyears']['vehicle_model'] ?? 462);
}

function twoyears_ensure_tables(): bool
{
    static $ready = null;
    if ($ready !== null) return $ready;

    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS `ucp_twoyears_accounts` (
                `account_id` INT UNSIGNED NOT NULL,
                `card_code` VARCHAR(32) NOT NULL,
                `package_code` VARCHAR(32) NOT NULL DEFAULT '#TWOYEARS',
                `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`account_id`),
                UNIQUE KEY `uq_twoyears_card_code` (`card_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
        db()->exec(
            "CREATE TABLE IF NOT EXISTS `ucp_twoyears_character_rewards` (
                `reward_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `account_id` INT UNSIGNED NOT NULL,
                `character_id` INT UNSIGNED NOT NULL,
                `vehicle_model` SMALLINT UNSIGNED NOT NULL DEFAULT 462,
                `vehicle_granted` TINYINT(1) NOT NULL DEFAULT 0,
                `vehicle_id` BIGINT UNSIGNED NULL,
                `vehicle_granted_at` DATETIME NULL,
                `notice_seen_at` DATETIME NULL,
                `last_error` VARCHAR(500) NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`reward_id`),
                UNIQUE KEY `uq_twoyears_character` (`character_id`),
                KEY `idx_twoyears_account_notice` (`account_id`, `notice_seen_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
        $ready = true;
    } catch (Throwable $e) {
        $ready = false;
    }

    return $ready;
}

function twoyears_register_account_package(int $accountId): ?array
{
    if (!twoyears_enabled() || !twoyears_ensure_tables()) return null;

    $cardCode = 'TWY-' . str_pad((string)$accountId, 6, '0', STR_PAD_LEFT);

    try {
        $stmt = db()->prepare(
            "INSERT INTO ucp_twoyears_accounts (account_id, card_code, package_code)
             VALUES (?, ?, '#TWOYEARS')
             ON DUPLICATE KEY UPDATE card_code = VALUES(card_code)"
        );
        $stmt->execute([$accountId, $cardCode]);

        return [
            'account_id' => $accountId,
            'card_code' => $cardCode,
            'package_code' => '#TWOYEARS',
        ];
    } catch (Throwable $e) {
        return null;
    }
}

function twoyears_account_package(int $accountId): ?array
{
    if (!twoyears_enabled() || !twoyears_ensure_tables()) return null;

    try {
        $stmt = db()->prepare("SELECT * FROM ucp_twoyears_accounts WHERE account_id = ? LIMIT 1");
        $stmt->execute([$accountId]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function twoyears_account_reward(int $accountId): ?array
{
    if (!twoyears_ensure_tables()) return null;

    try {
        $stmt = db()->prepare(
            "SELECT * FROM ucp_twoyears_character_rewards
             WHERE account_id = ?
             ORDER BY created_at ASC, reward_id ASC
             LIMIT 1"
        );
        $stmt->execute([$accountId]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * #TWOYEARS grants the starter vehicle to the first approved character only.
 */
function twoyears_queue_character_reward(int $accountId, int $characterId): ?array
{
    if (!twoyears_enabled() || !twoyears_ensure_tables()) return null;

    try {
        $stmt = db()->prepare(
            "SELECT * FROM ucp_twoyears_character_rewards
             WHERE account_id = ? AND character_id = ? LIMIT 1"
        );
        $stmt->execute([$accountId, $characterId]);
        $sameCharacterReward = $stmt->fetch();
        if ($sameCharacterReward) return $sameCharacterReward;

        // An account can receive this starter reward only once.
        if (twoyears_account_reward($accountId)) return null;

        $stmt = db()->prepare(
            "INSERT INTO ucp_twoyears_character_rewards
             (account_id, character_id, vehicle_model)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$accountId, $characterId, twoyears_vehicle_model()]);

        $rewardId = (int)db()->lastInsertId();
        $stmt = db()->prepare("SELECT * FROM ucp_twoyears_character_rewards WHERE reward_id = ? LIMIT 1");
        $stmt->execute([$rewardId]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function twoyears_character_reward(int $accountId, int $characterId): ?array
{
    if (!twoyears_ensure_tables()) return null;

    try {
        $stmt = db()->prepare(
            "SELECT * FROM ucp_twoyears_character_rewards
             WHERE account_id = ? AND character_id = ? LIMIT 1"
        );
        $stmt->execute([$accountId, $characterId]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function twoyears_vehicle_plate(int $characterId): string
{
    return 'TWY' . str_pad((string)$characterId, 6, '0', STR_PAD_LEFT);
}

function twoyears_ensure_vehicle_table(): bool
{
    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS `player_vehicles` (
                `vehicle_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `character_id` INT UNSIGNED NOT NULL,
                `model_id` SMALLINT UNSIGNED NOT NULL,
                `plate` VARCHAR(16) NOT NULL,
                `color1` SMALLINT NOT NULL DEFAULT 3,
                `color2` SMALLINT NOT NULL DEFAULT 3,
                `mileage` DECIMAL(12,1) NOT NULL DEFAULT 0,
                `fuel` DECIMAL(6,2) NOT NULL DEFAULT 100,
                `health` FLOAT NOT NULL DEFAULT 1000,
                `is_stored` TINYINT(1) NOT NULL DEFAULT 1,
                `is_spawned` TINYINT(1) NOT NULL DEFAULT 0,
                `is_favorite` TINYINT(1) NOT NULL DEFAULT 0,
                `pos_x` FLOAT NOT NULL DEFAULT 2495.3633,
                `pos_y` FLOAT NOT NULL DEFAULT -1687.3105,
                `pos_z` FLOAT NOT NULL DEFAULT 13.5156,
                `pos_a` FLOAT NOT NULL DEFAULT 0,
                `interior_id` INT NOT NULL DEFAULT 0,
                `virtual_world` INT NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`vehicle_id`),
                UNIQUE KEY `uq_player_vehicle_plate` (`plate`),
                KEY `idx_player_vehicles_character` (`character_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function twoyears_grant_faggio(int $accountId, int $characterId): array
{
    $reward = twoyears_character_reward($accountId, $characterId);
    if (!$reward) $reward = twoyears_queue_character_reward($accountId, $characterId);
    if (!$reward) {
        return ['granted' => false, 'vehicle_id' => null, 'reason' => 'not_eligible'];
    }

    if ((int)($reward['vehicle_granted'] ?? 0) === 1) {
        return [
            'granted' => true,
            'vehicle_id' => !empty($reward['vehicle_id']) ? (int)$reward['vehicle_id'] : null,
            'plate' => twoyears_vehicle_plate($characterId),
            'reason' => 'already_granted'
        ];
    }

    if (!db_table_exists('player_vehicles') && !twoyears_ensure_vehicle_table()) {
        return ['granted' => false, 'vehicle_id' => null, 'reason' => 'vehicle_table'];
    }

    $columns = db_table_columns('player_vehicles');
    $ownerCol = first_existing_column($columns, ['character_id', 'owner_character_id', 'owner_id', 'characterid', 'char_id', 'charid', 'character']);
    $modelCol = first_existing_column($columns, ['model_id', 'model', 'vehicle_model', 'modelid', 'veh_model', 'vehmodel']);
    $plateCol = first_existing_column($columns, ['plate', 'number_plate', 'license_plate', 'numberplate', 'licenseplate']);
    $idCol = first_existing_column($columns, ['vehicle_id', 'id', 'sql_id', 'vehicle_db_id', 'db_id']);

    if ($ownerCol === null || $modelCol === null || $plateCol === null) {
        $message = 'player_vehicles khong co cot owner/model/plate tuong thich.';
        try {
            $stmt = db()->prepare(
                "UPDATE ucp_twoyears_character_rewards
                 SET last_error = ? WHERE reward_id = ? AND account_id = ?"
            );
            $stmt->execute([$message, (int)$reward['reward_id'], $accountId]);
        } catch (Throwable $ignored) {
        }
        return ['granted' => false, 'vehicle_id' => null, 'reason' => 'vehicle_schema', 'error' => $message];
    }

    $plate = twoyears_vehicle_plate($characterId);

    try {
        // Never grant the same #TWOYEARS vehicle twice.
        $idSelect = $idCol !== null ? "`{$idCol}`" : '1';
        $stmt = db()->prepare(
            "SELECT {$idSelect} FROM player_vehicles
             WHERE `{$ownerCol}` = ? AND `{$plateCol}` = ? LIMIT 1"
        );
        $stmt->execute([$characterId, $plate]);
        $existing = $stmt->fetchColumn();
        $vehicleId = ($idCol !== null && $existing !== false) ? (int)$existing : 0;

        if ($existing === false) {
            $insertColumns = [$ownerCol, $modelCol, $plateCol];
            $insertValues = [$characterId, twoyears_vehicle_model(), $plate];

            // Only write optional fields that exist in the gamemode's actual schema.
            $optionalFields = [
                [['color1', 'colour1', 'primary_color'], 3],
                [['color2', 'colour2', 'secondary_color'], 3],
                [['mileage', 'odometer', 'kilometers', 'km'], 0],
                [['fuel', 'fuel_level'], 100],
                [['health', 'vehicle_health'], 1000],
                [['is_stored', 'stored', 'in_garage'], 1],
                [['is_spawned', 'spawned'], 0],
                [['is_favorite', 'favorite', 'favourite'], 0],
                [['pos_x', 'x', 'spawn_x'], 2495.3633],
                [['pos_y', 'y', 'spawn_y'], -1687.3105],
                [['pos_z', 'z', 'spawn_z'], 13.5156],
                [['pos_a', 'a', 'angle', 'spawn_a'], 0],
                [['interior_id', 'interior'], 0],
                [['virtual_world', 'world', 'vw'], 0],
                [['locked', 'is_locked'], 0],
                [['engine', 'engine_state'], 0],
                [['lights', 'light_state'], 0],
                [['paintjob', 'paint_job'], -1],
            ];

            foreach ($optionalFields as [$candidates, $value]) {
                $column = first_existing_column($columns, $candidates);
                if ($column === null || in_array($column, $insertColumns, true)) continue;
                $insertColumns[] = $column;
                $insertValues[] = $value;
            }

            $quoted = array_map(static fn(string $column): string => "`{$column}`", $insertColumns);
            $placeholders = implode(', ', array_fill(0, count($insertColumns), '?'));
            $sql = 'INSERT INTO player_vehicles (' . implode(', ', $quoted) . ') VALUES (' . $placeholders . ')';

            $stmt = db()->prepare($sql);
            $stmt->execute($insertValues);
            $vehicleId = (int)db()->lastInsertId();

            // lastInsertId normally works regardless of the PK column name, but re-read it
            // from the actual id alias when available so reward tracking stays exact.
            if ($idCol !== null) {
                $stmt = db()->prepare(
                    "SELECT `{$idCol}` FROM player_vehicles
                     WHERE `{$ownerCol}` = ? AND `{$plateCol}` = ? LIMIT 1"
                );
                $stmt->execute([$characterId, $plate]);
                $vehicleId = (int)($stmt->fetchColumn() ?: $vehicleId);
            }
        }

        $stmt = db()->prepare(
            "UPDATE ucp_twoyears_character_rewards
             SET vehicle_granted = 1,
                 vehicle_id = ?,
                 vehicle_granted_at = COALESCE(vehicle_granted_at, NOW()),
                 last_error = NULL
             WHERE reward_id = ? AND account_id = ?"
        );
        $stmt->execute([
            $vehicleId > 0 ? $vehicleId : null,
            (int)$reward['reward_id'],
            $accountId
        ]);

        return [
            'granted' => true,
            'vehicle_id' => $vehicleId > 0 ? $vehicleId : null,
            'plate' => $plate,
            'reason' => 'granted'
        ];
    } catch (Throwable $e) {
        try {
            $stmt = db()->prepare(
                "UPDATE ucp_twoyears_character_rewards
                 SET last_error = ? WHERE reward_id = ? AND account_id = ?"
            );
            $stmt->execute([mb_substr($e->getMessage(), 0, 500), (int)$reward['reward_id'], $accountId]);
        } catch (Throwable $ignored) {
        }

        return ['granted' => false, 'vehicle_id' => null, 'reason' => 'insert_failed', 'error' => mb_substr($e->getMessage(), 0, 500)];
    }
}

function ucp_ensure_notifications_table(): bool
{
    static $ready = null;
    if ($ready !== null) return $ready;

    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS `ucp_notifications` (
                `notification_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `account_id` INT UNSIGNED NOT NULL,
                `type` VARCHAR(40) NOT NULL DEFAULT 'system',
                `title` VARCHAR(160) NOT NULL,
                `message` TEXT NOT NULL,
                `action_url` VARCHAR(255) NULL,
                `action_label` VARCHAR(80) NULL,
                `dedupe_key` VARCHAR(120) NULL,
                `meta_json` LONGTEXT NULL,
                `is_read` TINYINT(1) NOT NULL DEFAULT 0,
                `read_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`notification_id`),
                UNIQUE KEY `uq_ucp_notifications_dedupe` (`dedupe_key`),
                KEY `idx_ucp_notifications_account` (`account_id`, `is_read`, `created_at`),
                KEY `idx_ucp_notifications_type` (`account_id`, `type`, `is_read`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
        $ready = true;
    } catch (Throwable $e) {
        $ready = false;
    }

    return $ready;
}

function ucp_notification_create(
    int $accountId,
    string $type,
    string $title,
    string $message,
    ?string $actionUrl = null,
    ?string $actionLabel = null,
    ?string $dedupeKey = null,
    array $meta = []
): ?int {
    if (!ucp_ensure_notifications_table()) return null;

    try {
        $stmt = db()->prepare(
            "INSERT INTO ucp_notifications
             (account_id, type, title, message, action_url, action_label, dedupe_key, meta_json)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                message = VALUES(message),
                action_url = VALUES(action_url),
                action_label = VALUES(action_label),
                meta_json = VALUES(meta_json)"
        );
        $stmt->execute([
            $accountId,
            mb_substr($type, 0, 40),
            mb_substr($title, 0, 160),
            $message,
            $actionUrl !== null ? mb_substr($actionUrl, 0, 255) : null,
            $actionLabel !== null ? mb_substr($actionLabel, 0, 80) : null,
            $dedupeKey !== null ? mb_substr($dedupeKey, 0, 120) : null,
            $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ]);

        if ($dedupeKey !== null && $dedupeKey !== '') {
            $stmt = db()->prepare("SELECT notification_id FROM ucp_notifications WHERE dedupe_key = ? LIMIT 1");
            $stmt->execute([$dedupeKey]);
            $id = $stmt->fetchColumn();
            return $id !== false ? (int)$id : null;
        }

        return (int)db()->lastInsertId();
    } catch (Throwable $e) {
        return null;
    }
}

function ucp_notifications_for_account(int $accountId, int $limit = 8): array
{
    if (!ucp_ensure_notifications_table()) return [];

    try {
        $limit = max(1, min(50, $limit));
        $stmt = db()->prepare(
            "SELECT * FROM ucp_notifications
             WHERE account_id = ?
             ORDER BY is_read ASC, created_at DESC, notification_id DESC
             LIMIT {$limit}"
        );
        $stmt->execute([$accountId]);
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function ucp_notification_unread_count(int $accountId): int
{
    if (!ucp_ensure_notifications_table()) return 0;

    try {
        $stmt = db()->prepare("SELECT COUNT(*) FROM ucp_notifications WHERE account_id = ? AND is_read = 0");
        $stmt->execute([$accountId]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function ucp_notification_get(int $accountId, int $notificationId): ?array
{
    if (!ucp_ensure_notifications_table()) return null;

    try {
        $stmt = db()->prepare(
            "SELECT * FROM ucp_notifications
             WHERE notification_id = ? AND account_id = ? LIMIT 1"
        );
        $stmt->execute([$notificationId, $accountId]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function ucp_notification_mark_read(int $accountId, int $notificationId): ?array
{
    $notification = ucp_notification_get($accountId, $notificationId);
    if (!$notification) return null;

    try {
        $stmt = db()->prepare(
            "UPDATE ucp_notifications
             SET is_read = 1, read_at = COALESCE(read_at, NOW())
             WHERE notification_id = ? AND account_id = ?"
        );
        $stmt->execute([$notificationId, $accountId]);
        $notification['is_read'] = 1;
        return $notification;
    } catch (Throwable $e) {
        return null;
    }
}

function ucp_notification_mark_all_read(int $accountId): void
{
    if (!ucp_ensure_notifications_table()) return;

    try {
        $stmt = db()->prepare(
            "UPDATE ucp_notifications
             SET is_read = 1, read_at = COALESCE(read_at, NOW())
             WHERE account_id = ? AND is_read = 0"
        );
        $stmt->execute([$accountId]);
    } catch (Throwable $e) {
        // Notification features must never break the UCP.
    }
}

function ucp_pending_character_approval_notification(int $accountId): ?array
{
    if (!ucp_ensure_notifications_table()) return null;

    try {
        $stmt = db()->prepare(
            "SELECT * FROM ucp_notifications
             WHERE account_id = ? AND type = 'character_approved' AND is_read = 0
             ORDER BY created_at ASC, notification_id ASC
             LIMIT 1"
        );
        $stmt->execute([$accountId]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function ucp_notification_target(?string $target): string
{
    $target = trim((string)$target);
    if ($target === '' || str_contains($target, '://') || str_starts_with($target, '//')) {
        return 'dashboard.php';
    }
    return ltrim($target, '/');
}

/**
 * Returns true when a table exists in the currently selected database.
 * Cached per request because vehicle widgets can be rendered more than once.
 */
function db_table_exists(string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) return $cache[$table];

    try {
        $stmt = db()->prepare(
            "SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1"
        );
        $stmt->execute([$table]);
        return $cache[$table] = (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return $cache[$table] = false;
    }
}

function db_table_columns(string $table): array
{
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];

    // Only tables explicitly supported by the UCP may be inspected.
    if (!in_array($table, ['player_vehicles'], true) || !db_table_exists($table)) {
        return $cache[$table] = [];
    }

    try {
        $rows = db()->query("SHOW COLUMNS FROM `{$table}`")->fetchAll();
        $columns = [];
        foreach ($rows as $row) {
            $field = (string)($row['Field'] ?? '');
            if ($field !== '') $columns[$field] = true;
        }
        return $cache[$table] = $columns;
    } catch (Throwable $e) {
        return $cache[$table] = [];
    }
}

function first_existing_column(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (isset($columns[$candidate])) return $candidate;
    }
    return null;
}

function row_value(array $row, array $keys, mixed $default = null): mixed
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
            return $row[$key];
        }
    }
    return $default;
}

function vehicle_model_name(int $model): string
{
    $names = [
        400 => 'Landstalker', 401 => 'Bravura', 402 => 'Buffalo', 404 => 'Perennial',
        405 => 'Sentinel', 410 => 'Manana', 411 => 'Infernus', 412 => 'Voodoo',
        415 => 'Cheetah', 419 => 'Esperanto', 420 => 'Taxi', 421 => 'Washington',
        422 => 'Bobcat', 429 => 'Banshee', 434 => 'Hotknife', 436 => 'Previon',
        439 => 'Stallion', 445 => 'Admiral', 448 => 'Pizzaboy', 451 => 'Turismo',
        458 => 'Solair', 461 => 'PCJ-600', 462 => 'Faggio', 463 => 'Freeway',
        466 => 'Glendale', 467 => 'Oceanic', 468 => 'Sanchez', 470 => 'Patriot',
        474 => 'Hermes', 475 => 'Sabre', 477 => 'ZR-350', 478 => 'Walton',
        479 => 'Regina', 480 => 'Comet', 482 => 'Burrito', 483 => 'Camper',
        489 => 'Rancher', 491 => 'Virgo', 492 => 'Greenwood', 495 => 'Sandking',
        496 => 'Blista Compact', 500 => 'Mesa', 506 => 'Super GT', 507 => 'Elegant',
        516 => 'Nebula', 517 => 'Majestic', 518 => 'Buccaneer', 521 => 'FCR-900',
        522 => 'NRG-500', 526 => 'Fortune', 527 => 'Cadrona', 529 => 'Willard',
        533 => 'Feltzer', 534 => 'Remington', 535 => 'Slamvan', 536 => 'Blade',
        540 => 'Vincent', 541 => 'Bullet', 542 => 'Clover', 543 => 'Sadler',
        545 => 'Hustler', 546 => 'Intruder', 547 => 'Primo', 549 => 'Tampa',
        550 => 'Sunrise', 551 => 'Merit', 554 => 'Yosemite', 555 => 'Windsor',
        558 => 'Uranus', 559 => 'Jester', 560 => 'Sultan', 561 => 'Stratum',
        562 => 'Elegy', 565 => 'Flash', 566 => 'Tahoma', 567 => 'Savanna',
        575 => 'Broadway', 576 => 'Tornado', 579 => 'Huntley', 580 => 'Stafford',
        581 => 'BF-400', 585 => 'Emperor', 586 => 'Wayfarer', 587 => 'Euros',
        589 => 'Club', 596 => 'Police Car (LSPD)', 600 => 'Picador', 602 => 'Alpha',
        603 => 'Phoenix'
    ];

    return $names[$model] ?? ('Vehicle Model ' . $model);
}

/**
 * Normalise the server's player_vehicles row into a stable UCP shape.
 * The aliases intentionally cover older/newer vehicle module column names,
 * so the UCP does not force a second ownership table.
 */
function normalize_owned_vehicle(array $row): array
{
    $model = (int)row_value($row, ['model_id', 'model', 'vehicle_model', 'modelid', 'veh_model', 'vehmodel'], 0);
    $vehicleId = (int)row_value($row, ['vehicle_id', 'id', 'sql_id', 'vehicle_db_id', 'db_id'], 0);
    $characterId = (int)row_value($row, ['_owner_character_id', 'character_id', 'owner_character_id', 'owner_id'], 0);
    $characterName = (string)row_value($row, ['_owner_character_name', 'character_name', 'owner_name'], 'Không xác định');

    $storedRaw = row_value($row, ['is_stored', 'stored', 'in_garage'], null);
    $spawnedRaw = row_value($row, ['is_spawned', 'spawned'], null);
    $favoriteRaw = row_value($row, ['is_favorite', 'favorite', 'favourite'], 0);

    $state = 'Sẵn sàng';
    if ($storedRaw !== null && (int)$storedRaw === 1) $state = 'Đang cất';
    if ($spawnedRaw !== null && (int)$spawnedRaw === 1) $state = 'Đang sử dụng';

    return [
        'vehicle_id' => $vehicleId,
        'character_id' => $characterId,
        'character_name' => $characterName,
        'model' => $model,
        'model_name' => $model > 0 ? vehicle_model_name($model) : 'Phương tiện',
        'plate' => strtoupper((string)row_value($row, ['plate', 'number_plate', 'license_plate', 'numberplate', 'licenseplate'], 'NO PLATE')),
        'color1' => row_value($row, ['color1', 'colour1', 'primary_color'], null),
        'color2' => row_value($row, ['color2', 'colour2', 'secondary_color'], null),
        'mileage' => row_value($row, ['mileage', 'odometer', 'kilometers', 'km'], null),
        'fuel' => row_value($row, ['fuel', 'fuel_level'], null),
        'health' => row_value($row, ['health', 'vehicle_health'], null),
        'favorite' => (int)$favoriteRaw === 1,
        'state' => $state,
        'created_at' => row_value($row, ['created_at', 'purchased_at', 'purchase_date'], null),
    ];
}

/**
 * Read every owned vehicle belonging to the logged-in master account.
 * Ownership remains character-based: account -> up to 3 characters -> vehicles.
 */
function owned_vehicles_for_account(int $accountId): array
{
    if (!db_table_exists('player_vehicles')) {
        return [
            'available' => false,
            'vehicles' => [],
            'message' => 'Bảng player_vehicles chưa có trong database máy chủ.'
        ];
    }

    $columns = db_table_columns('player_vehicles');
    if (!$columns) {
        return ['available' => false, 'vehicles' => [], 'message' => 'Không đọc được cấu trúc player_vehicles.'];
    }

    $characterOwner = first_existing_column($columns, ['character_id', 'owner_character_id', 'owner_id', 'characterid', 'char_id', 'charid', 'character']);
    $accountOwner = first_existing_column($columns, ['account_id', 'owner_account_id']);
    $idColumn = first_existing_column($columns, ['vehicle_id', 'id', 'sql_id', 'vehicle_db_id', 'db_id']);

    try {
        if ($characterOwner !== null) {
            $order = $idColumn ? "pv.`{$idColumn}` DESC" : 'pc.character_id ASC';
            $sql = "SELECT pv.*, pc.character_id AS _owner_character_id, pc.name AS _owner_character_name
                    FROM player_vehicles pv
                    INNER JOIN player_characters pc ON pc.character_id = pv.`{$characterOwner}`
                    WHERE pc.account_id = ?
                    ORDER BY {$order}";
            $stmt = db()->prepare($sql);
            $stmt->execute([$accountId]);
        } elseif ($accountOwner !== null) {
            $order = $idColumn ? "`{$idColumn}` DESC" : "`{$accountOwner}` ASC";
            $stmt = db()->prepare("SELECT * FROM player_vehicles WHERE `{$accountOwner}` = ? ORDER BY {$order}");
            $stmt->execute([$accountId]);
        } else {
            return [
                'available' => false,
                'vehicles' => [],
                'message' => 'player_vehicles chưa có cột character_id/owner_character_id/owner_id tương thích.'
            ];
        }

        $vehicles = [];
        foreach ($stmt->fetchAll() as $row) $vehicles[] = normalize_owned_vehicle($row);
        return ['available' => true, 'vehicles' => $vehicles, 'message' => null];
    } catch (Throwable $e) {
        return ['available' => false, 'vehicles' => [], 'message' => 'Không thể tải dữ liệu phương tiện sở hữu.'];
    }
}

function owned_vehicles_for_character(int $accountId, int $characterId): array
{
    $result = owned_vehicles_for_account($accountId);
    if (!$result['available']) return $result;

    $result['vehicles'] = array_values(array_filter(
        $result['vehicles'],
        static fn(array $vehicle): bool => (int)$vehicle['character_id'] === $characterId
    ));
    return $result;
}

function format_vehicle_metric(mixed $value, string $suffix = ''): string
{
    if ($value === null || $value === '') return '—';
    if (is_numeric($value)) return number_format((float)$value, 0, ',', '.') . $suffix;
    return (string)$value . $suffix;
}
