<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: /login.php');
    exit;
}
$can_adjust = in_array($_SESSION['role'], ['admin', 'manager']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Adjustment / Audit</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">🔁 Stock Adjustment / Audits</h1>
        <div id="alertArea"></div>
        <div class="mb-4">
            <?php if ($can_adjust): ?>
                <button id="addAdjustBtn" class="bg-blue-600 text-white px-4 py-2 rounded">Manual Stock Adjustment</button>
            <?php endif; ?>
        </div>
        <div id="adjustmentTable"></div>

        <!-- Modal -->
        <div id="adjustModal" class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden">
            <div class="flex items-center justify-center w-full h-full">
                <div class="bg-white w-full max-w-lg rounded shadow-lg relative flex flex-col max-h-[90vh]">
                    <button type="button" onclick="closeAdjustModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl z-10">&times;</button>
                    <div class="overflow-y-auto p-6 flex-1">
                        <h2 id="adjustModalTitle" class="text-lg font-bold mb-4">Manual Stock Adjustment</h2>
                        <form id="adjustForm" class="space-y-4">
                            <input type="hidden" name="action" value="add">
                            <div>
                                <label class="block font-medium">Date</label>
                                <input type="date" name="date" required class="w-full border rounded px-3 py-2" value="<?= date('Y-m-d') ?>" />
                            </div>
                            <div>
                                <label class="block font-medium">Product</label>
                                <select name="product_id" id="adjProductId" required class="w-full border rounded px-3 py-2"></select>
                                <div id="adjStockInfo" class="text-sm text-gray-500 mt-1"></div>
                            </div>
                            <div>
                                <label class="block font-medium">New Stock Level</label>
                                <input type="number" name="new_stock" id="adjNewStock" required class="w-full border rounded px-3 py-2" min="0" />
                            </div>
                            <div>
                                <label class="block font-medium">Reason</label>
                                <input type="text" name="reason" required class="w-full border rounded px-3 py-2" placeholder="e.g. Theft, audit correction, error" />
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">Adjust</button>
                                <button type="button" onclick="closeAdjustModal()" class="ml-3 text-gray-600 underline">Cancel</button>
                            </div>
                        </form>
                        <div id="adjustModalError" class="text-red-600 mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
<script>
function refreshAdjustTable() {
    fetch('ajax/stock_adjustment_list.php')
        .then(r=>r.text())
        .then(html => document.getElementById('adjustmentTable').innerHTML = html);
}
function showAlert(msg, type='success') {
    document.getElementById('alertArea').innerHTML =
        `<div class="mb-4 p-3 ${type==='success'?'bg-green-100 text-green-700':'bg-red-100 text-red-700'} rounded">${msg}</div>`;
    setTimeout(() => document.getElementById('alertArea').innerHTML = '', 5000);
}
function openAdjustModal() {
    document.getElementById('adjustModalTitle').textContent = "Manual Stock Adjustment";
    document.getElementById('adjustModalError').textContent = '';
    document.getElementById('adjustForm').reset();
    // Fetch products
    fetch('ajax/products_dropdown.php')
        .then(r=>r.text())
        .then(html => document.getElementById('adjProductId').innerHTML = html);
    document.getElementById('adjStockInfo').textContent = '';
    document.getElementById('adjustModal').classList.remove('hidden');
}
function closeAdjustModal() {
    document.getElementById('adjustModal').classList.add('hidden');
}
document.getElementById('addAdjustBtn')?.addEventListener('click', function() {
    openAdjustModal();
});
document.getElementById('adjProductId')?.addEventListener('change', function() {
    let pid = this.value;
    if (!pid) return;
    fetch('ajax/product_stock.php?product_id=' + pid)
        .then(r=>r.text())
        .then(stock => {
            document.getElementById('adjStockInfo').textContent = "Current stock: " + stock;
        });
});
document.getElementById('adjustForm').addEventListener('submit', function(e) {
    e.preventDefault();
    let form = e.target;
    let formData = new FormData(form);
    fetch('ajax/stock_adjustment_form.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            closeAdjustModal();
            refreshAdjustTable();
            showAlert(res.message || 'Adjustment recorded!', 'success');
        } else {
            document.getElementById('adjustModalError').textContent = res.error || 'Error';
        }
    })
    .catch(error => {
        document.getElementById('adjustModalError').textContent = "AJAX Error: " + error.message;
    });
});
refreshAdjustTable();
</script>
</body>
</html>