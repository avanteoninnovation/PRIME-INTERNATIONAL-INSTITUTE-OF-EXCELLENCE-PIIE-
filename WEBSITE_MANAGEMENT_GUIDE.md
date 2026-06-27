# Website Management System - Complete User Guide

## 🎉 Database Populated Successfully!

Your website management system now has sample content with:
- **14 Pages** (home, about, programs, admissions, facilities, contact, fees, and more)
- **60 Sections** (hero sliders, feature boxes, team sections, testimonials, etc.)
- **25+ Items** (programs, features, team members, testimonials, facilities, etc.)

---

## How to Edit Existing Content

### **Option 1: Quick Inline Edit**

1. **Navigate** to Website Management admin panel
2. **Scroll** to find the content table (Website Pages, Website Sections, or Website Items)
3. **Click "Edit"** button next to any item
4. An inline edit form will appear below the item
5. **Modify** the fields you want to change
6. **Click "Update"** to save changes
7. The form will close and your changes are saved to the database

#### Example - Edit Home Page:
```
1. Go to Website Management
2. Find "Home" in the Pages table
3. Click Edit next to "home"
4. Update Title: "Welcome to PIIE" → "Welcome to Our Institute"
5. Click Update button
6. Page is now updated in database!
```

---

### **Option 2: Add New Content to Existing Sections**

1. **Click** "Add Website Item" button at the top
2. **Select Section** from dropdown (e.g., "why_choose_us")
3. **Fill in Fields**:
   - Title: "New Feature"
   - Description: "Feature description"
   - Icon: "fa-solid fa-star"
   - Badge: "NEW" or "FEATURED"
4. **Click "Create Item"** button
5. New item appears in the Website Items table
6. You can immediately edit it if needed

---

### **Option 3: Add New Section to Existing Page**

1. **Click** "Add Website Section" button at the top
2. **Select Page** (e.g., "home")
3. **Select Section Type** from dropdown
4. **Fill in**:
   - Title: "Section Heading"
   - Subtitle: "Optional subtitle"
   - Content: "Section description"
5. **Click "Create Section"** button
6. New section is added to that page

---

### **Option 4: Add Completely New Page**

1. **Click** "Add Website Page" button
2. **Fill in**:
   - Page Key: "about-services" (unique identifier)
   - Title: "About Our Services"
   - Slug: "about-services" (for URL)
3. **Click "Create Page"** button
4. New page is created and ready for sections and items

---

## Key Features You Can Now Test

### ✅ Editing Functions:
- **Edit Pages**: Update title, slug, status, sort order
- **Edit Sections**: Update title, subtitle, content, image, sort order
- **Edit Items**: Update all fields (title, description, icon, badge, video_url, links, etc.)
- **Toggle Status**: Activate/Deactivate any item
- **Reorder**: Move items up/down with arrow buttons
- **Delete**: Remove items with delete button

### ✅ Form Validation:
- **Required Fields**: Page key, title are required
- **Unique Keys**: Section and page keys must be unique
- **File Uploads**: Support for images in sections and items
- **New Fields**: Icon, badge, and video_url fields for items

### ✅ Database Features:
- **Audit Trail**: created_by, updated_by columns track who made changes
- **Timestamps**: created_at, updated_at automatically recorded
- **Status Control**: Active/Inactive toggle for all content
- **Sort Order**: Customize display order of items

---

## Database Structure

### **website_pages** Table
```
Columns: id, page_key, title, slug, status, sort_order, 
         created_by, updated_by, created_at, updated_at
         
Example Records:
- home | Home | home | Active | 1
- about | About Us | about-us | Active | 2
- programs | Academic Programs | programs | Active | 3
```

### **website_sections** Table
```
Columns: id, page_key, section_key, title, subtitle, content, 
         image, status, sort_order, created_by, updated_by, created_at, updated_at
         
Example Records:
- home | hero_slider | Welcome to PIIE | ... | Active
- home | why_choose_us | Why Choose PIIE? | ... | Active
- about | vision_mission | Our Vision & Mission | ... | Active
```

### **website_items** Table
```
Columns: id, section_key, item_type, title, subtitle, description, 
         content, image, link, button_text, icon, badge, video_url,
         status, sort_order, created_by, updated_by, created_at, updated_at
         
Example Records:
- hero_slider | hero_slide | Excellence in Education | ... | fa-solid fa-book
- why_choose_us | feature_box | Curriculum | ... | fa-solid fa-book | PREMIUM
- leadership_team | team_member | Dr. Ahmed Khan | Director | ...
```

---

## Workflow Examples

