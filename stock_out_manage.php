<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: /login.php');
    exit;
}
$can_manage_stock = in_array($_SESSION['role'], ['admin', 'manager', 'cashier']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Out / Sales Integration</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">📤 Stock Out / Sales Integration</h1>
        <div id="alertArea"></div>
        <div class="mb-4">
            <?php if ($can_manage_stock): ?>
                <button id="addStockOutBtn" class="bg-blue-600 text-white px-4 py-2 rounded">New Stock Out</button>
            <?php endif; ?>
        </div>
        <div id="stockOutTable"></div>

        <!-- Modal -->
        <div id="stockOutModal" class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden">
            <div class="flex items-center justify-center w-full h-full">
                <div class="bg-white w-full max-w-2xl rounded shadow-lg relative flex flex-col max-h-[90vh]">
                    <button type="button" onclick="closeStockOutModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl z-10">&times;</button>
                    <div class="overflow-y-auto p-6 flex-1">
                        <h2 id="stockOutModalTitle" class="text-lg font-bold mb-4"></h2>
                        <form id="stockOutForm" class="space-y-4">
                            <input type="hidden" name="stock_out_id" id="stockOutId" />
                            <input type="hidden" name="action" id="stockOutFormAction" value="add">
                            <div>
                                <label class="block font-medium">Date</label>
                                <input type="date" name="date" id="stockOutDate" required class="w-full border rounded px-3 py-2" value="<?= date('Y-m-d') ?>" />
                            </div>
                            <div>
                                <label class="block font-medium">Type</label>
                                <select name="type" id="stockOutType" required class="w-full border rounded px-3 py-2">
                                    <option value="sale">Sale</option>
                                    <option value="damage">Damage</option>
                                    <option value="return">Return</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-medium">Reference / Note</label>
                                <input type="text" name="reference" id="stockOutReference" class="w-full border rounded px-3 py-2" />
                            </div>
                            <div>
                                <label class="block font-medium mb-2">Products</label>
                                <div id="stockOutProductRows"></div>
                                <button type="button" onclick="addStockOutProductRow()" class="mt-2 text-sm bg-gray-200 px-2 py-1 rounded">+ Add Product</button>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="submit" id="stockOutSaveBtn" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition"></button>
                                <button type="button" onclick="closeStockOutModal()" class="ml-3 text-gray-600 underline">Cancel</button>
                            </div>
                        </form>
                        <div id="stockOutModalError" class="text-red-600 mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
<script>
function refreshStockOutTable() {
    fetch('ajax/stock_out_list.php')
        .then(r=>r.text())
        .then(html => document.getElementById('stockOutTable').innerHTML = html);
}
function showAlert(msg, type='success') {
    document.getElementById('alertArea').innerHTML =
        `<div class="mb-4 p-3 ${type==='success'?'bg-green-100 text-green-700':'bg-red-100 text-red-700'} rounded">${msg}</div>`;
    setTimeout(() => document.getElementById('alertArea').innerHTML = '', 5000);
}
function openStockOutModal(title, btnLabel, data={}) {
    document.getElementById('stockOutModalTitle').textContent = title;
    document.getElementById('stockOutSaveBtn').textContent = btnLabel;
    document.getElementById('stockOutModalError').textContent = '';
    document.getElementById('stockOutForm').reset();
    document.getElementById('stockOutFormAction').value = data.id ? 'edit' : 'add';
    document.getElementById('stockOutId').value = data.id || '';
    document.getElementById('stockOutDate').value = data.date || (new Date()).toISOString().slice(0,10);
    document.getElementById('stockOutType').value = data.type || 'sale';
    document.getElementById('stockOutReference').value = data.reference || '';
    // Fill product rows
    if (data.items) {
        document.getElementById('stockOutProductRows').innerHTML = '';
        data.items.forEach(item => addStockOutProductRow(item));
    } else {
        document.getElementById('stockOutProductRows').innerHTML = '';
        addStockOutProductRow(); // at least one row
    }
    document.getElementById('stockOutModal').classList.remove('hidden');
}
function closeStockOutModal() {
    document.getElementById('stockOutModal').classList.add('hidden');
}
function addStockOutProductRow(item={}) {
    fetch('ajax/products_dropdown.php')
        .then(r=>r.text())
        .then(productsHtml => {
            let row = document.createElement('div');
            row.className = "flex gap-2 mb-2 items-center stock-out-product-row";
            row.innerHTML = `
                <select name="product_id[]" required class="border rounded px-2 py-1">${productsHtml}</select>
                <input type="number" name="quantity[]" min="1" required class="border rounded px-2 py-1 w-20" placeholder="Qty" value="${item.quantity||''}">
                <button type="button" class="text-red-600 remove-stock-out-product-row">&times;</button>
            `;
            let select = row.querySelector('select');
            if (item.product_id) select.value = item.product_id;
            row.querySelector('.remove-stock-out-product-row').onclick = function() {
                row.parentNode.removeChild(row);
            };
            document.getElementById('stockOutProductRows').appendChild(row);
        });
}
document.getElementById('addStockOutBtn')?.addEventListener('click', function() {
    openStockOutModal('Record Stock Out', 'Save');
});
document.body.addEventListener('click', function(e) {
    if (e.target.matches('.editStockOutBtn')) {
        let id = e.target.dataset.id;
        fetch('ajax/stock_out_list.php?stock_out_id=' + id)
            .then(r => r.json())
            .then(res => openStockOutModal('Edit Stock Out', 'Update', res));
    }
    if (e.target.matches('.deleteStockOutBtn')) {
        if (!confirm('Delete this record?')) return;
        let id = e.target.dataset.id;
        fetch('ajax/stock_out_form.php', {
            method: 'POST',
            body: new URLSearchParams({action: 'delete', stock_out_id: id})
        }).then(r => r.json()).then(res => {
            if (res.success) {
                refreshStockOutTable();
                showAlert(res.message || 'Deleted!', 'success');
            } else {
                showAlert(res.error || 'Delete failed', 'error');
            }
        });
    }
});
document.getElementById('stockOutForm').addEventListener('submit', function(e) {
    e.preventDefault();
    let form = e.target;
    let formData = new FormData(form);
    fetch('ajax/stock_out_form.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            closeStockOutModal();
            refreshStockOutTable();
            showAlert(res.message || 'Saved!', 'success');
        } else {
            document.getElementById('stockOutModalError').textContent = res.error || 'Error';
        }
    })
    .catch(error => {
        document.getElementById('stockOutModalError').textContent = "AJAX Error: " + error.message;
    });
});
refreshStockOutTable();
</script>
</body>
</html>