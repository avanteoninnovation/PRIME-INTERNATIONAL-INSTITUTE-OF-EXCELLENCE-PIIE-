<?php
// Add database fields using direct PDO connection

// Load environment variables
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, 'DB_') === 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

try {
    // PDO connection
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $database = $_ENV['DB_DATABASE'] ?? 'ekattor8';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Add columns to website_pages table
    $columns_to_add = [
        'subtitle' => "VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL",
        'page_image' => "VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL",
        'overlay_opacity' => "INT DEFAULT 40",
        'cta_button_text' => "VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL",
        'cta_button_link' => "VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL",
        'show_in_navigation' => "TINYINT(1) DEFAULT 1",
        'display_order' => "INT DEFAULT 0",
        'nav_title' => "VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL COMMENT 'Custom title for navigation'"
    ];
    
    foreach ($columns_to_add as $column_name => $column_def) {
        // Check if column already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_NAME = 'website_pages' AND COLUMN_NAME = ?");
        $stmt->execute([$column_name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['cnt'] == 0) {
            $pdo->exec("ALTER TABLE website_pages ADD COLUMN $column_name $column_def");
            echo "✓ Added column: $column_name\n";
        } else {
            echo "✗ Column already exists: $column_name\n";
        }
    }
    
    echo "\n✓ Database fields added successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
