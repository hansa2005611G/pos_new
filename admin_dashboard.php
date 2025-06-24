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
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'admin_sidebar.php'; ?>
    <main class="flex-1 p-8 overflow-y-auto">
        <h1 class="text-3xl font-bold mb-8">Admin Dashboard</h1>
        <!-- KPI Cards -->
        <div id="kpi-cards" class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8"></div>
        <!-- Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded shadow p-4">
                <h2 class="text-lg font-semibold mb-2">Stock Movement</h2>
                <canvas id="stockMovementChart" height="150"></canvas>
            </div>
            <div class="bg-white rounded shadow p-4">
                <h2 class="text-lg font-semibold mb-2">Top-Selling Products</h2>
                <canvas id="topProductsChart" height="150"></canvas>
            </div>
        </div>
        <!-- Quick Snapshots, Activity Log, Pending Tasks/Alerts -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div>
                <div class="bg-white rounded shadow p-4 mb-6">
                    <h2 class="text-lg font-semibold mb-2">Quick Inventory Snapshots</h2>
                    <ul id="quick-snapshots" class="list-disc list-inside text-gray-700"></ul>
                </div>
                <div class="bg-white rounded shadow p-4">
                    <h2 class="text-lg font-semibold mb-2">Pending Tasks / Alerts</h2>
                    <ul id="pending-tasks" class="list-disc list-inside text-red-700"></ul>
                </div>
            </div>
            <div class="lg:col-span-2">
                <div class="bg-white rounded shadow p-4 mb-6">
                    <h2 class="text-lg font-semibold mb-2">Recent Activity Log</h2>
                    <ul id="activity-log" class="divide-y divide-gray-200 text-gray-700 max-h-64 overflow-y-auto"></ul>
                </div>
                <div class="bg-white rounded shadow p-4">
                    <h2 class="text-lg font-semibold mb-2">Admin Controls / Quick Links</h2>
                    <div class="flex flex-wrap gap-4">
                        <a href="product_form.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition text-sm font-semibold">+ Add Product</a>
                        <a href="purchase_order_form.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition text-sm font-semibold">+ Add Purchase Order</a>
                        <a href="supplier_form.php" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition text-sm font-semibold">+ Add Supplier</a>
                        <a href="products_list.php" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300 transition text-sm font-semibold">🔍 Search Inventory</a>
                        <a href="settings.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition text-sm font-semibold">⚙️ Settings</a>
                        <a href="user_permissions.php" class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 transition text-sm font-semibold">👤 Manage Users & Roles</a>
                    </div>
                </div>
                <div class="bg-white rounded shadow p-4 mt-6">
                    <h2 class="text-lg font-semibold mb-2">Module Access</h2>
                    <div class="flex flex-wrap gap-4">
                        <a href="product_manage.php" class="bg-blue-100 text-blue-800 px-4 py-2 rounded hover:bg-blue-200 transition text-sm font-semibold">View All Products</a>
                        <a href="pos_terminal.php" class="bg-green-100 text-green-800 px-4 py-2 rounded hover:bg-green-200 transition text-sm font-semibold">Open POS Terminal</a>
                        <a href="sales_history.php" class="bg-yellow-100 text-yellow-800 px-4 py-2 rounded hover:bg-yellow-200 transition text-sm font-semibold">View Sales History</a>
                        <a href="stock_adjustment_manage.php" class="bg-indigo-100 text-indigo-800 px-4 py-2 rounded hover:bg-indigo-200 transition text-sm font-semibold">Stock Adjustment Logs</a>
                        <a href="reports.php" class="bg-gray-100 text-gray-800 px-4 py-2 rounded hover:bg-gray-200 transition text-sm font-semibold">Access Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>
    // Load KPI cards
    fetch('ajax/get_dashboard_kpis.php')
      .then(r => r.json())
      .then(data => {
        document.getElementById('kpi-cards').innerHTML = `
          <div class="bg-white p-6 rounded shadow text-center">
            <div class="text-2xl font-bold text-blue-600">${data.total_products}</div>
            <div class="text-gray-600 mt-2">Total Products</div>
          </div>
          <div class="bg-white p-6 rounded shadow text-center">
            <div class="text-2xl font-bold text-yellow-600">${data.low_stock}</div>
            <div class="text-gray-600 mt-2">Low Stock Items</div>
          </div>
          <div class="bg-white p-6 rounded shadow text-center">
            <div class="text-2xl font-bold text-red-600">${data.out_of_stock}</div>
            <div class="text-gray-600 mt-2">Out of Stock Items</div>
          </div>
          <div class="bg-white p-6 rounded shadow text-center">
            <div class="text-2xl font-bold text-green-600">${data.inventory_value}</div>
            <div class="text-gray-600 mt-2">Inventory Value</div>
          </div>
          <div class="bg-white p-6 rounded shadow text-center">
            <div class="text-2xl font-bold text-indigo-600">${data.todays_sales}</div>
            <div class="text-gray-600 mt-2">Today's Sales</div>
          </div>
          <div class="bg-white p-6 rounded shadow text-center">
            <div class="text-2xl font-bold text-blue-800">${data.purchase_orders}</div>
            <div class="text-gray-600 mt-2">Recent Purchase Orders</div>
          </div>
          <div class="bg-white p-6 rounded shadow text-center">
            <div class="text-2xl font-bold text-purple-600">${data.suppliers}</div>
            <div class="text-gray-600 mt-2">Active Suppliers</div>
          </div>
        `;
      });

    // Load Quick Snapshots
    fetch('ajax/get_quick_snapshots.php')
      .then(r => r.json())
      .then(data => {
        document.getElementById('quick-snapshots').innerHTML =
          data.map(x => `<li>${x}</li>`).join('');
      });

    // Load Pending Tasks/Alerts
    fetch('ajax/get_pending_tasks.php')
      .then(r => r.json())
      .then(data => {
        document.getElementById('pending-tasks').innerHTML =
          data.map(x => `<li>${x}</li>`).join('');
      });

    // Load Activity Log
    fetch('ajax/get_activity_log.php')
      .then(r => r.json())
      .then(data => {
        document.getElementById('activity-log').innerHTML =
          data.map(x => `<li class="py-2">${x.timestamp} – <strong>${x.user}</strong>: ${x.action}</li>`).join('');
      });

    // Stock Movement Chart
    fetch('ajax/get_stock_movement.php')
      .then(r => r.json())
      .then(data => {
        new Chart(document.getElementById('stockMovementChart').getContext('2d'), {
          type: 'line',
          data: {
            labels: data.labels,
            datasets: [
              { label: "Stock In", data: data.stock_in, borderColor: "#22c55e", fill: false },
              { label: "Stock Out", data: data.stock_out, borderColor: "#ef4444", fill: false }
            ]
          },
          options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
      });

    // Top Selling Products Chart
    fetch('ajax/get_top_products.php')
      .then(r => r.json())
      .then(data => {
        new Chart(document.getElementById('topProductsChart').getContext('2d'), {
          type: 'bar',
          data: {
            labels: data.labels,
            datasets: [
              { label: "Sales", data: data.sales, backgroundColor: "#2563eb" }
            ]
          },
          options: { responsive: true, plugins: { legend: { display: false } } }
        });
      });

    </script>
</body>
</html>