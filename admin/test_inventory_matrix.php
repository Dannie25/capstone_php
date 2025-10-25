<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory Matrix Demo</title>
  <style>
    :root {
      --primary-color: #5b6b46;
      --light-gray: #f8f8f8;
      --border-color: #e0e0e0;
    }
    
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
      background: #fafafa;
    }
    
    .container {
      max-width: 1000px;
      margin: 0 auto;
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    h1 {
      color: #333;
      margin-bottom: 30px;
    }
    
    .controls {
      margin-bottom: 30px;
      padding: 20px;
      background: #f8f8f8;
      border-radius: 8px;
    }
    
    .controls h3 {
      margin-top: 0;
      color: #555;
    }
    
    .checkbox-group {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
      margin-bottom: 15px;
    }
    
    .checkbox-group label {
      display: flex;
      align-items: center;
      gap: 5px;
      cursor: pointer;
    }
    
    .checkbox-group input[type="checkbox"] {
      cursor: pointer;
    }
    
    /* Matrix Styles */
    .inventory-matrix-container {
      background: white;
      padding: 20px;
      border-radius: 8px;
      border: 1px solid var(--border-color);
      margin-bottom: 20px;
    }
    
    .inventory-matrix-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .inventory-matrix-header h4 {
      margin: 0;
      color: #333;
    }
    
    .inventory-matrix {
      margin: 20px 0;
      overflow-x: auto;
    }
    
    .inventory-matrix table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .inventory-matrix th {
      background: #2c2c2c;
      color: white;
      padding: 12px 8px;
      text-align: center;
      font-weight: 600;
      border: 1px solid #444;
      font-size: 14px;
    }
    
    .inventory-matrix th:first-child {
      background: transparent;
      border: none;
    }
    
    .inventory-matrix td {
      padding: 8px;
      text-align: center;
      border: 1px solid #ddd;
      background: white;
    }
    
    .inventory-matrix td:first-child {
      background: #2c2c2c;
      color: white;
      font-weight: 600;
      text-align: center;
      border: 1px solid #444;
      font-size: 14px;
    }
    
    .inventory-matrix input[type="number"] {
      width: 70px;
      padding: 6px 8px;
      border: 1px solid var(--border-color);
      border-radius: 4px;
      text-align: center;
      font-size: 14px;
    }
    
    .inventory-matrix input[type="number"]:focus {
      border-color: var(--primary-color);
      outline: none;
      box-shadow: 0 0 0 2px rgba(91, 107, 70, 0.1);
    }
    
    .inventory-matrix tr:hover td {
      background-color: rgba(217, 230, 167, 0.1);
    }
    
    .inventory-matrix tr:hover td:first-child {
      background: #3a3a3a;
    }
    
    .btn {
      padding: 10px 20px;
      background: var(--primary-color);
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      margin-top: 20px;
    }
    
    .btn:hover {
      background: #4a5a37;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>📦 Inventory Matrix Demo</h1>
    
    <div class="controls">
      <h3>Select Colors:</h3>
      <div class="checkbox-group" id="color-checkboxes">
        <label><input type="checkbox" value="White" checked> White</label>
        <label><input type="checkbox" value="Black" checked> Black</label>
        <label><input type="checkbox" value="Green" checked> Green</label>
        <label><input type="checkbox" value="Red"> Red</label>
        <label><input type="checkbox" value="Blue"> Blue</label>
      </div>
      
      <h3>Select Sizes:</h3>
      <div class="checkbox-group" id="size-checkboxes">
        <label><input type="checkbox" value="Small" checked> Small</label>
        <label><input type="checkbox" value="Medium" checked> Medium</label>
        <label><input type="checkbox" value="Large" checked> Large</label>
        <label><input type="checkbox" value="ExtraSmall" checked> ExtraSmall</label>
        <label><input type="checkbox" value="XL"> XL</label>
        <label><input type="checkbox" value="XXL"> XXL</label>
      </div>
    </div>
    
    <div class="inventory-matrix-container" id="matrix-container">
      <div class="inventory-matrix-header">
        <h4>📦 Inventory Matrix: Quantity per Size & Color</h4>
      </div>
      <div class="inventory-matrix">
        <table id="inventory-table">
          <thead>
            <tr>
              <th></th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
      <small style="color: #666;">Enter the quantity available for each size-color combination.</small>
    </div>
    
    <button class="btn" onclick="getInventoryData()">Get Inventory Data (Console)</button>
  </div>

  <script>
    // Function to build the inventory matrix table
    function buildInventoryMatrix(colors, sizes, existingData = {}) {
      const table = document.getElementById('inventory-table');
      if (!table) return;
      
      const thead = table.querySelector('thead tr');
      const tbody = table.querySelector('tbody');
      
      // Clear existing content
      thead.innerHTML = '<th style="width:120px;"></th>'; // Empty corner cell
      tbody.innerHTML = '';
      
      // Add color headers
      colors.forEach(color => {
        const th = document.createElement('th');
        th.textContent = color;
        th.style.textAlign = 'center';
        thead.appendChild(th);
      });
      
      // Add size rows
      sizes.forEach(size => {
        const tr = document.createElement('tr');
        
        // Size label in first column (same styling as color headers)
        const tdSize = document.createElement('td');
        tdSize.textContent = size;
        tr.appendChild(tdSize);
        
        // Quantity inputs for each color
        colors.forEach(color => {
          const td = document.createElement('td');
          const input = document.createElement('input');
          input.type = 'number';
          input.name = 'inventory[' + color + '_' + size + ']';
          input.min = '0';
          input.value = existingData[color + '_' + size] || '0';
          input.placeholder = '0';
          td.appendChild(input);
          tr.appendChild(td);
        });
        
        tbody.appendChild(tr);
      });
    }
    
    // Function to update matrix based on selections
    function updateMatrix() {
      const selectedColors = [];
      const selectedSizes = [];
      
      // Get selected colors
      document.querySelectorAll('#color-checkboxes input:checked').forEach(cb => {
        selectedColors.push(cb.value);
      });
      
      // Get selected sizes
      document.querySelectorAll('#size-checkboxes input:checked').forEach(cb => {
        selectedSizes.push(cb.value);
      });
      
      // Build matrix if both colors and sizes are selected
      if (selectedColors.length > 0 && selectedSizes.length > 0) {
        // Preserve existing values
        const existingData = {};
        document.querySelectorAll('#inventory-table input[type=number]').forEach(input => {
          const match = input.name.match(/inventory\[(.+)\]/);
          if (match) {
            existingData[match[1]] = input.value;
          }
        });
        
        buildInventoryMatrix(selectedColors, selectedSizes, existingData);
      } else {
        // Clear table if no selection
        const tbody = document.querySelector('#inventory-table tbody');
        if (tbody) tbody.innerHTML = '';
      }
    }
    
    // Function to get all inventory data
    function getInventoryData() {
      const data = {};
      document.querySelectorAll('#inventory-table input[type=number]').forEach(input => {
        const match = input.name.match(/inventory\[(.+)\]/);
        if (match && input.value) {
          data[match[1]] = parseInt(input.value);
        }
      });
      console.log('Inventory Data:', data);
      alert('Check console for inventory data!');
    }
    
    // Add event listeners to checkboxes
    document.querySelectorAll('#color-checkboxes input, #size-checkboxes input').forEach(cb => {
      cb.addEventListener('change', updateMatrix);
    });
    
    // Initial render
    updateMatrix();
  </script>
</body>
</html>
