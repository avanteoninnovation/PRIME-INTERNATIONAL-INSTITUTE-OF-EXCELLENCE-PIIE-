# Website Management - Quick Guide

## ✅ The Admin Interface Has Been Improved!

Your website management dashboard now has **clear action buttons** and **professional modals** making it easy to add new pages, sections, and items.

---

## What Changed

### BEFORE ❌
```
[Your page header with title]
↓
[Core Website Settings form - hard to find]
↓ (scroll down A LOT)
[Create Website Page form - mixed with settings]
↓ (scroll down more)
[Pages table with inline edit]
↓ (scroll down more)
[Create Website Section form - even harder to find]
↓ (scroll down more)
[Sections table]
↓ (scroll down even more)
[Create Website Item form - easy to miss]
↓
[Items table]
```

**Problem**: Forms are scattered and hidden. Hard to find. Requires lots of scrolling.

---

### AFTER ✅
```
┌─────────────────────────────────────────────┐
│ Website Management                          │
│ Home > Settings > Website Management       │
│                                             │
│ [+ Add Website Page]                        │
│ [+ Add Website Section]                     │
│ [+ Add Website Item]                        │
└─────────────────────────────────────────────┘
        ↓
    [Core Website Settings]
        ↓
    [Pages Table with Edit/Delete buttons]
        ↓
    [Sections Table with Edit/Delete buttons]
        ↓
    [Items Table with Edit/Delete buttons]
```

**Benefit**: Clear buttons at the top. Forms appear in professional modals. Clean interface. No searching needed.

---

## How to Use

### 1️⃣ To Create a New Website Page

**Step 1**: Click the **green button** labeled **"+ Add Website Page"**

**Step 2**: A professional form will appear in a modal popup:
```
┌─ Add Website Page ─────────────────────┐
│                                        │
│ Page Key *          [enter key]        │
│ (Use lowercase and underscores)        │
│                                        │
│ Title *             [enter title]      │
│                                        │
│ Slug                [enter slug]       │
│ (Used in URL)                          │
│                                        │
│ Meta Title          [enter title]      │
│ Meta Description    [enter desc]       │
│ Meta Keywords       [enter keywords]   │
│ Canonical URL       [enter url]        │
│ Sort Order          [0]                │
│ Status              [dropdown]         │
│                                        │
│              [Cancel] [Create Page]    │
└────────────────────────────────────────┘
```

**Step 3**: Fill in the form fields

**Step 4**: Click **"Create Page"** button

**Step 5**: The modal closes and the page reloads with your new page in the list

---

### 2️⃣ To Create a New Website Section

**Step 1**: Click the **green button** labeled **"+ Add Website Section"**

**Step 2**: A form will appear:
```
┌─ Add Website Section ──────────────────┐
│                                        │
│ Page Key *          [dropdown ▼]       │
│ (Which page to add section to)         │
│                                        │
│ Section Key *       [dropdown ▼]       │
│ (What layout/type: hero, programs...)  │
│                                        │
│ Title               [enter text]       │
│ Subtitle            [enter text]       │
│ Content             [enter text...]    │
│                                        │
│ Section Image       [choose file]      │
│ Sort Order          [0]                │
│ Status              [Active v]         │
│                                        │
│         [Cancel] [Create Section]      │
└────────────────────────────────────────┘
```

**Step 3**: Fill in the fields:
- **Page Key**: Choose which page this section belongs to
- **Section Key**: Choose the section type (e.g., "hero_section", "programs_section")
- **Title**: Give it a heading
- **Image**: Upload an image if needed

**Step 4**: Click **"Create Section"**

**Step 5**: Done! Page reloads and new section appears in the list

---

### 3️⃣ To Create a New Website Item

**Step 1**: Click the **green button** labeled **"+ Add Website Item"**

**Step 2**: A comprehensive form appears:
```
┌─ Add Website Item ─────────────────────┐
│                                        │
│ Section Key *       [dropdown ▼]       │
│ (Which section to add item to)         │
│                                        │
│ Title *             [enter title]      │
│ Subtitle            [enter text]       │
│ Description         [longer text...]   │
│ Content             [longer text...]   │
│                                        │
│ Link                [enter URL]        │
│ Button Text         [enter text]       │
│ Icon                [fontawesome]      │
│ Badge               [label or rating]  │
│ Video URL           [enter URL]        │
│ Item Image          [choose file]      │
│                                        │
│ Sort Order          [0]                │
│ Status              [Active v]         │
│                                        │
│       [Cancel] [Create Item]           │
└────────────────────────────────────────┘
```