### Example 1: Add a New Feature to Why Choose Us Section
```
1. Click "Add Website Item"
2. Section Key: "why_choose_us"
3. Title: "Scholarships Available"
4. Description: "We offer merit-based scholarships"
5. Icon: "fa-solid fa-dollar-sign"
6. Badge: "NEW"
7. Click Create Item
✅ New feature appears in the admin panel!
```

### Example 2: Update Home Page Title
```
1. Scroll to Website Pages table
2. Click Edit for "home" page
3. Change Title to: "Welcome to Our School"
4. Click Update
✅ Changes saved to database!
```

### Example 3: Add a Testimonial
```
1. Click "Add Website Item"
2. Section Key: "testimonials"
3. Title: Student Name
4. Subtitle: "Class of 2024, Science"
5. Description: "Student quote in quotes"
6. Badge: "⭐⭐⭐⭐⭐"
7. Click Create Item
✅ Testimonial added!
```

---

## Common Actions

| Action | Steps |
|--------|-------|
| **Edit item** | Find in table → Click Edit → Modify fields → Click Update |
| **Delete item** | Find in table → Click Delete → Confirm |
| **Reorder items** | Click Up/Down buttons to change sort order |
| **Deactivate item** | Click Deactivate button (will not show on frontend) |
| **Activate item** | Click Activate button (will show on frontend) |
| **Upload image** | Click "Choose File" in image field (JPG, PNG, WebP max 5MB) |

---

## Content You Can Now Test

### Pages Ready to Edit:
- ✅ **Home** - Hero sliders, features, programs
- ✅ **About** - Vision/mission, leadership team
- ✅ **Programs** - Available programs, program categories
- ✅ **Admissions** - Admission process steps, testimonials
- ✅ **Facilities** - Facility highlights, amenities
- ✅ **Contact** - Contact information and forms
- ✅ **Fees** - Fee structure and payment options

### Sections Ready to Test:
- Hero sliders with multiple slides
- Feature boxes with icons and badges
- Team member profiles
- Testimonials with star ratings
- Programs and courses
- Facility highlights
- Step-by-step guides

### Item Types You Can Add:
- `hero_slide` - Banner slides with images and buttons
- `feature_box` - Feature cards with icons
- `program` - Program information with links
- `team_member` - Team profile with image
- `testimonial` - Student/parent reviews
- `facility` - Facility description with icon
- `step` - Process step with badge number
- `text_card` - Text-based information card

---

## Database Query Examples

### Get all pages:
```sql
SELECT page_key, title, status FROM website_pages;
```

### Get sections for home page:
```sql
SELECT section_key, title FROM website_sections WHERE page_key = 'home';
```

### Get items for a section:
```sql
SELECT title, description, icon FROM website_items WHERE section_key = 'why_choose_us';
```

### Count content:
```sql
SELECT 
  (SELECT COUNT(*) FROM website_pages) as pages,
  (SELECT COUNT(*) FROM website_sections) as sections,
  (SELECT COUNT(*) FROM website_items) as items;
```

---

## Tips for Managing Content

1. **Use Descriptive Titles**: Make titles clear and descriptive
2. **Consistent Icons**: Use Font Awesome classes (fa-solid fa-*)
3. **Badge Consistency**: Use "NEW", "FEATURED", "POPULAR", "PREMIUM"
4. **Sort Order**: Use 1, 2, 3... to maintain logical order
5. **Status Management**: Deactivate items before major updates
6. **Backup**: Database is automatically backed up, but note IDs before deleting

---

## Next Steps

1. **✅ Explore Content**: Browse the tables and view existing content
2. **📝 Edit Content**: Try editing a few items to familiarize yourself
3. **➕ Add New Items**: Create new content for existing sections
4. **🔧 Test Features**: Try uploading images, using badges, adding links
5. **📱 Frontend View**: Use "View" button to see how content appears on website
6. **🎯 Customize**: Modify content to match your actual school information

---

## Need Help?

### Common Issues:

**Q: Can't see the tables?**
- A: Scroll down on the Website Management page to see the tables

**Q: Edit button not working?**
- A: Click Edit again, it toggles the inline edit form

**Q: Lost my changes?**
- A: Page refresh will show latest database state

**Q: Image upload failed?**
- A: Ensure file is JPG/PNG/WebP and under 5MB

**Q: Can't add item to section?**
- A: Make sure section exists first, then select it from dropdown

---

## System Status

✅ **Database**: Populated with 14 pages, 60 sections, 25+ items  
✅ **Forms**: Create, read, update, delete all working  
✅ **Validation**: Server-side validation active  
✅ **Audit Trail**: User tracking enabled  
✅ **File Uploads**: Ready for images  
✅ **Status**: READY FOR PRODUCTION  

Enjoy managing your website content! 🚀
