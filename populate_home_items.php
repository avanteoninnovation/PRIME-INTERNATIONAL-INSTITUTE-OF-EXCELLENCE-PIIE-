<?php
// Direct MySQL update using mysqli - Populate items for correct sections
$host = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'ekattor8';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Delete existing items for these sections
$conn->query('DELETE FROM website_items WHERE section_key IN ("governance_structure", "fees_structure", "online_learning_odel", "student_support_services", "international_students")');

// Insert items for governance_structure (Team)
$teamItems = [
    ['section_key' => 'governance_structure', 'item_type' => 'team_member', 'title' => 'Dr. Naboth Twinamatsiko', 'subtitle' => 'Director', 'description' => 'Visionary leader with 15+ years in education', 'icon' => 'fas fa-user-tie', 'status' => 1],
    ['section_key' => 'governance_structure', 'item_type' => 'team_member', 'title' => 'Mr. Bendaki Evans', 'subtitle' => 'Institute Secretary', 'description' => 'Administrative expert ensuring smooth operations', 'icon' => 'fas fa-user-tie', 'status' => 1],
    ['section_key' => 'governance_structure', 'item_type' => 'team_member', 'title' => 'Ms. Grace Nakamya', 'subtitle' => 'Academic Registrar', 'description' => 'Dedicated to quality assurance and excellence', 'icon' => 'fas fa-user-tie', 'status' => 1],
];

// Insert items for fees_structure (Programs)
$programItems = [
    ['section_key' => 'fees_structure', 'item_type' => 'program', 'title' => 'Master of Public Administration', 'subtitle' => 'MPAM', 'description' => 'Advanced degree for public service professionals', 'content' => '2 Years Full-Time / 3 Years Part-Time', 'icon' => 'fas fa-graduation-cap', 'badge' => 'POPULAR', 'status' => 1],
    ['section_key' => 'fees_structure', 'item_type' => 'program', 'title' => 'Bachelor of Business Administration', 'subtitle' => 'BBA', 'description' => 'Comprehensive business education for entrepreneurs', 'content' => '3 Years Full-Time / 4 Years Part-Time', 'icon' => 'fas fa-chart-line', 'badge' => 'NEW', 'status' => 1],
    ['section_key' => 'fees_structure', 'item_type' => 'program', 'title' => 'Advanced Diploma in Business', 'subtitle' => 'ADB', 'description' => 'Practical business skills for professionals', 'content' => '18 Months Intensive', 'icon' => 'fas fa-certificate', 'status' => 1],
];

// Insert items for online_learning_odel
$onlineItems = [
    ['section_key' => 'online_learning_odel', 'item_type' => 'feature', 'title' => 'Flexible Schedule', 'description' => 'Study at your own pace and time', 'icon' => 'fas fa-clock', 'status' => 1],
    ['section_key' => 'online_learning_odel', 'item_type' => 'feature', 'title' => 'Interactive Courses', 'description' => 'Engaging online learning materials', 'icon' => 'fas fa-laptop', 'status' => 1],
    ['section_key' => 'online_learning_odel', 'item_type' => 'feature', 'title' => 'Expert Support', 'description' => 'Access to experienced instructors', 'icon' => 'fas fa-headset', 'status' => 1],
];

// Insert items for student_support_services
$supportItems = [
    ['section_key' => 'student_support_services', 'item_type' => 'service', 'title' => 'Academic Advising', 'description' => 'Personalized guidance from academic counselors', 'icon' => 'fas fa-book-open', 'status' => 1],
    ['section_key' => 'student_support_services', 'item_type' => 'service', 'title' => 'Technical Support', 'description' => '24/7 technical assistance for online learning platform', 'icon' => 'fas fa-headset', 'status' => 1],
    ['section_key' => 'student_support_services', 'item_type' => 'service', 'title' => 'Career Services', 'description' => 'Job placement assistance and career coaching', 'icon' => 'fas fa-briefcase', 'status' => 1],
];

// Insert items for international_students
$intlItems = [
    ['section_key' => 'international_students', 'item_type' => 'feature', 'title' => 'Global Access', 'description' => 'Accessible from anywhere in the world', 'icon' => 'fas fa-globe', 'status' => 1],
    ['section_key' => 'international_students', 'item_type' => 'feature', 'title' => 'Multiple Languages', 'description' => 'Learning materials in multiple languages', 'icon' => 'fas fa-language', 'status' => 1],
    ['section_key' => 'international_students', 'item_type' => 'feature', 'title' => 'Visa Support', 'description' => 'Assistance with international enrollment', 'icon' => 'fas fa-passport', 'status' => 1],
];

// Combine all items
$allItems = array_merge($teamItems, $programItems, $onlineItems, $supportItems, $intlItems);

try {
    foreach ($allItems as $item) {
        $sectionKey = $item['section_key'];
        $itemType = $item['item_type'];
        $title = $conn->real_escape_string($item['title']);
        $subtitle = isset($item['subtitle']) ? $conn->real_escape_string($item['subtitle']) : '';
        $description = isset($item['description']) ? $conn->real_escape_string($item['description']) : '';
        $content = isset($item['content']) ? $conn->real_escape_string($item['content']) : '';
        $icon = isset($item['icon']) ? $item['icon'] : '';
        $badge = isset($item['badge']) ? $item['badge'] : '';
        $status = $item['status'];
        
        $sql = "INSERT INTO website_items (
            section_key, item_type, title, subtitle, description, content, icon, badge, status, sort_order, created_by, updated_by, created_at, updated_at
        ) VALUES (
            '$sectionKey', '$itemType', '$title', '$subtitle', '$description', '$content', '$icon', '$badge', $status, 1, 1, 1, NOW(), NOW()
        )";
        
        if (!$conn->query($sql)) {
            echo "Error inserting {$title}: " . $conn->error . "\n";
        }
    }
    
    echo "✓ Successfully populated " . count($allItems) . " items for home page\n\n";
    
    // Show summary by section
    $result = $conn->query("
        SELECT ws.title, COUNT(wi.id) as count 
        FROM website_sections ws 
        LEFT JOIN website_items wi ON ws.section_key = wi.section_key 
        WHERE ws.page_key = 'home' 
        GROUP BY ws.section_key, ws.title 
        ORDER BY ws.sort_order
    ");
    echo "Items by section:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['title']}: {$row['count']} items\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

$conn->close();
?>
