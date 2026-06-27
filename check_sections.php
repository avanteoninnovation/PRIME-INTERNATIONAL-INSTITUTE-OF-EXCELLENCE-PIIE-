<?php
// Direct MySQL query using mysqli
$host = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'ekattor8';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "===== HOME PAGE SECTIONS =====\n";
$result = $conn->query("SELECT id, section_key, title FROM website_sections WHERE page_key = 'home' ORDER BY sort_order");
while ($row = $result->fetch_assoc()) {
    echo "  ID: {$row['id']}, Key: {$row['section_key']}, Title: {$row['title']}\n";
}

echo "\n===== ITEMS IN DATABASE =====\n";
$result = $conn->query("SELECT DISTINCT section_key, COUNT(*) as count FROM website_items GROUP BY section_key ORDER BY section_key");
while ($row = $result->fetch_assoc()) {
    echo "  {$row['section_key']}: {$row['count']} items\n";
}

$conn->close();
?>
