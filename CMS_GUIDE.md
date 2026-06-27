# Dynamic Website Management - Complete CMS Guide

## Overview

The website is now a **complete CMS** (Content Management System). Administrators can create unlimited pages with unlimited sections and items—all from the admin dashboard—without writing any code.

## How It Works

### 1. **Pages** (website_pages table)
- `page_key`: Internal identifier (required, must be unique, e.g., "home", "about-us", "programs")
- `title`: Page title displayed to visitors
- `slug`: URL path (e.g., "about-us" → website.com/website/about-us)
- `status`: 1 = Active, 0 = Inactive (admins can toggle)
- `sort_order`: Position in admin list

**Example pages:**
- page_key: `home` → renders at website root or /website/home
- page_key: `about` → renders at /website/about
- page_key: `programs` → renders at /website/programs

### 2. **Sections** (website_sections table)
- `page_key`: Which page this section belongs to (e.g., "home")
- `section_key`: Type of section (maps to rendering component)
- `title`: Section heading
- `subtitle`: Optional secondary heading
- `content`: Main text/description
- `image`: Optional background or featured image
- `status`: 1 = Active, 0 = Inactive
- `sort_order`: Display order within the page

### 3. **Items** (website_items table)
- `section_key`: Which section this item belongs to
- `title`: Item title
- `subtitle`: Optional subtitle
- `description`: Short description
- `content`: Full content
- `image`: Item image
- `link`: URL (for buttons/links)
- `button_text`: Button label (default: "Learn More")
- `icon`: FontAwesome icon class (e.g., "fa-solid fa-graduation-cap")
- `badge`: Badge/label text or star rating
- `video_url`: Optional video URL
- `status`: 1 = Active, 0 = Inactive
- `sort_order`: Display order within section

### 4. **Settings** (website_settings table)
Global website configuration:
- `institution_name`: Organization name
- `tagline`: Short motto
- `motto`: Main mission statement
- `logo`: Logo filename (uploaded in admin)
- `favicon`: Favicon filename
- `contact_email`, `contact_phone_1`, `contact_phone_2`
- `contact_address`, `contact_website`
- `footer_about`: Footer description
- `director_name`, `institute_secretary_name`
- `social_facebook`, `social_twitter`, `social_linkedin`, `social_instagram`
- And more...

---

## Dynamic Rendering Engine

The system automatically maps `section_key` values to reusable components. Each component has a specific design and layout optimized for that content type.

### Section Types & Components

| section_key | Component | Best For | Layout |
|---|---|---|---|
| `hero`, `hero_slider`, `hero_section` | Hero Section | Banner, main message | Full-width hero with title, content, image, buttons |
| `portals`, `portals_links` | Portals/Services | Quick access links | 3-column card grid with icons |
| `programs`, `academic_programmes` | Programs Grid | Course listings | 3-column card grid with badges |
| `team`, `leadership` | Team/Leadership | Staff profiles | 3-column card grid with photos |
| `faqs`, `faq` | FAQ/Accordion | Questions & answers | Expandable accordion |
| `news`, `blog` | News/Blog | Articles & updates | 3-column card grid with images |
| `testimonials`, `reviews` | Testimonials | Client feedback | 3-column cards with ratings |
| `partnerships`, `affiliations` | Partnerships | Partner logos | Logo grid |
| `gallery`, `images` | Gallery | Photo collection | Responsive image grid with lightbox |
| `steps`, `process`, `admissions` | Steps/Process | Multi-step guides | Numbered steps with descriptions |
| *(any other)* | Generic Section | Custom content | Title, subtitle, content, items grid |

### Generic Component

If you use a `section_key` that doesn't match any specific component, the system automatically uses the **Generic Component**, which displays:
- Section title
- Section subtitle
- Section content
- Grid of items (if any exist)
- Item images, badges, buttons

This allows unlimited flexibility—create any section without needing code updates!

---

## Example: Creating a Home Page

### Step 1: Create the Home Page
In Admin Panel → Website Management → Pages
```
Title: "Home"
Page Key: "home"
Slug: "home"
Status: Active
```

### Step 2: Add Sections to Home

