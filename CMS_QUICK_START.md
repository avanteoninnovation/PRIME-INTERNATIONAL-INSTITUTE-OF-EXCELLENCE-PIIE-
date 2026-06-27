# Quick Start: CMS Admin Guide

## 🚀 5-Minute Setup

### Create Your First Page

1. Go to: **Admin Dashboard → Website Management**
2. Click: **Add Page**
3. Fill in:
   ```
   Title: "My New Page"
   Page Key: "my-page"  (must be unique, use lowercase)
   Slug: "my-page"
   Status: ✓ Active
   ```
4. Click: **Save**

---

## Add Your First Section

1. Your new page appears in the list
2. Click: **Edit** on your page
3. Click: **Add Section**
4. Fill in:
   ```
   Title: "Section Title"
   Section Key: "hero_section"  (see table below)
   Content: "Optional description"
   Image: (upload if needed)
   Status: ✓ Active
   Sort Order: 1
   ```
5. Click: **Save**

---

## Add Items to Section

1. Click: **Edit** on your section
2. Click: **Add Item**
3. Fill in:
   ```
   Title: "Item Title"
   Subtitle: "Optional subtitle"
   Description: "Short text"
   Content: "Full text"
   Image: (upload if needed)
   Icon: "fa-solid fa-star"  (optional, FontAwesome)
   Badge: "NEW" or "5"  (optional, for star ratings)
   Link: "http://example.com"  (optional)
   Button Text: "Learn More"
   Status: ✓ Active
   Sort Order: 1
   ```
4. Click: **Save**

---

## Section Keys (Copy-Paste Ready)

### Pick one, paste into Section Key field:

**Hero/Banner Sections:**
- `hero_section` - Main banner with title and image
- `hero` - Alternative name for hero
- `hero_slider` - Alternative name for hero

**Portal/Services (3-column cards with icons):**
- `portals_section` - Quick access links
- `portals_links` - Alternative name
- `portals` - Alternative name

**Programs (3-column cards with badges):**
- `programs_section` - Course/program listings
- `academic_programmes` - Alternative name
- `programs` - Alternative name

**Team/Leadership (3-column with photos):**
- `team_section` - Staff profiles
- `team` - Alternative name
- `leadership` - Alternative name
- `leadership_team` - Alternative name

**FAQ (Accordion expandable):**
- `faqs_section` - Q&A format
- `faqs` - Alternative name
- `faq` - Alternative name

**News/Blog (3-column card grid):**
- `news_section` - Article listings
- `news` - Alternative name
- `blog` - Alternative name
- `news_events` - Alternative name

**Testimonials (3-column with ratings):**
- `testimonials_section` - Client feedback
- `testimonials` - Alternative name
- `reviews` - Alternative name

**Partnerships/Logos (Logo grid):**
- `partnerships_section` - Partner logos
- `partnerships_affiliations` - Alternative name
- `affiliations` - Alternative name
- `partnerships` - Alternative name

**Gallery (Photo grid with lightbox):**
- `gallery_section` - Photo collection
- `gallery` - Alternative name
- `images` - Alternative name

**Steps/Process (Numbered guide):**
- `steps_section` - Process steps
- `steps` - Alternative name
- `process` - Alternative name
- `admissions` - Alternative name (for admission process)

**Custom (Generic - works for anything):**
- Use ANY other name (e.g., `governance`, `research_labs`, `about_content`)
- System automatically uses generic component
- Perfect for unique content

---

## FontAwesome Icons (Common Examples)

Use these in Item Icon field:

```
fa-solid fa-graduation-cap      - Graduation cap
fa-solid fa-book                - Book
fa-solid fa-users               - People/Team
fa-solid fa-briefcase           - Business
fa-solid fa-star                - Star
fa-solid fa-heart               - Heart
fa-solid fa-check               - Checkmark
fa-solid fa-trophy              - Trophy
fa-solid fa-globe               - Globe
fa-solid fa-phone               - Phone
fa-solid fa-envelope            - Email
fa-solid fa-map-pin             - Location
fa-brands fa-facebook-f         - Facebook
fa-brands fa-linkedin-in        - LinkedIn
fa-brands fa-twitter            - Twitter
fa-brands fa-instagram          - Instagram
fa-solid fa-video               - Video
fa-solid fa-calendar            - Calendar
fa-solid fa-clock               - Clock
fa-solid fa-user-tie            - Person (formal)
fa-solid fa-chalkboard-user     - Teacher
fa-solid fa-user-graduate       - Student
```

**Find more**: Visit https://fontawesome.com/icons

---

## Common Use Cases

### Use Case 1: About Page
```
Page Key: about
Page Title: About Us
---
Section: "Who We Are"
Section Key: generic_section
Content: "Our mission..."
Items: Team members or values

Result: Professional about page
```