**Step 3**: Fill in the fields based on your needs:
- **Section Key**: Choose which section this item belongs to
- **Title**: Main heading (required)
- **Subtitle**: Optional secondary text
- **Description**: Short preview text
- **Content**: Full content
- **Link**: URL for a button
- **Icon**: FontAwesome icon class (e.g., `fa-solid fa-star`)
- **Badge**: Label or star rating (e.g., `5` for 5 stars, or `NEW`)
- **Image**: Upload an image
- **Video URL**: Embed a YouTube or Vimeo video

**Step 4**: Click **"Create Item"**

**Step 5**: Done! New item appears in the list immediately

---

## Existing Functionality Still Works

### Edit Inline
- Find any page, section, or item in the table
- Click the **"Edit"** button in its row
- An edit form appears inline below the row
- Make your changes
- Click **"Update"**

### Deactivate/Activate
- Click the **"Deactivate"** or **"Activate"** button to toggle visibility
- Deactivated items are hidden from the frontend

### Reorder Items
- Click **"Up"** to move an item up in the list
- Click **"Down"** to move an item down
- Sort order determines display order on the website

### Delete Items
- Click **"Delete"** to permanently remove
- Confirm when prompted

---

## Quick Reference: Section Types

When creating a section, choose the Section Key that matches your content type:

| Section Key | Best For |
|---|---|
| `hero_section` | Main banner/hero area with image and buttons |
| `hero_slider` | Same as above (alternative name) |
| `portals_section` | Quick access links/service cards |
| `programs_section` | Course or program listings |
| `team_section` | Staff profiles with photos |
| `faqs_section` | Question and answer accordion |
| `news_section` | News articles or blog posts |
| `testimonials_section` | Client reviews with star ratings |
| `partnerships_section` | Partner or affiliate logos |
| `gallery_section` | Photo gallery with lightbox |
| `steps_section` | Process steps or instructions |
| Custom section key | Use generic component for unique content |

---

## FontAwesome Icons

For the "Icon" field, you can use any FontAwesome icon class:

```
fa-solid fa-graduation-cap    → Graduation cap
fa-solid fa-book               → Book
fa-solid fa-users              → People/Team
fa-solid fa-briefcase          → Business
fa-solid fa-star               → Star
fa-solid fa-heart              → Heart
fa-solid fa-trophy             → Trophy
fa-solid fa-globe              → Globe
fa-solid fa-phone              → Phone
fa-solid fa-envelope           → Email
fa-solid fa-map-pin            → Location
fa-brands fa-facebook-f        → Facebook
fa-brands fa-linkedin-in       → LinkedIn
fa-brands fa-twitter           → Twitter
fa-brands fa-instagram         → Instagram
```

[Find more icons at fontawesome.com](https://fontawesome.com/icons)

---

## Success! 🎉

Your new pages, sections, and items appear immediately in their respective tables after creation.

Each table shows:
- **Item details** in columns
- **Status** (Active/Inactive)
- **Order** (sort position)
- **Action buttons** (Edit, Deactivate/Activate, Up, Down, Delete)

---

## Tips

✅ **Page Key**: Use lowercase with underscores (e.g., `about_us`, `programs`, `team`)
✅ **Slug**: Used in URLs, use lowercase with hyphens (e.g., `about-us`, `programs`, `team`)
✅ **Status**: Default to "Active" when first creating
✅ **Sort Order**: Lower numbers appear first (0, 1, 2, etc.)
✅ **Meta Fields**: Help search engines understand your pages (SEO)
✅ **Icons**: Optional but make pages look better
✅ **Images**: Keep under 5MB, JPG/PNG/WebP format

---

## Need Help?

If a modal doesn't submit:
1. Check that all required fields (marked with *) are filled
2. Ensure image files are under 5MB
3. Check that you've selected dropdown values
4. Look for error messages at the bottom of the form

If page doesn't reload after creating:
1. Refresh the page manually (F5 or Cmd+R)
2. Check browser console for errors (F12)
3. Try again

---

## Summary

**Before**: Scattered forms, lots of scrolling, hard to find create functionality  
**After**: Clear buttons at top, professional modals, automatic page refresh

**Result**: Faster, easier, more intuitive website management!

Enjoy your improved admin dashboard! 🚀
