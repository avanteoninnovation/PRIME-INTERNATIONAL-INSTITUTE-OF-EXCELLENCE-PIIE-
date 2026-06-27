<?php
/**
 * Link Integrity Audit Script
 * Checks all links on the website for validity
 */

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
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $database = $_ENV['DB_DATABASE'] ?? 'ekattor8';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $issues = [];
    $total_checked = 0;
    
    echo "\n=== WEBSITE LINK INTEGRITY AUDIT ===\n\n";
    
    // 1. Check Pages
    echo "Checking Pages...\n";
    $stmt = $pdo->query("SELECT id, title, slug, cta_button_link FROM website_pages");
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($pages as $page) {
        $total_checked++;
        
        // Check if slug is empty
        if (!$page['slug']) {
            $issues[] = [
                'type' => 'Page',
                'id' => $page['id'],
                'name' => $page['title'],
                'issue' => 'Empty slug - navigation links will fail'
            ];
        }
        
        // Check if CTA link is valid
        if ($page['cta_button_link'] && $page['cta_button_link'] !== '#') {
            if (!preg_match('|^https?://|', $page['cta_button_link']) && strpos($page['cta_button_link'], '/') === 0) {
                // Internal link - basic validation
                $valid_routes = ['/', '/website/', '/admin/', '/superadmin/', '/admissions', '/programs', '/about', '/fees', '/contact'];
                $is_valid = false;
                foreach ($valid_routes as $route) {
                    if (strpos($page['cta_button_link'], $route) === 0) {
                        $is_valid = true;
                        break;
                    }
                }
                if (!$is_valid) {
                    $issues[] = [
                        'type' => 'Page CTA Link',
                        'id' => $page['id'],
                        'name' => $page['title'],
                        'issue' => 'Potentially invalid CTA link: ' . $page['cta_button_link']
                    ];
                }
            }
        }
    }
    echo "✓ Checked " . count($pages) . " pages\n";
    
    // 2. Check Items
    echo "Checking Website Items...\n";
    $stmt = $pdo->query("SELECT id, title, link FROM website_items WHERE status = 1");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        // Check if link is empty href="#"
        if ($item['link'] === '#' || $item['link'] === '' || is_null($item['link'])) {
            $issues[] = [
                'type' => 'Item',
                'id' => $item['id'],
                'name' => $item['title'] ?? 'Unnamed Item',
                'issue' => 'Empty or hash-only link'
            ];
        }
    }
    echo "✓ Checked " . count($items) . " items\n";
    
    // 3. Check Settings
    echo "Checking Website Settings...\n";
    $stmt = $pdo->query("SELECT id, `key` FROM website_settings WHERE status = 1");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✓ Checked " . count($settings) . " settings\n";
    
    // 4. Report Results
    echo "\n=== AUDIT RESULTS ===\n";
    echo "Total Items Checked: " . ($total_checked + count($items) + count($settings)) . "\n";
    
    if (empty($issues)) {
        echo "✓ No issues found! All links appear to be valid.\n";
    } else {
        echo "\n⚠ ISSUES FOUND: " . count($issues) . "\n";
        echo "───────────────────────────────────────────\n";
        
        foreach ($issues as $issue) {
            echo "\n[{$issue['type']}] ID: {$issue['id']}\n";
            echo "Name: {$issue['name']}\n";
            echo "Issue: {$issue['issue']}\n";
        }
    }
    
    echo "\n=== RECOMMENDATIONS ===\n";
    echo "1. All internal links should be properly formatted URLs or valid routes\n";
    echo "2. External links should have full URLs (http:// or https://)\n";
    echo "3. No links should be empty or '#' only\n";
    echo "4. All page slugs must be populated for navigation to work\n";
    echo "5. Use route() helper in blade templates for internal links\n";
    
    echo "\n✓ Audit complete!\n\n";
    
} catch (Exception $e) {
    echo "✗ Error during audit: " . $e->getMessage() . "\n";
    exit(1);
}
