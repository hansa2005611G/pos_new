<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: /login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>📊 Reports & Analytics</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-10">
  <h1 class="text-2xl font-bold mb-8 flex items-center gap-2">
    <span>📊</span> Reports & Analytics
  </h1>
  <div class="mb-6 flex flex-wrap gap-2">
    <button onclick="showTab('lowstock')" id="tab-lowstock" class="tabBtn">Low Stock</button>
    <button onclick="showTab('stockio')" id="tab-stockio" class="tabBtn">Stock In/Out</button>
    <button onclick="showTab('invval')" id="tab-invval" class="tabBtn">Inventory Value</button>
    <button onclick="showTab('purchsup')" id="tab-purchsup" class="tabBtn">Purchase by Supplier</button>
    <button onclick="showTab('topmove')" id="tab-topmove" class="tabBtn">Top Moving</button>
    <button onclick="showTab('slowmove')" id="tab-slowmove" class="tabBtn">Slow Moving</button>
  </div>

  <!-- Dynamic Controls for some reports -->
  <div id="reportControls" class="mb-4"></div>
  <!-- Report Output -->
  <div id="reportArea"></div>
</main>
<script>
// Tab styling
document.querySelectorAll('.tabBtn').forEach(btn => {
  btn.classList.add(
    "px-4", "py-2", "rounded", "bg-white", "shadow", "hover:bg-blue-100", "transition", "border", "border-gray-200"
  );
});
function setActiveTab(tab) {
  document.querySelectorAll('.tabBtn').forEach(btn => btn.classList.remove('bg-blue-600','text-white'));
  document.getElementById('tab-'+tab).classList.add('bg-blue-600','text-white');
}

// Tab switching and AJAX loads
function showTab(tab) {
  setActiveTab(tab);
  document.getElementById('reportControls').innerHTML = ''; // Clear controls by default

  if(tab === 'lowstock') {
    fetch('ajax/report_low_stock.php').then(r=>r.text()).then(html=>reportArea.innerHTML=html);
  }
  if(tab === 'stockio') {
    // Date range controls
    let today = new Date().toISOString().slice(0,10);
    let firstDay = today.slice(0,8) + '01';
    document.getElementById('reportControls').innerHTML = `
      <label class="mr-2">From: <input type="date" id="stockioFrom" value="${firstDay}" class="border rounded px-2 py-1"></label>
      <label class="mr-2">To: <input type="date" id="stockioTo" value="${today}" class="border rounded px-2 py-1"></label>
      <button id="stockioGo" class="px-3 py-1 rounded bg-blue-600 text-white">Show</button>
    `;
    function load() {
      let from = document.getElementById('stockioFrom').value;
      let to = document.getElementById('stockioTo').value;
      fetch(`ajax/report_stock_summary.php?from=${from}&to=${to}`).then(r=>r.text()).then(html=>reportArea.innerHTML=html);
    }
    document.getElementById('reportControls').querySelector('#stockioGo').onclick = load;
    load();
  }
  if(tab === 'invval') {
    fetch('ajax/report_inventory_value.php').then(r=>r.text()).then(html=>reportArea.innerHTML=html);
  }
  if(tab === 'purchsup') {
    // Supplier dropdown
    fetch('ajax/suppliers_dropdown.php').then(r=>r.text()).then(opts=>{
      document.getElementById('reportControls').innerHTML = `
        <label>Supplier:
          <select id="rpSupplier" class="border rounded px-2 py-1">${opts}</select>
        </label>
        <button id="rpSuppGo" class="px-3 py-1 rounded bg-blue-600 text-white ml-2">Show</button>
      `;
      function load() {
        let sid = document.getElementById('rpSupplier').value;
        fetch('ajax/report_supplier_purchases.php?supplier_id=' + sid).then(r=>r.text()).then(html=>reportArea.innerHTML=html);
      }
      document.getElementById('rpSuppGo').onclick = load;
      load();
    });
  }
  if(tab === 'topmove') {
    fetch('ajax/report_top_products.php?type=top').then(r=>r.text()).then(html=>reportArea.innerHTML=html);
  }
  if(tab === 'slowmove') {
    fetch('ajax/report_top_products.php?type=slow').then(r=>r.text()).then(html=>reportArea.innerHTML=html);
  }
}
showTab('lowstock');
</script>
<style>
/* Optional: improve table look */
table { border-collapse: collapse; width: 100%; }
th, td { padding: 8px 12px; }
th { background: #f1f5f9; }
tr:nth-child(even) td { background: #f9fafb; }
</style>
</body>
</html>