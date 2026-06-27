<?php
// Direct MySQL update using mysqli
$host = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'ekattor8';

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

try {
    // Remove leading slashes from slugs
    $query0 = 'UPDATE website_pages SET slug = TRIM(LEADING "/" FROM slug)';
    $conn->query($query0);
    
    // For home page, set slug to empty string since it's the landing page
    $query_home = 'UPDATE website_pages SET slug = "home" WHERE page_key = "home"';
    $conn->query($query_home);
    
    // Fetch all pages to verify
    $result = $conn->query('SELECT id, page_key, slug, title FROM website_pages ORDER BY sort_order');
    
    echo "Pages updated successfully!\n";
    echo "Current pages:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - page_key: {$row['page_key']}, slug: {$row['slug']}, title: {$row['title']}\n";
    }
    
    echo "\n✓ All pages now have proper slugs for navigation!\n";
    $conn->close();
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
