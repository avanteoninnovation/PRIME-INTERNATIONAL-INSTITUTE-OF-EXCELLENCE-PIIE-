<?php
// Populate page header fields and navigation settings

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
    
    // Define page data with subtitles and display order
    $pages_data = [
        'home' => [
            'subtitle' => 'Internationally Benchmarked Education for Global Excellence',
            'display_order' => 1,
            'show_in_navigation' => 1,
            'cta_button_text' => 'Apply Now',
            'cta_button_link' => '/admissions'
        ],
        'about' => [
            'subtitle' => 'Learn More About Our Institution',
            'display_order' => 2,
            'show_in_navigation' => 1,
        ],
        'programs' => [
            'subtitle' => 'Explore Our Academic Programs',
            'display_order' => 3,
            'show_in_navigation' => 1,
        ],
        'fees' => [
            'subtitle' => 'Transparent and Competitive Pricing',
            'display_order' => 4,
            'show_in_navigation' => 1,
        ],
        'admissions' => [
            'subtitle' => 'Begin Your Educational Journey',
            'display_order' => 5,
            'show_in_navigation' => 1,
            'cta_button_text' => 'Apply Now',
            'cta_button_link' => '/admissions'
        ],
        'contact' => [
            'subtitle' => 'Get In Touch With Us',
            'display_order' => 6,
            'show_in_navigation' => 1,
        ],
    ];
    
    foreach ($pages_data as $page_key => $data) {
        $stmt = $pdo->prepare("
            UPDATE website_pages 
            SET 
                subtitle = :subtitle,
                display_order = :display_order,
                show_in_navigation = :show_in_navigation,
                cta_button_text = COALESCE(:cta_button_text, cta_button_text),
                cta_button_link = COALESCE(:cta_button_link, cta_button_link)
            WHERE page_key = :page_key
        ");
        
        $stmt->execute([
            ':subtitle' => $data['subtitle'] ?? null,
            ':display_order' => $data['display_order'] ?? 0,
            ':show_in_navigation' => $data['show_in_navigation'] ?? 1,
            ':cta_button_text' => $data['cta_button_text'] ?? null,
            ':cta_button_link' => $data['cta_button_link'] ?? null,
            ':page_key' => $page_key,
        ]);
        
        echo "✓ Updated page: $page_key\n";
    }
    
    echo "\n✓ All pages updated successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
