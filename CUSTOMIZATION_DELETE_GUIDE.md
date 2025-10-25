# Customization CMS - Delete Functionality Guide

## Overview
The Customization CMS page now has **two delete buttons**:
1. **Delete Card Button** - Deletes the entire card (option + image)
2. **Delete Image Button** - Deletes only the image, keeps the option

## Features Implemented

### 1. **Delete Card Button** (Red X on Top-Right of Card)
- **Purpose**: Permanently deletes the entire card including the option AND its associated image
- **Appearance**: Red button with X icon in the top-right corner of the card
- **Size**: 32px × 32px
- **Color**: Red (#e53e3e) with semi-transparent background
- **Icon**: Bootstrap Icons x-lg icon
- **Confirmation**: "Delete this entire card including the image? This action cannot be undone."
- **Location**: Positioned absolutely at top-right corner (12px from top and right) of the card
- **Hover Effect**: Scales up 1.15x and darkens to #c53030
- **Visibility**: Only appears for custom options (not default ones)
- **Z-index**: 20 (appears above everything)

### 2. **Delete Image Button** (Red Trash on Image)
- **Purpose**: Deletes only the uploaded image, keeps the option in the list
- **Appearance**: Red button with trash icon in the top-right corner of the image preview
- **Size**: 36px × 36px
- **Color**: Red (#e53e3e) with semi-transparent background
- **Icon**: Bootstrap Icons trash-fill icon
- **Confirmation**: "Are you sure you want to delete this image? This action cannot be undone."
- **Location**: Positioned absolutely in the top-right corner (8px from top and right) of the image preview
- **Hover Effect**: Scales up 1.1x and darkens to #c53030
- **Z-index**: 10 (appears above image)

## Visual Hierarchy

```
┌─────────────────────────────────┐
│  Part Card              [❌]     │ ← Delete Card Button (top-right, custom only)
├─────────────────────────────────┤
│  Title: "V-Neck"                │
│  Badge: vneck                   │
│                                 │
│  ┌───────────────────────────┐  │
│  │ [Image Preview]      [🗑️] │  │ ← Delete Image Button (on image)
│  │                           │  │
│  └───────────────────────────┘  │
│                                 │
│  [Choose File] [Upload/Replace] │ ← Upload Form (Green)
└─────────────────────────────────┘
```

**Two Delete Options:**
- **[❌] Card Button** = Delete entire card (option + image)
- **[🗑️] Image Button** = Delete only the image (keep option)

## Button Styling

### Delete Card Button
- **Size**: 32px × 32px square
- **Position**: Absolute, top-right corner of card (12px offset)
- **Background**: rgba(229, 62, 62, 0.95) - semi-transparent red
- **Border-radius**: 8px (rounded corners)
- **Icon**: Bootstrap Icons x-lg (14px)
- **Z-index**: 20 (appears above everything)
- **Hover effect**: 
  - Scales to 1.15x
  - Background darkens to #c53030
  - Enhanced shadow

### Delete Image Button
- **Size**: 36px × 36px square
- **Position**: Absolute, top-right corner of image (8px offset)
- **Background**: rgba(229, 62, 62, 0.95) - semi-transparent red
- **Border-radius**: 8px (rounded corners)
- **Icon**: Bootstrap Icons trash-fill (16px)
- **Z-index**: 10 (appears above image)
- **Hover effect**: 
  - Scales to 1.1x
  - Background darkens to #c53030
  - Enhanced shadow

## Backend Logic

### Delete Image (`delete_part`)
1. Retrieves image path from database
2. Deletes database record
3. Deletes physical file from `img/shirt_parts/` directory
4. Shows success message

### Delete Card (`delete_option`)
1. **First**: Finds and deletes associated image from `shirt_parts` table
2. **Second**: Deletes physical image file from `img/shirt_parts/` directory
3. **Third**: Deletes custom option from `shirt_part_labels` table
4. Shows success message: "Option and associated image removed successfully."
5. Only works for custom options (not default ones)

## File Location
- **Admin Page**: `admin/customization_cms.php`
- **Image Directory**: `img/shirt_parts/`
- **Database Tables**: 
  - `shirt_parts` (stores images)
  - `shirt_part_labels` (stores custom option labels)

## Usage Instructions

### To Delete Only the Image (Keep Option):
1. Hover over the image preview
2. Click the red **trash icon** button (🗑️) in the top-right corner of the image
3. Confirm: "Are you sure you want to delete this image? This action cannot be undone."
4. Image will be instantly removed from both database and file system
5. The preview will return to "No Image" state
6. The option remains in the list for future uploads

### To Delete the Entire Card (Option + Image):
1. Look at the top-right corner of the card
2. Click the red **X button** (❌) - only visible for custom options
3. Confirm: "Delete this entire card including the image? This action cannot be undone."
4. The entire card will be removed:
   - Option deleted from the list
   - Associated image deleted from database
   - Physical image file deleted from server
5. Card disappears from the grid

## Security Features
- Confirmation dialogs prevent accidental deletions
- Only admin users can access this page
- File system cleanup ensures no orphaned files
- Protected default options cannot be removed

## Improvements Made
1. ✅ **Delete Card Button** - X button on card top-right deletes entire card (option + image)
2. ✅ **Delete Image Button** - Trash icon on image deletes only the image
3. ✅ **Backend Logic Updated** - `delete_option` now deletes both option AND associated image
4. ✅ Fixed image path inconsistency (all now use `../` prefix)
5. ✅ Added clear icons: X for card deletion, trash for image deletion
6. ✅ Semi-transparent red backgrounds for visibility
7. ✅ Enhanced confirmation messages with specific context
8. ✅ Smooth hover effects (scale + color change)
9. ✅ Proper z-index layering (card button z:20, image button z:10)
10. ✅ Removed redundant "Remove Option" buttons at bottom
11. ✅ Cleaner, more intuitive UI with two clear delete options
12. ✅ Default options protected (no delete card button shown)
