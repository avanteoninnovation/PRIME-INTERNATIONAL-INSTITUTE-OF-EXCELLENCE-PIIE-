<?php

namespace App\Helpers;

use Illuminate\Support\Facades\View;

class WebsiteRenderingHelper
{
    /**
     * Render a website section component dynamically
     * Maps section_key to specific components, with fallback to generic
     *
     * @param $section WebsiteSection model
     * @param $items Collection of WebsiteItem models
     * @param $page WebsitePage model  
     * @param $settings Collection of settings
     * @param $seo WebsiteSeoSetting model
     * @return string HTML
     */
    public static function renderSection($section, $items = null, $page = null, $settings = null, $seo = null)
    {
        // Component mapping: section_key => component name
        $componentMap = [
            'hero' => 'hero_section',
            'hero_slider' => 'hero_section',
            'hero_section' => 'hero_section',
            
            'portals' => 'portals_section',
            'portals_section' => 'portals_section',
            'portals_links' => 'portals_section',
            
            'programs' => 'programs_section',
            'programs_section' => 'programs_section',
            'academic_programmes' => 'programs_section',
            'programme_categories' => 'programs_section',
            
            'team' => 'team_section',
            'team_section' => 'team_section',
            'leadership' => 'team_section',
            'leadership_team' => 'team_section',
            
            'faqs' => 'faqs_section',
            'faqs_section' => 'faqs_section',
            'faq' => 'faqs_section',
            
            'news' => 'news_section',
            'news_section' => 'news_section',
            'news_events' => 'news_section',
            'blog' => 'news_section',
            
            'testimonials' => 'testimonials_section',
            'testimonials_section' => 'testimonials_section',
            'reviews' => 'testimonials_section',
            
            'partnerships' => 'partnerships_section',
            'partnerships_section' => 'partnerships_section',
            'affiliations' => 'partnerships_section',
            'partnerships_affiliations' => 'partnerships_section',
            
            'gallery' => 'gallery_section',
            'gallery_section' => 'gallery_section',
            'images' => 'gallery_section',
            
            'steps' => 'steps_section',
            'steps_section' => 'steps_section',
            'process' => 'steps_section',
            'admissions' => 'steps_section',
        ];

        // Get component name from mapping, or use generic
        $sectionKey = $section->section_key;
        $componentName = $componentMap[$sectionKey] ?? null;
        
        // If no specific component, use generic
        if (!$componentName) {
            $componentName = 'generic_section';
        }

        // Check if component exists
        $componentPath = 'frontend.components.' . $componentName;
        if (!View::exists($componentPath)) {
            $componentPath = 'frontend.components.generic_section';
        }

        // Render the component with data
        return view($componentPath, [
            'section' => $section,
            'items' => $items,
            'page' => $page,
            'settings' => $settings,
            'seo' => $seo,
        ])->render();
    }

    /**
     * Check if a specific component exists for a section_key
     *
     * @param string $sectionKey
     * @return bool
     */
    public static function hasSpecificComponent($sectionKey)
    {
        $components = ['hero_section', 'portals_section', 'programs_section', 'team_section', 'faqs_section', 'news_section', 'testimonials_section', 'partnerships_section', 'gallery_section', 'steps_section'];
        return in_array($sectionKey, $components);
    }
}
