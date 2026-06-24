<?php

namespace Database\Seeders;

use App\Models\WebsiteItem;
use App\Models\WebsitePage;
use App\Models\WebsiteSection;
use App\Models\WebsiteSeoSetting;
use App\Models\WebsiteSetting;
use Illuminate\Database\Seeder;

class WebsiteContentSeeder extends Seeder
{
    public function run()
    {
        $pages = [
            ['page_key' => 'home', 'title' => 'Home', 'slug' => '/', 'status' => 1, 'sort_order' => 1],
            ['page_key' => 'about', 'title' => 'About', 'slug' => '/about', 'status' => 1, 'sort_order' => 2],
            ['page_key' => 'programs', 'title' => 'Programs', 'slug' => '/programs', 'status' => 1, 'sort_order' => 3],
            ['page_key' => 'fees', 'title' => 'Fees', 'slug' => '/fees', 'status' => 1, 'sort_order' => 4],
            ['page_key' => 'admissions', 'title' => 'Admissions', 'slug' => '/admissions', 'status' => 1, 'sort_order' => 5],
            ['page_key' => 'contact', 'title' => 'Contact', 'slug' => '/contact', 'status' => 1, 'sort_order' => 6],
        ];

        foreach ($pages as $page) {
            WebsitePage::updateOrCreate(['page_key' => $page['page_key']], $page);
        }

        $sections = [
            ['page_key' => 'home', 'section_key' => 'hero_slider', 'title' => 'Twinehs Divine Integrated Institute of Business and Technology', 'subtitle' => 'Quality Education | Practical Skills | Professional Excellence', 'content' => 'Dedicated to academic excellence, professional development, and skills enhancement. Serving students, professionals, and entrepreneurs seeking practical and career-oriented education.', 'status' => 1, 'sort_order' => 1],
            ['page_key' => 'about', 'section_key' => 'about_institution', 'title' => 'Who We Are', 'subtitle' => 'Twinehs Divine Integrated Institute of Business and Technology (TDIIBT)', 'content' => 'Twinehs Divine Integrated Institute of Business and Technology (TDIIBT) is dedicated to academic excellence, professional development, and skills enhancement. The institute serves students, professionals, and entrepreneurs seeking practical and career-oriented education.', 'status' => 1, 'sort_order' => 2],
            ['page_key' => 'about', 'section_key' => 'vision_mission_motto', 'title' => 'Vision, Mission and Motto', 'subtitle' => 'Learning for Impact', 'content' => 'Mission: To provide quality, accessible, and practical education that empowers individuals to succeed in their chosen careers. Vision: To be a leading fully online institute recognized for excellence in business and technology education in Uganda and beyond. Motto: Learning for Impact.', 'status' => 1, 'sort_order' => 3],
            ['page_key' => 'about', 'section_key' => 'core_values', 'title' => 'Core Values', 'subtitle' => 'Our values guide institutional excellence', 'content' => 'Academic excellence, integrity, innovation, inclusivity, professionalism, and impact-driven learning.', 'status' => 1, 'sort_order' => 4],
            ['page_key' => 'about', 'section_key' => 'why_choose_us', 'title' => 'Why Choose Us', 'subtitle' => 'Flexible, online, career-oriented learning', 'content' => 'TDIIBT offers fully online and virtual learning with practical, industry-relevant education designed for immediate career impact.', 'status' => 1, 'sort_order' => 5],
            ['page_key' => 'programs', 'section_key' => 'academic_programmes', 'title' => 'Academic Programmes', 'subtitle' => 'FULLY ONLINE / VIRTUAL', 'content' => 'All programs are delivered fully online through our virtual eLearning platform.', 'status' => 1, 'sort_order' => 6],
            ['page_key' => 'programs', 'section_key' => 'programme_categories', 'title' => 'Programme Categories', 'subtitle' => 'Program categories and current scope', 'content' => 'Program categories with indicative counts and progression pathways.', 'status' => 1, 'sort_order' => 7],
            ['page_key' => 'fees', 'section_key' => 'fees_structure', 'title' => 'Fees Structure', 'subtitle' => 'Fee Information', 'content' => 'Fees may be reviewed at any time. Applicants should confirm current fees with the institute before payment.', 'status' => 1, 'sort_order' => 8],
            ['page_key' => 'admissions', 'section_key' => 'admissions', 'title' => 'Admissions', 'subtitle' => 'Online admission process', 'content' => 'Provisional admission offers are issued after review. Registration must be completed within four weeks from semester commencement.', 'status' => 1, 'sort_order' => 9],
            ['page_key' => 'home', 'section_key' => 'online_learning_odel', 'title' => 'Online Learning / ODeL', 'subtitle' => 'Virtual learning through eLearning', 'content' => 'TDIIBT provides online mode of study through its eLearning portal.', 'status' => 1, 'sort_order' => 10],
            ['page_key' => 'about', 'section_key' => 'governance_structure', 'title' => 'Governance Structure', 'subtitle' => 'Institutional governance and leadership', 'content' => 'Governance supports strategic decision-making, quality assurance, and academic oversight.', 'status' => 1, 'sort_order' => 11],
            ['page_key' => 'about', 'section_key' => 'leadership_team', 'title' => 'Leadership / Team', 'subtitle' => 'Institute leadership', 'content' => 'Meet the dedicated leadership guiding TDIIBT\'s academic mission.', 'status' => 1, 'sort_order' => 12],
            ['page_key' => 'about', 'section_key' => 'director_message', 'title' => 'Director Message', 'subtitle' => 'Message from the Director', 'content' => 'Welcome to TDIIBT. We are committed to practical, quality, and accessible education for impactful careers.', 'status' => 1, 'sort_order' => 13],
            ['page_key' => 'about', 'section_key' => 'principal_message', 'title' => 'Principal Message', 'subtitle' => 'Message from the Principal', 'content' => 'Our programs are structured for excellence, flexibility, and relevance in the modern workplace.', 'status' => 1, 'sort_order' => 14],
            ['page_key' => 'about', 'section_key' => 'academic_registrar_message', 'title' => 'Academic Registrar Message', 'subtitle' => 'Message from the Academic Registrar', 'content' => 'Admissions, registration, and records management are handled with professionalism and transparency.', 'status' => 1, 'sort_order' => 15],
            ['page_key' => 'about', 'section_key' => 'institute_secretary_message', 'title' => 'Institute Secretary Message', 'subtitle' => 'Message from the Institute Secretary', 'content' => 'The institute secretariat ensures smooth administrative coordination and stakeholder support.', 'status' => 1, 'sort_order' => 16],
            ['page_key' => 'about', 'section_key' => 'strategic_plan', 'title' => 'Strategic Plan', 'subtitle' => 'Institutional strategic focus', 'content' => 'TDIIBT strategic priorities include quality online learning, partnerships, innovation, and student success.', 'status' => 1, 'sort_order' => 17],
            ['page_key' => 'about', 'section_key' => 'research_innovation', 'title' => 'Research & Innovation', 'subtitle' => 'Applied research and innovation', 'content' => 'The institute supports practical research and innovation aligned with industry and community needs.', 'status' => 1, 'sort_order' => 18],
            ['page_key' => 'about', 'section_key' => 'partnerships_affiliations', 'title' => 'Partnerships / Affiliations', 'subtitle' => 'Institutional affiliations', 'content' => 'Information about affiliations and accreditations is published as available.', 'status' => 1, 'sort_order' => 19],
            ['page_key' => 'home', 'section_key' => 'student_support_services', 'title' => 'Student Support Services', 'subtitle' => 'Academic and learner support', 'content' => 'Students receive academic guidance, portal support, and administrative assistance throughout study.', 'status' => 1, 'sort_order' => 20],
            ['page_key' => 'home', 'section_key' => 'international_students', 'title' => 'International Students', 'subtitle' => 'Inclusive online access', 'content' => 'TDIIBT welcomes learners from diverse locations through accessible online delivery.', 'status' => 1, 'sort_order' => 21],
            ['page_key' => 'home', 'section_key' => 'news_events', 'title' => 'News & Events', 'subtitle' => 'Latest updates', 'content' => 'Stay informed with institutional news, announcements, and events.', 'status' => 1, 'sort_order' => 22],
            ['page_key' => 'home', 'section_key' => 'faqs', 'title' => 'Frequently Asked Questions', 'subtitle' => 'Common questions', 'content' => 'Find quick answers about admissions, fees, online learning, and support.', 'status' => 1, 'sort_order' => 23],
            ['page_key' => 'contact', 'section_key' => 'contact_page', 'title' => 'Contact Us', 'subtitle' => 'Get in touch', 'content' => 'Reach out to us for admissions inquiries, programs, and support.', 'status' => 1, 'sort_order' => 24],
            ['page_key' => 'home', 'section_key' => 'footer_settings', 'title' => 'Footer', 'subtitle' => 'Footer information and links', 'content' => 'Footer content and contact details.', 'status' => 1, 'sort_order' => 25],
            ['page_key' => 'home', 'section_key' => 'quick_links', 'title' => 'Quick Links', 'subtitle' => 'Primary website navigation links', 'content' => 'Quick links for important pages.', 'status' => 1, 'sort_order' => 26],
            ['page_key' => 'home', 'section_key' => 'portals_links', 'title' => 'Portals Links', 'subtitle' => 'Digital portals', 'content' => 'School Management System, Student Portal, and Webmail.', 'status' => 1, 'sort_order' => 27],
            ['page_key' => 'home', 'section_key' => 'social_media_links', 'title' => 'Social Media Links', 'subtitle' => 'Official social links', 'content' => 'Follow institute channels for updates.', 'status' => 1, 'sort_order' => 28],
        ];

        foreach ($sections as $section) {
            WebsiteSection::updateOrCreate(
                ['section_key' => $section['section_key']],
                $section
            );
        }

        $items = [
            ['section_key' => 'programme_categories', 'item_type' => 'program_category', 'title' => 'Graduate Courses', 'description' => 'Master\'s and Postgraduate Diploma programs for advanced academic and professional development.', 'content' => '12 Masters, 8 PGD', 'status' => 1, 'sort_order' => 1],
            ['section_key' => 'programme_categories', 'item_type' => 'program_category', 'title' => 'Bachelor Programs', 'description' => 'Undergraduate degree programs across multiple disciplines.', 'content' => '13 Programs', 'status' => 1, 'sort_order' => 2],
            ['section_key' => 'programme_categories', 'item_type' => 'program_category', 'title' => 'Diploma Programs', 'description' => 'Specialized diploma courses for career readiness.', 'content' => '7 Programs', 'status' => 1, 'sort_order' => 3],
            ['section_key' => 'programme_categories', 'item_type' => 'program_category', 'title' => 'National Certificate - Business', 'description' => 'Comprehensive National Certificate programs in business and related fields.', 'content' => '16 Programs', 'status' => 1, 'sort_order' => 4],
            ['section_key' => 'programme_categories', 'item_type' => 'program_category', 'title' => 'Vocational Programs', 'description' => 'National Certificate programs in technical and vocational trades.', 'content' => '12 Programs', 'status' => 1, 'sort_order' => 5],
            ['section_key' => 'programme_categories', 'item_type' => 'program_category', 'title' => 'Short Courses', 'description' => 'Compact courses for quick upskilling - various durations available.', 'content' => '30+ Programs', 'status' => 1, 'sort_order' => 6],
            ['section_key' => 'programme_categories', 'item_type' => 'program_category', 'title' => 'Professional Programs', 'description' => 'Industry certifications and professional development.', 'content' => 'Coming soon / In development', 'status' => 1, 'sort_order' => 7],

            ['section_key' => 'leadership_team', 'item_type' => 'leader', 'title' => 'Twinamatsiko Naboth PhD(c)', 'subtitle' => 'Director', 'description' => 'Leading TDIIBT\'s vision for accessible and quality online education.', 'status' => 1, 'sort_order' => 1],
            ['section_key' => 'leadership_team', 'item_type' => 'leader', 'title' => 'Mr. Bendaki Evans', 'subtitle' => 'Institute Secretary', 'description' => 'Overseeing administrative operations and institutional coordination.', 'status' => 1, 'sort_order' => 2],

            ['section_key' => 'portals_links', 'item_type' => 'portal', 'title' => 'School Management System', 'link' => '/login', 'status' => 1, 'sort_order' => 1],
            ['section_key' => 'portals_links', 'item_type' => 'portal', 'title' => 'Student Portal', 'link' => '/login', 'status' => 1, 'sort_order' => 2],
            ['section_key' => 'portals_links', 'item_type' => 'portal', 'title' => 'Webmail', 'link' => 'mailto:info@tdiibt.ac.ug', 'status' => 1, 'sort_order' => 3],

            ['section_key' => 'quick_links', 'item_type' => 'quick_link', 'title' => 'Home', 'link' => '#home', 'status' => 1, 'sort_order' => 1],
            ['section_key' => 'quick_links', 'item_type' => 'quick_link', 'title' => 'About', 'link' => '#about', 'status' => 1, 'sort_order' => 2],
            ['section_key' => 'quick_links', 'item_type' => 'quick_link', 'title' => 'Programs', 'link' => '#programs', 'status' => 1, 'sort_order' => 3],
            ['section_key' => 'quick_links', 'item_type' => 'quick_link', 'title' => 'Fees', 'link' => '#fees', 'status' => 1, 'sort_order' => 4],
            ['section_key' => 'quick_links', 'item_type' => 'quick_link', 'title' => 'Admissions', 'link' => '#admissions', 'status' => 1, 'sort_order' => 5],
            ['section_key' => 'quick_links', 'item_type' => 'quick_link', 'title' => 'Contact', 'link' => '#contact', 'status' => 1, 'sort_order' => 6],

            ['section_key' => 'social_media_links', 'item_type' => 'social', 'title' => 'Facebook', 'link' => '#', 'status' => 1, 'sort_order' => 1],
            ['section_key' => 'social_media_links', 'item_type' => 'social', 'title' => 'X', 'link' => '#', 'status' => 1, 'sort_order' => 2],
            ['section_key' => 'social_media_links', 'item_type' => 'social', 'title' => 'LinkedIn', 'link' => '#', 'status' => 1, 'sort_order' => 3],
            ['section_key' => 'social_media_links', 'item_type' => 'social', 'title' => 'Instagram', 'link' => '#', 'status' => 1, 'sort_order' => 4],

            ['section_key' => 'faqs', 'item_type' => 'faq', 'title' => 'Is study fully online?', 'description' => 'Yes. TDIIBT offers fully online and virtual study through the eLearning portal.', 'status' => 1, 'sort_order' => 1],
            ['section_key' => 'faqs', 'item_type' => 'faq', 'title' => 'When is registration due?', 'description' => 'Registration should be completed within four weeks from semester commencement.', 'status' => 1, 'sort_order' => 2],
            ['section_key' => 'faqs', 'item_type' => 'faq', 'title' => 'Are fees refundable?', 'description' => 'Fees are non-refundable once registration has been processed.', 'status' => 1, 'sort_order' => 3],
        ];

        foreach ($items as $item) {
            WebsiteItem::updateOrCreate(
                ['section_key' => $item['section_key'], 'item_type' => $item['item_type'], 'title' => $item['title']],
                $item
            );
        }

        $settings = [
            'institution_name' => 'Twinehs Divine Integrated Institute of Business and Technology (TDIIBT)',
            'tagline' => 'Quality Education | Practical Skills | Professional Excellence',
            'motto' => 'Learning for Impact',
            'contact_address' => 'P.O. Box 202386 Kampala GPO',
            'contact_phone_1' => '+256 707390607',
            'contact_phone_2' => '+256 788099193',
            'contact_email' => 'info@tdiibt.ac.ug',
            'contact_website' => 'http://www.tdiibt.ac.ug',
            'director_name' => 'Twinamatsiko Naboth PhD(c)',
            'institute_secretary_name' => 'Mr. Bendaki Evans',
            'footer_copyright' => '© Twinehs Divine Integrated Institute of Business and Technology (TDIIBT). All Rights Reserved.',
            'hero_badge' => 'Fully Online / Virtual Institute',
            'program_delivery_label' => 'FULLY ONLINE / VIRTUAL',
        ];

        foreach ($settings as $key => $value) {
            WebsiteSetting::updateOrCreate(['key' => $key], ['value' => $value, 'is_json' => 0, 'status' => 1]);
        }

        foreach ($pages as $page) {
            WebsiteSeoSetting::updateOrCreate(
                ['page_key' => $page['page_key']],
                [
                    'meta_title' => 'TDIIBT - ' . $page['title'],
                    'meta_description' => 'Twinehs Divine Integrated Institute of Business and Technology (TDIIBT) official website.',
                    'meta_keywords' => 'TDIIBT, Twinehs Divine Integrated Institute of Business and Technology, online institute Uganda',
                    'canonical_url' => 'http://www.tdiibt.ac.ug',
                    'status' => 1,
                ]
            );
        }
    }
}
