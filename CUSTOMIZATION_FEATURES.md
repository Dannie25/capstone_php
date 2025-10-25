# Customization Features Documentation

## Overview
Enhanced `customization.php` with advanced garment customization features for MTC Clothing.

## New Features Added

### 1. **Custom Text Input**
- **Location**: Right panel, top section
- **Features**:
  - Text input field (max 50 characters)
  - Font style selector (Arial, Impact, Georgia, Courier New, Comic Sans, Times New Roman)
  - Color picker with hex display
  - Real-time preview on 3D model
  - Text rendered as texture on garment

### 2. **Pattern Picker**
- **Location**: Left panel, below size selection
- **Options**:
  - **Plain**: Solid color (default)
  - **Stripes**: Vertical white stripes pattern
  - **Floral**: Decorative floral circles
  - **Geometric**: Grid with diagonal lines
- **Implementation**: Canvas-based pattern generation applied to fabric material

### 3. **Fit & Cut Options**
- **Location**: Left panel, below pattern picker
- **Options**:
  - **Slim Fit**: Tailored & Fitted (15% narrower body, smaller sleeves)
  - **Regular Fit**: Classic Comfort (default, standard proportions)
  - **Oversized**: Relaxed & Roomy (15% wider body, longer length, larger sleeves)
- **Implementation**: Dynamic 3D model scaling based on fit selection

### 4. **Embroidery/Print Placement Tool**
- **Location**: Right panel, below logo upload
- **Placement Options**:
  - **Center**: Main chest area (default)
  - **Left Chest**: Small logo placement
  - **Right Chest**: Small logo placement
  - **Back**: Full back design
  - **Left Sleeve**: Sleeve emblem
  - **Right Sleeve**: Sleeve emblem
- **Features**:
  - Visual feedback showing selected placement
  - Automatic scaling based on placement area
  - Applies to both logo and custom text
  - 3D rotation for back and sleeve placements

## Technical Implementation

### Frontend Changes
- **CSS**: Added 200+ lines of styling for new UI components
- **JavaScript**: Enhanced 3D rendering with:
  - Pattern texture generation
  - Text-to-texture conversion
  - Dynamic placement positioning
  - Fit-based model scaling
  - Real-time preview updates

### Backend Changes
- **Form Data**: Extended to capture:
  - `custom_text`: User's custom text
  - `text_font`: Selected font family
  - `text_color`: Text color hex value
  - `logo_placement`: Selected placement position
  - `text_placement`: Text position (same as logo)
  - `pattern_type`: Selected pattern
  - `fit_type`: Selected fit option
- **Database**: Extended data stored in `special_instructions` field as JSON

### 3D Model Enhancements
- **Pattern Application**: Canvas-based textures applied to fabric material
- **Fit Adjustments**: 
  - Body width: 0.85x (slim), 1.0x (regular), 1.15x (oversized)
  - Body length: 1.6 (regular), 1.7 (oversized)
  - Sleeve radius: 0.24 (slim), 0.28 (regular), 0.32 (oversized)
- **Placement Logic**: Switch-case positioning for 6 placement zones
- **Text Rendering**: HTML5 Canvas to Three.js texture pipeline

## User Experience Flow

1. **Select Base Options** (Left Panel):
   - Choose color from palette
   - Select size (Adult/Kids)
   - Pick pattern style
   - Choose fit type

2. **Add Customizations** (Right Panel):
   - Enter custom text
   - Select font and color
   - Upload logo image
   - Choose placement position
   - Select shirt type

3. **Preview** (Center Panel):
   - Real-time 3D preview
   - Drag to rotate model
   - See all customizations applied

4. **Submit**: All data saved to database for processing

## Browser Compatibility
- Requires modern browser with:
  - HTML5 Canvas support
  - WebGL for Three.js
  - ES6 JavaScript features
  - File API for image uploads

## Future Enhancements
- Drag-and-drop positioning on 3D model
- Multiple text/logo layers
- Advanced pattern customization
- Color gradient options
- Preview from multiple angles (front/back/side buttons)
