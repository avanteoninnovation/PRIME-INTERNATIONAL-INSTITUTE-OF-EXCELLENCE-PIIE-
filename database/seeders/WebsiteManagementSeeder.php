<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WebsiteManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('website_items')->truncate();
        DB::table('website_sections')->truncate();
        DB::table('website_pages')->truncate();

        $now = Carbon::now();
        $adminId = 1; // Superadmin user ID

        // ==================== PAGES ====================
        $pages = [
            ['page_key' => 'home', 'title' => 'Home', 'slug' => 'home', 'status' => 1, 'sort_order' => 1],
            ['page_key' => 'about', 'title' => 'About Us', 'slug' => 'about-us', 'status' => 1, 'sort_order' => 2],
            ['page_key' => 'programs', 'title' => 'Academic Programs', 'slug' => 'programs', 'status' => 1, 'sort_order' => 3],
            ['page_key' => 'admissions', 'title' => 'Admissions', 'slug' => 'admissions', 'status' => 1, 'sort_order' => 4],
            ['page_key' => 'facilities', 'title' => 'Our Facilities', 'slug' => 'facilities', 'status' => 1, 'sort_order' => 5],
        ];

        foreach ($pages as $page) {
            DB::table('website_pages')->insert([
                'page_key' => $page['page_key'],
                'title' => $page['title'],
                'slug' => $page['slug'],
                'status' => $page['status'],
                'sort_order' => $page['sort_order'],
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ==================== HOME PAGE SECTIONS ====================
        
        // Hero Slider Section
        DB::table('website_sections')->insert([
            'page_key' => 'home',
            'section_key' => 'hero_slider',
            'title' => 'Welcome to PIIE',
            'subtitle' => 'Prime International Institute of Excellence',
            'content' => 'We provide world-class education with modern facilities and experienced faculty.',
            'image' => '/assets/hero-banner.jpg',
            'status' => 1,
            'sort_order' => 1,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Hero Items
        DB::table('website_items')->insert([
            [
                'section_key' => 'hero_slider',
                'item_type' => 'hero_slide',
                'title' => 'Excellence in Education',
                'subtitle' => 'Join thousands of successful students',
                'description' => 'Experience world-class education with state-of-the-art facilities.',
                'content' => 'Slide content for hero 1',
                'image' => '/assets/slide-1.jpg',
                'link' => '/',
                'button_text' => 'Explore More',
                'status' => 1,
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'hero_slider',
                'item_type' => 'hero_slide',
                'title' => 'Experienced Faculty',
                'subtitle' => 'Learn from the best educators',
                'description' => 'Our faculty members have decades of industry experience.',
                'content' => 'Slide content for hero 2',
                'image' => '/assets/slide-2.jpg',
                'link' => '/',
                'button_text' => 'Meet Our Team',
                'status' => 1,
                'sort_order' => 2,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Why Choose Us Section
        DB::table('website_sections')->insert([
            'page_key' => 'home',
            'section_key' => 'why_choose_us',
            'title' => 'Why Choose PIIE?',
            'subtitle' => 'We stand out because of our commitment to excellence',
            'content' => 'Choose us for your educational journey.',
            'status' => 1,
            'sort_order' => 2,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Why Choose Us Items
        DB::table('website_items')->insert([
            [
                'section_key' => 'why_choose_us',
                'item_type' => 'feature_box',
                'title' => 'World-Class Curriculum',
                'subtitle' => 'Latest Educational Standards',
                'description' => 'Our curriculum is designed to meet international standards and prepare students for global challenges.',
                'icon' => 'fa-solid fa-book',
                'badge' => 'PREMIUM',
                'status' => 1,
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'why_choose_us',
                'item_type' => 'feature_box',
                'title' => 'Modern Infrastructure',
                'subtitle' => 'State-of-the-art Facilities',
                'description' => 'We invest in modern facilities including smart classrooms, laboratories, and sports amenities.',
                'icon' => 'fa-solid fa-building',
                'badge' => 'NEW',
                'status' => 1,
                'sort_order' => 2,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'why_choose_us',
                'item_type' => 'feature_box',
                'title' => 'Expert Mentorship',
                'subtitle' => 'Guidance from Industry Experts',
                'description' => 'Our experienced faculty provides personalized guidance to help students achieve their goals.',
                'icon' => 'fa-solid fa-chalkboard-user',
                'badge' => 'FEATURED',
                'status' => 1,
                'sort_order' => 3,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'why_choose_us',
                'item_type' => 'feature_box',
                'title' => 'Holistic Development',
                'subtitle' => 'Beyond Academic Excellence',
                'description' => 'We focus on developing well-rounded individuals through sports, arts, and extracurricular activities.',
                'icon' => 'fa-solid fa-star',
                'status' => 1,
                'sort_order' => 4,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ==================== ABOUT PAGE SECTIONS ====================

        // Vision & Mission
        DB::table('website_sections')->insert([
            'page_key' => 'about',
            'section_key' => 'vision_mission',
            'title' => 'Our Vision & Mission',
            'subtitle' => 'Guiding Our Journey',
            'content' => 'We are committed to providing quality education that transforms lives.',
            'status' => 1,
            'sort_order' => 1,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Vision & Mission Items
        DB::table('website_items')->insert([
            [
                'section_key' => 'vision_mission',
                'item_type' => 'text_card',
                'title' => 'Our Vision',
                'description' => 'To become a leading educational institution recognized for academic excellence and character building.',
                'content' => 'Our vision guides every decision we make in education.',
                'icon' => 'fa-solid fa-eye',
                'status' => 1,
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'vision_mission',
                'item_type' => 'text_card',
                'title' => 'Our Mission',
                'description' => 'To provide inclusive, quality education that develops competent, confident, and caring individuals.',
                'content' => 'We strive to empower students with knowledge and values.',
                'icon' => 'fa-solid fa-rocket',
                'status' => 1,
                'sort_order' => 2,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Leadership Team
        DB::table('website_sections')->insert([
            'page_key' => 'about',
            'section_key' => 'leadership_team',
            'title' => 'Leadership Team',
            'subtitle' => 'Meet Our Visionary Leaders',
            'content' => 'Our experienced leadership team is dedicated to educational excellence.',
            'status' => 1,
            'sort_order' => 2,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Leadership Team Members
        DB::table('website_items')->insert([
            [
                'section_key' => 'leadership_team',
                'item_type' => 'team_member',
                'title' => 'Dr. Ahmed Khan',
                'subtitle' => 'Director & Founder',
                'description' => 'Visionary educator with 30+ years of experience in educational management.',
                'image' => '/assets/team/director.jpg',
                'link' => '#',
                'status' => 1,
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'leadership_team',
                'item_type' => 'team_member',
                'title' => 'Prof. Fatima Ali',
                'subtitle' => 'Academic Head',
                'description' => 'Expert in curriculum development and academic excellence with 25+ years experience.',
                'image' => '/assets/team/academic-head.jpg',
                'link' => '#',
                'status' => 1,
                'sort_order' => 2,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ==================== PROGRAMS PAGE SECTIONS ====================

        // Available Programs
        DB::table('website_sections')->insert([
            'page_key' => 'programs',
            'section_key' => 'available_programs',
            'title' => 'Academic Programs',
            'subtitle' => 'Choose Your Path to Success',
            'content' => 'We offer diverse programs tailored to different interests and career goals.',
            'status' => 1,
            'sort_order' => 1,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Program Items
        DB::table('website_items')->insert([
            [
                'section_key' => 'available_programs',
                'item_type' => 'program',
                'title' => 'Science Stream',
                'subtitle' => 'Physics, Chemistry, Biology',
                'description' => 'Comprehensive science education with hands-on laboratory experience.',
                'content' => 'Our science program emphasizes practical learning and critical thinking.',
                'link' => '/programs/science',
                'button_text' => 'Learn More',
                'icon' => 'fa-solid fa-microscope',
                'badge' => 'POPULAR',
                'status' => 1,
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'available_programs',
                'item_type' => 'program',
                'title' => 'Arts & Commerce',
                'subtitle' => 'History, Economics, Accounting',
                'description' => 'Diverse curriculum covering humanities and business studies.',
                'content' => 'Prepare for careers in humanities, social sciences, and business.',
                'link' => '/programs/arts-commerce',
                'button_text' => 'Learn More',
                'icon' => 'fa-solid fa-graduation-cap',
                'badge' => 'FEATURED',
                'status' => 1,
                'sort_order' => 2,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'available_programs',
                'item_type' => 'program',
                'title' => 'Digital & IT',
                'subtitle' => 'Technology & Computer Science',
                'description' => 'Modern technology curriculum with industry-relevant skills.',
                'content' => 'Learn the latest in software development and digital technologies.',
                'link' => '/programs/digital-it',
                'button_text' => 'Learn More',
                'icon' => 'fa-solid fa-laptop',
                'badge' => 'NEW',
                'status' => 1,
                'sort_order' => 3,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ==================== ADMISSIONS PAGE SECTIONS ====================

        // Admission Process
        DB::table('website_sections')->insert([
            'page_key' => 'admissions',
            'section_key' => 'admission_process',
            'title' => 'Admission Process',
            'subtitle' => 'How to Join PIIE',
            'content' => 'Simple and transparent admission process.',
            'status' => 1,
            'sort_order' => 1,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Admission Steps
        DB::table('website_items')->insert([
            [
                'section_key' => 'admission_process',
                'item_type' => 'step',
                'title' => 'Step 1: Application',
                'subtitle' => 'Fill Online Form',
                'description' => 'Complete the admission application form with your personal details.',
                'content' => 'Visit our portal and fill the application form carefully.',
                'badge' => '1',
                'status' => 1,
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'admission_process',
                'item_type' => 'step',
                'title' => 'Step 2: Entrance Test',
                'subtitle' => 'Assess Your Knowledge',
                'description' => 'Sit for our entrance examination to evaluate your academic level.',
                'content' => 'Our entrance test is designed to assess your current knowledge level.',
                'badge' => '2',
                'status' => 1,
                'sort_order' => 2,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'admission_process',
                'item_type' => 'step',
                'title' => 'Step 3: Interview',
                'subtitle' => 'One-on-One Discussion',
                'description' => 'Have a personal interview with our admission counselor.',
                'content' => 'Discuss your academic goals and career aspirations with us.',
                'badge' => '3',
                'status' => 1,
                'sort_order' => 3,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'admission_process',
                'item_type' => 'step',
                'title' => 'Step 4: Enrollment',
                'subtitle' => 'Begin Your Journey',
                'description' => 'Complete the enrollment process and join our student community.',
                'content' => 'Welcome to PIIE! Begin your educational journey with us.',
                'badge' => '4',
                'status' => 1,
                'sort_order' => 4,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Testimonials
        DB::table('website_sections')->insert([
            'page_key' => 'admissions',
            'section_key' => 'testimonials',
            'title' => 'What Our Students Say',
            'subtitle' => 'Success Stories from PIIE',
            'content' => 'Hear from our satisfied students.',
            'status' => 1,
            'sort_order' => 2,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Testimonial Items
        DB::table('website_items')->insert([
            [
                'section_key' => 'testimonials',
                'item_type' => 'testimonial',
                'title' => 'Sharma Rahul',
                'subtitle' => 'Class of 2023, Science',
                'description' => '"PIIE transformed my academic journey. The excellent teaching methods and supportive environment helped me score 95% in board exams."',
                'content' => 'Testimonial from successful student',
                'badge' => '⭐⭐⭐⭐⭐',
                'status' => 1,
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'testimonials',
                'item_type' => 'testimonial',
                'title' => 'Priya Jain',
                'subtitle' => 'Class of 2022, Arts',
                'description' => '"The faculty at PIIE are exceptional educators. They go beyond textbooks and make learning engaging and fun."',
                'content' => 'Testimonial from satisfied student',
                'badge' => '⭐⭐⭐⭐⭐',
                'status' => 1,
                'sort_order' => 2,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ==================== FACILITIES PAGE SECTIONS ====================

        // Our Facilities
        DB::table('website_sections')->insert([
            'page_key' => 'facilities',
            'section_key' => 'facility_highlights',
            'title' => 'Our State-of-the-Art Facilities',
            'subtitle' => 'Everything Students Need to Excel',
            'content' => 'PIIE is equipped with modern facilities to support student learning.',
            'status' => 1,
            'sort_order' => 1,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Facility Items
        DB::table('website_items')->insert([
            [
                'section_key' => 'facility_highlights',
                'item_type' => 'facility',
                'title' => 'Modern Classrooms',
                'subtitle' => 'Tech-Enabled Learning',
                'description' => 'Smart classrooms with interactive whiteboards and multimedia facilities.',
                'icon' => 'fa-solid fa-chalkboard',
                'status' => 1,
                'sort_order' => 1,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'facility_highlights',
                'item_type' => 'facility',
                'title' => 'Science Laboratories',
                'subtitle' => 'Hands-On Learning',
                'description' => 'Fully equipped physics, chemistry, and biology laboratories for practical learning.',
                'icon' => 'fa-solid fa-flask',
                'status' => 1,
                'sort_order' => 2,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'facility_highlights',
                'item_type' => 'facility',
                'title' => 'Computer Labs',
                'subtitle' => 'Technology Skills',
                'description' => 'Advanced computer labs with latest software and high-speed internet.',
                'icon' => 'fa-solid fa-computer',
                'status' => 1,
                'sort_order' => 3,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'facility_highlights',
                'item_type' => 'facility',
                'title' => 'Library & Learning Center',
                'subtitle' => 'Rich Resources',
                'description' => 'Extensive collection of books, journals, and digital resources.',
                'icon' => 'fa-solid fa-book',
                'status' => 1,
                'sort_order' => 4,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'facility_highlights',
                'item_type' => 'facility',
                'title' => 'Sports Complex',
                'subtitle' => 'Holistic Development',
                'description' => 'Complete sports facilities including basketball, volleyball, badminton courts and swimming pool.',
                'icon' => 'fa-solid fa-basketball',
                'status' => 1,
                'sort_order' => 5,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
