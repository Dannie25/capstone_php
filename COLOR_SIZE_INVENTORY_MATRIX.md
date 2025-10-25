# Color-Size Inventory Matrix System

## Overview
Ang bagong sistema ay nagbibigay ng **matrix interface** para sa pag-manage ng inventory per color at size combination. Tulad ng nakita mo sa screenshot, makikita mo ang table na may:
- **Rows**: Mga kulay (Colors)
- **Columns**: Mga sizes (S, M, L, XL, etc.)
- **Cells**: Quantity input fields para sa bawat color-size combination

## Installation Steps

### 1. Run Database Migration
Una, kailangan mong i-run ang database migration para gumawa ng bagong table:

```
http://localhost/capstone_php/migrations/run_color_size_inventory_migration.php
```

O manually run ang SQL file sa phpMyAdmin:
```
capstone_php/migrations/20250116_add_color_size_inventory.sql
```

### 2. Verify Installation
Pagkatapos ng migration, dapat may bagong table na:
- `product_color_size_inventory` - Nag-store ng quantity per color-size combination

## How to Use

### Adding a New Product
1. Pumunta sa **Product Management** (`admin/product.php`)
2. Click **"Add New Product"**
3. Fill in product details (name, category, price, etc.)
4. **Select Sizes**: Check ang mga sizes na available (e.g., S, M, L, XL)
5. **Select Colors**: Check ang mga colors na available (e.g., Red, Blue, Black)
6. **Inventory Matrix**: Automatic na lalabas ang matrix table
   - Makikita mo ang grid na may colors sa left at sizes sa top
   - I-enter ang quantity para sa bawat color-size combination
   - Example: Red-S = 10, Red-M = 5, Red-L = 2
7. Upload product images
8. Click **"Save Product"**

### Editing a Product
1. Sa product list, click ang **Edit button** (pencil icon)
2. Makikita mo ang existing inventory sa matrix
3. Pwede mong:
   - Add/remove colors o sizes (automatic mag-update ang matrix)
   - Edit quantities sa matrix cells
   - Upload color images
4. Click **"Update Product"**

## Features

### Matrix Interface
- **Dynamic Table**: Automatic na nag-generate base sa selected colors at sizes
- **Real-time Updates**: Pag nag-check/uncheck ka ng color o size, automatic mag-update ang matrix
- **Preserve Values**: Pag nag-edit ka ng quantities, hindi mawawala kahit mag-add/remove ka ng colors/sizes
- **Visual Design**: Clean at organized table layout na madaling basahin

### Data Storage
- **Separate Table**: `product_color_size_inventory` table
  - `product_id`: Link sa product
  - `color`: Color name
  - `size`: Size name
  - `quantity`: Stock quantity
  - Unique constraint sa (product_id, color, size) para walang duplicates

### Validation
- Automatic na nag-save ng 0 kung walang quantity na na-enter
- Prevents negative quantities (min="0")
- Validates na may selected colors at sizes bago mag-show ng matrix

## Database Schema

```sql
CREATE TABLE `product_color_size_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `color` varchar(50) NOT NULL,
  `size` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_color_size` (`product_id`, `color`, `size`)
);
```

## Files Modified

### 1. `admin/product.php`
- Added `updateColorSizeInventory()` function
- Added inventory matrix HTML structure
- Added CSS styles for matrix table
- Added JavaScript functions:
  - `updateAddInventoryMatrix()` - Para sa Add modal
  - `updateEditInventoryMatrix()` - Para sa Edit modal
  - `buildInventoryMatrix()` - Builds the matrix table
- Updated form submission handlers to save inventory data
- Updated `editProduct()` function to load inventory data

### 2. `admin/get_product.php`
- Added inventory data retrieval
- Returns inventory as object with keys like "Red_S", "Blue_M", etc.

### 3. Database Migration Files
- `migrations/20250116_add_color_size_inventory.sql`
- `migrations/run_color_size_inventory_migration.php`

## Example Usage

### Sample Inventory Data
Para sa isang product na may:
- Colors: Red, Blue, Black
- Sizes: S, M, L

Ang matrix ay magiging ganito:

| Color \ Size | S  | M | L |
|--------------|----|----|---|
| Red          | 10 | 5  | 2 |
| Blue         | 7  | 8  | 6 |
| Black        | 0  | 4  | 3 |

### Database Storage
Ang data ay ise-save as individual records:
```
product_id | color | size | quantity
-----------|-------|------|----------
1          | Red   | S    | 10
1          | Red   | M    | 5
1          | Red   | L    | 2
1          | Blue  | S    | 7
1          | Blue  | M    | 8
1          | Blue  | L    | 6
1          | Black | S    | 0
1          | Black | M    | 4
1          | Black | L    | 3
```

## Benefits

1. **Granular Inventory Control**: Exact quantity tracking per color-size combination
2. **Better Stock Management**: Alam mo exactly kung anong combination ang mababa na ang stock
3. **User-Friendly Interface**: Visual matrix na madaling maintindihan at gamitin
4. **Scalable**: Pwedeng mag-add ng maraming colors at sizes without code changes
5. **Data Integrity**: Unique constraints prevent duplicate entries

## Troubleshooting

### Matrix not showing?
- Check kung may selected colors at sizes
- Verify na nag-run na ang database migration
- Check browser console for JavaScript errors

### Data not saving?
- Verify na ang `product_color_size_inventory` table exists
- Check PHP error logs
- Ensure form submission includes `inventory[]` data

### Old products not showing inventory?
- Normal lang - old products walang data sa new table
- Edit the product at i-set ang quantities sa matrix
- Save para ma-populate ang inventory data

## Support
Para sa questions o issues, check ang:
- Browser console (F12) for JavaScript errors
- PHP error logs for server-side issues
- Database structure using phpMyAdmin