#### Section 1: Hero Banner
- **Title**: "Welcome to PIIE"
- **Subtitle**: "Your Gateway to Quality Education"
- **section_key**: `hero_section`
- **Content**: "Lorem ipsum..."
- **Image**: (upload banner image)
- **Status**: Active
- **Sort Order**: 1

#### Section 2: About Us
- **Title**: "About Us"
- **section_key**: `generic_section` (or any custom key like "about_content")
- **Content**: "We are dedicated to..."
- **Status**: Active
- **Sort Order**: 2

#### Section 3: Portals
- **Title**: "Access Our Portals"
- **section_key**: `portals_section`
- **Content**: "Quick access to..."
- **Status**: Active
- **Sort Order**: 3

### Step 3: Add Items to Sections

#### For Portals Section (portal_section):
Create multiple items:

**Item 1:**
- **Title**: "Student Portal"
- **Description**: "Access your grades and courses"
- **Icon**: "fa-solid fa-user-graduate"
- **Link**: "/student-portal"
- **Button Text**: "Access Portal"
- **Status**: Active
- **Sort Order**: 1

**Item 2:**
- **Title**: "Staff Portal"
- **Description**: "Manage classes and grades"
- **Icon**: "fa-solid fa-chalkboard-user"
- **Link**: "/staff-portal"
- **Button Text**: "Access Portal"
- **Status**: Active
- **Sort Order**: 2

---

## Example: Creating a Programs Page

### Step 1: Create Page
```
Title: "Our Programs"
Page Key: "programs"
Slug: "programs"
Status: Active
```

### Step 2: Add Programs Section
- **Title**: "Academic Programs"
- **section_key**: `programs_section`
- **Content**: "Explore our wide range of programs..."
- **Status**: Active
- **Sort Order**: 1

### Step 3: Add Program Items
For each program, create an item:

**Master's in Business:**
- **Title**: "Master of Business Administration"
- **Subtitle**: "Graduate Level"
- **Icon**: "fa-solid fa-graduation-cap"
- **Badge**: "FULLY ONLINE"
- **Content**: "12 programs available"
- **Link**: "/master-business"
- **Button Text**: "View Details"
- **Status**: Active
- **Sort Order**: 1

---

## Example: Creating an FAQ Page

### Step 1: Create Page
```
Title: "Frequently Asked Questions"
Page Key: "faqs"
Slug: "faqs"
Status: Active
```

### Step 2: Add FAQ Section
- **Title**: "Your Questions Answered"
- **section_key**: `faqs_section`
- **Content**: "Find answers to common questions..."
- **Status**: Active
- **Sort Order**: 1

### Step 3: Add FAQ Items
For each question, create an item:

**FAQ 1:**
- **Title**: "What is your admission process?"
- **Description**: (leave empty or use for short answer)
- **Content**: "Our admission process involves..."
- **Status**: Active
- **Sort Order**: 1

**FAQ 2:**
- **Title**: "How much do programs cost?"
- **Content**: "Fees vary by program level..."
- **Status**: Active
- **Sort Order**: 2

---

## Example: Creating a Team Page

### Step 1: Create Page
```
Title: "Our Leadership"
Page Key: "leadership"
Slug: "leadership"
Status: Active
```

### Step 2: Add Team Section
- **Title**: "Meet Our Team"
- **section_key**: `team_section`
- **Content**: "Our dedicated leadership team..."
- **Status**: Active
- **Sort Order**: 1

### Step 3: Add Team Items
For each team member:

**Director:**
- **Title**: "Dr. John Smith"
- **Subtitle**: "Director"
- **Description**: "PhD in Educational Leadership with 20+ years of experience"
- **Image**: (upload photo)
- **Status**: Active
- **Sort Order**: 1

---

## Admin Panel Features

### Pages Table
- ✓ Create new pages
- ✓ Edit existing pages  
- ✓ Activate/Deactivate pages (toggle status)
- ✓ Reorder pages (Up/Down buttons)
- ✓ Frontend View button (if slug exists)
- ✓ Delete pages