### Use Case 2: Programs Page
```
Page Key: programs
Page Title: Our Programs
---
Section: "Academic Programs"
Section Key: programs_section
Items (each program):
  - Title: Program name
  - Icon: fa-solid fa-graduation-cap
  - Badge: FULLY ONLINE
  - Description: Program details
  - Link: Program page
  - Button Text: View Details

Result: Professional program grid
```

### Use Case 3: FAQ Page
```
Page Key: faqs
Page Title: Frequently Asked Questions
---
Section: "Your Questions Answered"
Section Key: faqs_section
Items (each question):
  - Title: Question text
  - Content: Answer text

Result: Expandable FAQ accordion
```

### Use Case 4: Team Page
```
Page Key: team
Page Title: Our Team
---
Section: "Leadership"
Section Key: team_section
Items (each person):
  - Title: Person name
  - Subtitle: Position
  - Description: Bio
  - Image: Photo

Result: Professional team grid with photos
```

### Use Case 5: News/Blog Page
```
Page Key: news
Page Title: Latest News
---
Section: "News & Updates"
Section Key: news_section
Items (each article):
  - Title: Article headline
  - Subtitle: Date or category
  - Description: Summary
  - Image: Featured image
  - Link: Full article
  - Button Text: Read More

Result: News article grid
```

---

## Image Upload Tips

- **Section Images**: Use landscape images (16:9 ratio)
  - Recommended: 800x600px or 1200x675px
  - Formats: JPG, PNG, WebP
  
- **Item Images**: Use square or portrait images (4:3 ratio)
  - Recommended: 400x300px or 600x450px
  - Formats: JPG, PNG, WebP

- **Team Photos**: Use square images
  - Recommended: 300x300px or 400x400px
  - Formats: JPG, PNG

**Maximum File Size**: 5MB per image

---

## SEO Settings (Per Page)

1. Create/Edit your page
2. Scroll to: **SEO Settings**
3. Fill in:
   ```
   Meta Title: "Page title for search engines (60 chars)"
   Meta Description: "Page description (160 chars)"
   Meta Keywords: "page, keywords, seo"
   Canonical URL: "https://yourdomain.com/page"
   ```

**Why?** Helps search engines understand your page.

---

## Status & Ordering

### Status (Active/Inactive)
- **Active (1)**: Page/section/item is visible on frontend
- **Inactive (0)**: Page/section/item is hidden

### Sort Order
- **Lower number = appears first**
- Examples:
  - Hero = 1 (appears first)
  - About = 2 (appears second)
  - Programs = 3 (appears third)

### Reorder Items
Use **Up/Down buttons** in admin list to change order without editing sort_order field.

---

## View Your Pages on Frontend

1. Go to: **Website Management**
2. Find your page in the table
3. Click: **Frontend View** (if slug exists)
4. OR Navigate directly: `/website/your-slug`

---

## Troubleshooting

### Page doesn't show on frontend
- ✓ Check Status = "Active" (1)
- ✓ Check slug is set and unique
- ✓ Check at least one section exists and is Active
- ✓ Clear browser cache (Ctrl+Shift+Delete)

### Section doesn't render correctly
- ✓ Check section_key matches list above (case-sensitive)
- ✓ Check Status = "Active" (1)
- ✓ Check at least one Item exists and is Active

### Items don't show in section
- ✓ Check item Status = "Active" (1)
- ✓ Check section_key in item matches section
- ✓ Check Sort Order is set correctly

### Images not uploading
- ✓ Check file size < 5MB
- ✓ Check format is JPG, PNG, or WebP
- ✓ Check file has proper extension

---

## Advanced Features

### Using Video URLs
In Item:
```
Video URL: "https://youtube.com/embed/VIDEO_ID"
or
Video URL: "https://vimeo.com/VIDEO_ID"
```

Components that support video can embed these automatically.

### Using Badges for Ratings
In Item:
```
Badge: "5"     → Displays 5 stars
Badge: "NEW"   → Displays "NEW" label
Badge: "HOT"   → Displays "HOT" label
Badge: "20%"   → Displays "20%" off label
```

### Using Meta JSON
In Item (Advanced):
```
Meta JSON: {"custom_field": "value"}
```
For developers to store additional data.

---

## Quick Checklist

Before launching a page:
- [ ] Page status = Active
- [ ] Page has slug
- [ ] All sections have page_key set to this page
- [ ] All sections status = Active
- [ ] All items status = Active
- [ ] Images are uploaded (if needed)
- [ ] Links are correct (http://...)
- [ ] SEO settings filled in
- [ ] Sort order is correct

---

## Get Help

1. See: **CMS_GUIDE.md** - Full documentation
2. See: **CMS_IMPLEMENTATION_REPORT.md** - Technical details
3. Ask your developer if something isn't working

---

## You're Ready! 🎉

Start creating pages and sections now. The system handles everything else automatically.

**No code. No database. No technical knowledge needed.**

Happy creating!
