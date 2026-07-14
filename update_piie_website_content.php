<?php

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'ekattor8';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

$content = require __DIR__ . '/database/seeders/piie_website_content_data.php';

function getTableColumns(PDO $pdo, string $table): array
{
    $stmt = $pdo->prepare(
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name'
    );
    $stmt->execute([':table_name' => $table]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function insertRow(PDO $pdo, string $table, array $row, array $columns): void
{
    $allowed = array_flip($columns);
    $payload = array_intersect_key($row, $allowed);

    if (empty($payload)) {
        return;
    }

    $fieldList = array_keys($payload);
    $placeholders = array_map(static fn ($field) => ':' . $field, $fieldList);
    $quotedFields = array_map(static fn ($field) => '`' . $field . '`', $fieldList);
    $sql = sprintf(
        'INSERT INTO `%s` (%s) VALUES (%s)',
        $table,
        implode(', ', $quotedFields),
        implode(', ', $placeholders)
    );

    $stmt = $pdo->prepare($sql);
    $params = [];

    foreach ($payload as $field => $value) {
        $params[':' . $field] = $value;
    }

    $stmt->execute($params);
}

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->beginTransaction();

    $pageColumns = getTableColumns($pdo, 'website_pages');
    $sectionColumns = getTableColumns($pdo, 'website_sections');
    $itemColumns = getTableColumns($pdo, 'website_items');
    $settingsColumns = getTableColumns($pdo, 'website_settings');
    $seoColumns = getTableColumns($pdo, 'website_seo_settings');

    $pdo->exec('DELETE FROM website_items');
    $pdo->exec('DELETE FROM website_sections');
    $pdo->exec('DELETE FROM website_pages');
    $pdo->exec('DELETE FROM website_seo_settings');

    foreach ($content['pages'] as $page) {
        insertRow($pdo, 'website_pages', [
            'page_key' => $page['page_key'],
            'title' => $page['title'],
            'slug' => $page['slug'],
            'subtitle' => $page['subtitle'] ?? null,
            'cta_button_text' => $page['cta_button_text'] ?? null,
            'cta_button_link' => $page['cta_button_link'] ?? null,
            'show_in_navigation' => $page['show_in_navigation'] ?? 1,
            'display_order' => $page['display_order'] ?? $page['sort_order'],
            'status' => $page['status'] ?? 1,
            'sort_order' => $page['sort_order'] ?? 0,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $pageColumns);
    }

    foreach ($content['sections'] as $section) {
        insertRow($pdo, 'website_sections', [
            'page_key' => $section['page_key'],
            'section_key' => $section['section_key'],
            'title' => $section['title'],
            'subtitle' => $section['subtitle'] ?? null,
            'content' => $section['content'] ?? null,
            'status' => $section['status'] ?? 1,
            'sort_order' => $section['sort_order'] ?? 0,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $sectionColumns);
    }

    foreach ($content['items'] as $item) {
        insertRow($pdo, 'website_items', [
            'section_key' => $item['section_key'],
            'item_type' => $item['item_type'],
            'title' => $item['title'],
            'subtitle' => $item['subtitle'] ?? null,
            'description' => $item['description'] ?? null,
            'content' => $item['content'] ?? null,
            'image' => $item['image'] ?? null,
            'link' => $item['link'] ?? null,
            'button_text' => $item['button_text'] ?? null,
            'icon' => $item['icon'] ?? null,
            'badge' => $item['badge'] ?? null,
            'status' => $item['status'] ?? 1,
            'sort_order' => $item['sort_order'] ?? 0,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $itemColumns);
    }

    foreach ($content['settings'] as $key => $value) {
        $existingSettingStmt = $pdo->prepare('SELECT id FROM website_settings WHERE `key` = :key_name LIMIT 1');
        $existingSettingStmt->execute([':key_name' => $key]);
        $existingSetting = $existingSettingStmt->fetchColumn();

        if ($existingSetting) {
            $updateFields = [];
            $params = [':key_name' => $key, ':value_text' => $value];

            if (in_array('value', $settingsColumns, true)) {
                $updateFields[] = '`value` = :value_text';
            }
            if (in_array('status', $settingsColumns, true)) {
                $updateFields[] = '`status` = 1';
            }
            if (in_array('updated_at', $settingsColumns, true)) {
                $updateFields[] = '`updated_at` = NOW()';
            }

            $pdo->prepare('UPDATE website_settings SET ' . implode(', ', $updateFields) . ' WHERE `key` = :key_name')->execute($params);
        } else {
            insertRow($pdo, 'website_settings', [
                'key' => $key,
                'value' => $value,
                'is_json' => 0,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ], $settingsColumns);
        }
    }

    foreach ($content['pages'] as $page) {
        insertRow($pdo, 'website_seo_settings', [
            'page_key' => $page['page_key'],
            'meta_title' => 'PIIE - ' . $page['title'],
            'meta_description' => 'Prime International Institute of Excellence (PIIE) official website.',
            'meta_keywords' => 'PIIE, Prime International Institute of Excellence, online higher education Uganda, ODeL Uganda',
            'canonical_url' => null,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $seoColumns);
    }

    $pdo->commit();

    echo "PIIE website content updated successfully.\n";
    echo 'Pages: ' . count($content['pages']) . ", Sections: " . count($content['sections']) . ", Items: " . count($content['items']) . "\n";
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, 'Update failed: ' . $exception->getMessage() . "\n");
    exit(1);
}