### Sections Table (within each page)
- ✓ Add sections to any page
- ✓ Edit section title, content, image
- ✓ Upload/replace/remove section image
- ✓ Activate/Deactivate sections (toggle status)
- ✓ Reorder sections (Up/Down buttons)
- ✓ Delete sections

### Items Table (within each section)
- ✓ Add unlimited items to any section
- ✓ Edit all item properties (title, description, icon, badge, link, etc.)
- ✓ Upload/replace/remove item images
- ✓ Activate/Deactivate items (toggle status)
- ✓ Reorder items (Up/Down buttons)
- ✓ Delete items

### Settings Panel
- ✓ Configure institution name, logo, favicon
- ✓ Update contact information
- ✓ Manage social media links
- ✓ Customize footer content
- ✓ Set leadership names
- ✓ Upload logo and favicon

---

## Best Practices

### Naming Conventions

**Page Keys** (use lowercase, hyphens or underscores):
- `home` - Home page
- `about-us` or `about_us` - About page
- `programs` - Programs listing
- `faqs` - Frequently Asked Questions
- `contact` - Contact page
- `blog` - Blog/News listing
- `team` or `leadership` - Staff directory
- `admissions` - Admissions information
- `fees` - Fee structure

**Section Keys** (use specific component names when possible):
- `hero_section` - Use for hero banner sections
- `portals_section` - Use for service/portal cards
- `programs_section` - Use for program listings
- `team_section` - Use for team/staff profiles
- `news_section` - Use for news/blog articles
- `faqs_section` - Use for Q&A sections
- `testimonials_section` - Use for client testimonials
- `partnerships_section` - Use for partner logos
- `gallery_section` - Use for photo galleries
- `steps_section` - Use for processes/workflows
- Custom naming - For generic content

**File Names**:
- Use descriptive names for images
- Use lowercase, no spaces
- Examples: `hero-banner.png`, `director-photo.jpg`, `program-icon.svg`

### Content Guidelines

- **Titles**: Keep under 60 characters for best display
- **Subtitles**: Keep under 100 characters
- **Descriptions**: 50-150 characters for cards
- **Content**: Use for longer text, supports plain text
- **Links**: Use absolute URLs (http://...) or relative paths (/path)
- **Icons**: Use FontAwesome classes (fa-solid fa-..., fa-brands fa-...)
- **Images**: Recommended sizes:
  - Section images: 800x600px or 16:9 ratio
  - Item images: 400x300px or 4:3 ratio
  - Team photos: 300x300px (square)
  - Logos: 200x100px or wider

---

## Advanced: Creating Completely Custom Sections

If none of the predefined components fit your needs:

1. Use a custom `section_key` (e.g., `research_laboratories`)
2. The system automatically uses the **Generic Component** 
3. The generic component will display your section with all its items
4. You can organize items any way you want using:
   - `icon` for visual indicators
   - `badge` for labels
   - `title`, `subtitle`, `description` for text
   - Images, links, and buttons

---

## Technical: For Developers

### Adding New Components

To add a new component type:

1. Create a Blade file: `resources/views/frontend/components/my_section.blade.php`
2. Add the mapping to `app/Helpers/WebsiteRenderingHelper.php`:
   ```php
   'my_key' => 'my_section',
   ```
3. Use `section_key: my_key` when creating sections in the admin panel
4. The component receives: `$section`, `$items`, `$page`, `$settings`, `$seo`

### Component Variables

Each component receives these variables:
```blade
$section   // Current WebsiteSection model
$items     // Collection of WebsiteItem models for this section
$page      // Current WebsitePage model
$settings  // Collection of website settings
$seo       // WebsiteSeoSetting for current page (SEO metadata)
```

---

## Summary

The Dynamic Website Management module is now a **fully-functional CMS**:

✓ Create unlimited pages  
✓ Add unlimited sections to each page  
✓ Add unlimited items to each section  
✓ Change layouts by using different section_key values  
✓ Reorder pages, sections, and items  
✓ Activate/deactivate any element  
✓ Upload and manage images  
✓ No code changes required  
✓ No database schema modifications needed  
✓ No route or controller updates required  

**That's it! Start building your website today.**
