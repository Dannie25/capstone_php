# Database Migrations

## How to Run Migrations

### For New Installations
Run the SQL files in order:
1. `create_subcontract_requests_table.sql` - Creates the subcontract_requests table with all columns

### For Existing Installations (Updates)
If you already have the `subcontract_requests` table, run these migration files:
1. `add_delivery_method_to_subcontract_requests.sql` - Adds delivery_method column
2. `update_design_file_column.sql` - Updates design_file to TEXT type for multiple images

## Running via phpMyAdmin
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select your database
3. Click on "SQL" tab
4. Copy and paste the SQL content from the migration file
5. Click "Go" to execute

## Running via MySQL Command Line
```bash
mysql -u root -p your_database_name < migrations/filename.sql
```

Replace `your_database_name` with your actual database name.
