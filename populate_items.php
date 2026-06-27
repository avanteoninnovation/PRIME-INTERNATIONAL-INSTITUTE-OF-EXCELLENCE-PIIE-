<?php
// Direct MySQL update using mysqli - Populate home page items
$host = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'ekattor8';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// First, clear existing items
$conn->query('DELETE FROM website_items WHERE section_key IN ("hero_slider", "why_choose_us", "leadership_team", "available_programs", "testimonials", "news", "faqs")');

// Insert Hero Slider Items
$heroItems = [
    ['section_key' => 'hero_slider', 'item_type' => 'hero_slide', 'title' => 'Excellence in Online Education', 'subtitle' => 'Flexible Learning for Modern Professionals', 'description' => 'TDIIBT offers fully online and virtual education designed for busy professionals', 'content' => 'Start your journey towards success', 'badge' => 'FEATURED', 'status' => 1],
    ['section_key' => 'hero_slider', 'item_type' => 'hero_slide', 'title' => 'Master of Public Administration', 'subtitle' => 'Industry-Relevant Curriculum', 'description' => 'Advanced degree program tailored for public service professionals', 'content' => 'Enroll Now', 'badge' => 'POPULAR', 'status' => 1],
];

// Insert Why Choose Us Items
$whyItems = [
    ['section_key' => 'why_choose_us', 'item_type' => 'feature', 'title' => '100% Online Learning', 'subtitle' => 'Study anytime, anywhere', 'description' => 'Complete flexibility with our fully online delivery model', 'icon' => 'fas fa-globe', 'status' => 1],
    ['section_key' => 'why_choose_us', 'item_type' => 'feature', 'title' => 'Expert Instructors', 'subtitle' => 'Industry professionals', 'description' => 'Learn from experienced professionals with real-world expertise', 'icon' => 'fas fa-chalkboard-user', 'status' => 1],
    ['section_key' => 'why_choose_us', 'item_type' => 'feature', 'title' => 'Career Support', 'subtitle' => 'Job placement assistance', 'description' => 'Comprehensive career services and alumni network', 'icon' => 'fas fa-briefcase', 'status' => 1],
    ['section_key' => 'why_choose_us', 'item_type' => 'feature', 'title' => 'Affordable Fees', 'subtitle' => 'Flexible payment options', 'description' => 'Competitive pricing with flexible payment plans', 'icon' => 'fas fa-piggy-bank', 'status' => 1],
];

// Insert Leadership Team Items
$teamItems = [
    ['section_key' => 'leadership_team', 'item_type' => 'team_member', 'title' => 'Dr. Naboth Twinamatsiko', 'subtitle' => 'Director', 'description' => 'Visionary leader with 15+ years in education and institutional development', 'content' => 'Leading TDIIBT towards excellence and innovation', 'status' => 1],
    ['section_key' => 'leadership_team', 'item_type' => 'team_member', 'title' => 'Mr. Bendaki Evans', 'subtitle' => 'Institute Secretary', 'description' => 'Administrative expert ensuring smooth institutional operations', 'content' => 'Managing administrative coordination and stakeholder relations', 'status' => 1],
    ['section_key' => 'leadership_team', 'item_type' => 'team_member', 'title' => 'Ms. Grace Nakamya', 'subtitle' => 'Academic Registrar', 'description' => 'Dedicated to quality assurance and academic excellence', 'content' => 'Overseeing curriculum development and student success', 'status' => 1],
];

// Insert Available Programs
$programItems = [
    ['section_key' => 'available_programs', 'item_type' => 'program', 'title' => 'Master of Public Administration', 'subtitle' => 'MPAM', 'description' => 'Advanced degree for public service professionals', 'content' => '2 Years Full-Time / 3 Years Part-Time', 'icon' => 'fas fa-graduation-cap', 'badge' => 'NEW', 'button_text' => 'View Program', 'link' => '#programs', 'status' => 1],
    ['section_key' => 'available_programs', 'item_type' => 'program', 'title' => 'Bachelor of Business Administration', 'subtitle' => 'BBA', 'description' => 'Comprehensive business education for aspiring entrepreneurs', 'content' => '3 Years Full-Time / 4 Years Part-Time', 'icon' => 'fas fa-chart-line', 'badge' => 'POPULAR', 'button_text' => 'View Program', 'link' => '#programs', 'status' => 1],
    ['section_key' => 'available_programs', 'item_type' => 'program', 'title' => 'Advanced Diploma in Business', 'subtitle' => 'ADB', 'description' => 'Practical business skills for professional development', 'content' => '18 Months Intensive', 'icon' => 'fas fa-certificate', 'button_text' => 'View Program', 'link' => '#programs', 'status' => 1],
];

// Insert Testimonials
$testimonyItems = [
    ['section_key' => 'testimonials', 'item_type' => 'testimonial', 'title' => 'Excellent Online Experience', 'subtitle' => 'John Kwame', 'description' => 'The flexibility of online learning allowed me to study while working full-time. Highly recommended!', 'content' => '⭐⭐⭐⭐⭐', 'status' => 1],
    ['section_key' => 'testimonials', 'item_type' => 'testimonial', 'title' => 'Career Advancement', 'subtitle' => 'Sarah Mutebi', 'description' => 'The MPA program prepared me well for my promotion. Excellent instructors and curriculum!', 'content' => '⭐⭐⭐⭐⭐', 'status' => 1],
    ['section_key' => 'testimonials', 'item_type' => 'testimonial', 'title' => 'Professional Growth', 'subtitle' => 'David Odera', 'description' => 'TDIIBT transformed my career. The support from instructors was exceptional throughout.', 'content' => '⭐⭐⭐⭐⭐', 'status' => 1],
];

// Combine all items
$allItems = array_merge($heroItems, $whyItems, $teamItems, $programItems, $testimonyItems);

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
        $buttonText = isset($item['button_text']) ? $item['button_text'] : '';
        $link = isset($item['link']) ? $item['link'] : '';
        $status = $item['status'];
        
        $sql = "INSERT INTO website_items (
            section_key, item_type, title, subtitle, description, content, icon, badge, button_text, link, status, sort_order, created_by, updated_by, created_at, updated_at
        ) VALUES (
            '$sectionKey', '$itemType', '$title', '$subtitle', '$description', '$content', '$icon', '$badge', '$buttonText', '$link', $status, 1, 1, 1, NOW(), NOW()
        )";
        
        if (!$conn->query($sql)) {
            echo "Error inserting {$title}: " . $conn->error . "\n";
        }
    }
    
    echo "✓ Successfully populated " . count($allItems) . " items\n";
    
    // Show summary
    $result = $conn->query("SELECT section_key, COUNT(*) as count FROM website_items GROUP BY section_key ORDER BY section_key");
    echo "\nItems by section:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['section_key']}: {$row['count']} items\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

$conn->close();
?>
