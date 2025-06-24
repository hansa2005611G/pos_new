<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: /login.php');
    exit;
}
$can_manage_purchases = in_array($_SESSION['role'], ['admin', 'manager']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchasing / Stock In</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">📥 Purchasing / Stock In</h1>
        <div id="alertArea"></div>
        <div class="mb-4">
            <?php if ($can_manage_purchases): ?>
                <button id="addPOBtn" class="bg-blue-600 text-white px-4 py-2 rounded">Create Purchase Order</button>
            <?php endif; ?>
        </div>
        <div id="purchaseOrdersTable"></div>

        <!-- Modal -->
        <div id="poModal" class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden">
            <div class="flex items-center justify-center w-full h-full">
                <div class="bg-white w-full max-w-2xl rounded shadow-lg relative flex flex-col max-h-[90vh]">
                    <button type="button" onclick="closePOModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl z-10">&times;</button>
                    <div class="overflow-y-auto p-6 flex-1">
                        <h2 id="poModalTitle" class="text-lg font-bold mb-4"></h2>
                        <form id="poForm" class="space-y-4">
                            <input type="hidden" name="po_id" id="poId" />
                            <input type="hidden" name="action" id="poFormAction" value="add">
                            <div>
                                <label class="block font-medium">Supplier</label>
                                <select name="supplier_id" id="poSupplierId" required class="w-full border rounded px-3 py-2"></select>
                            </div>
                            <div>
                                <label class="block font-medium">Purchase Date</label>
                                <input type="date" name="po_date" id="poDate" required class="w-full border rounded px-3 py-2" />
                            </div>
                            <div>
                                <label class="block font-medium mb-2">Products</label>
                                <div id="poProductRows"></div>
                                <button type="button" onclick="addPOProductRow()" class="mt-2 text-sm bg-gray-200 px-2 py-1 rounded">+ Add Product</button>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="submit" id="poSaveBtn" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition"></button>
                                <button type="button" onclick="closePOModal()" class="ml-3 text-gray-600 underline">Cancel</button>
                            </div>
                        </form>
                        <div id="poModalError" class="text-red-600 mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
<script>
function refreshPOTable() {
    fetch('ajax/purchase_orders_list.php')
        .then(r=>r.text())
        .then(html => document.getElementById('purchaseOrdersTable').innerHTML = html);
}
function showAlert(msg, type='success') {
    document.getElementById('alertArea').innerHTML =
        `<div class="mb-4 p-3 ${type==='success'?'bg-green-100 text-green-700':'bg-red-100 text-red-700'} rounded">${msg}</div>`;
    setTimeout(() => document.getElementById('alertArea').innerHTML = '', 5000);
}
function openPOModal(title, btnLabel, data={}) {
    document.getElementById('poModalTitle').textContent = title;
    document.getElementById('poSaveBtn').textContent = btnLabel;
    document.getElementById('poModalError').textContent = '';
    document.getElementById('poForm').reset();
    document.getElementById('poFormAction').value = data.id ? 'edit' : 'add';
    document.getElementById('poId').value = data.id || '';
    // Fill supplier dropdown and select value if present
    fetch('ajax/suppliers_dropdown.php')
        .then(r=>r.text())
        .then(html => {
            document.getElementById('poSupplierId').innerHTML = html;
            if (data.supplier_id)
                document.getElementById('poSupplierId').value = data.supplier_id;
        });
    document.getElementById('poDate').value = data.po_date || '';
    // Fill product rows
    if (data.items) {
        document.getElementById('poProductRows').innerHTML = '';
        data.items.forEach(item => addPOProductRow(item));
    } else {
        document.getElementById('poProductRows').innerHTML = '';
        addPOProductRow(); // at least one row
    }
    document.getElementById('poModal').classList.remove('hidden');
}
function closePOModal() {
    document.getElementById('poModal').classList.add('hidden');
}
function addPOProductRow(item={}) {
    fetch('ajax/products_dropdown.php')
        .then(r=>r.text())
        .then(productsHtml => {
            let row = document.createElement('div');
            row.className = "flex gap-2 mb-2 items-center po-product-row";
            row.innerHTML = `
                <select name="product_id[]" required class="border rounded px-2 py-1">${productsHtml}</select>
                <input type="number" name="quantity[]" min="1" required class="border rounded px-2 py-1 w-20" placeholder="Qty" value="${item.quantity||''}">
                <input type="number" name="unit_cost[]" min="0" step="0.01" required class="border rounded px-2 py-1 w-24" placeholder="Unit Cost" value="${item.unit_cost||''}">
                <button type="button" class="text-red-600 remove-po-product-row">&times;</button>
            `;
            let select = row.querySelector('select');
            if (item.product_id) select.value = item.product_id;
            row.querySelector('.remove-po-product-row').onclick = function() {
                row.parentNode.removeChild(row);
            };
            document.getElementById('poProductRows').appendChild(row);
        });
}
document.getElementById('addPOBtn')?.addEventListener('click', function() {
    openPOModal('Create Purchase Order', 'Save');
});
document.body.addEventListener('click', function(e) {
    if (e.target.matches('.confirmPOBtn')) {
        let id = e.target.dataset.id;
        fetch('ajax/purchase_order_form.php', {
            method: 'POST',
            body: new URLSearchParams({action: 'confirm', po_id: id})
        }).then(r => r.json()).then(res => {
            if (res.success) {
                refreshPOTable();
                showAlert(res.message || 'Purchase confirmed!', 'success');
            } else {
                showAlert(res.error || 'Error', 'error');
            }
        });
    }
    if (e.target.matches('.editPOBtn')) {
        let id = e.target.dataset.id;
        fetch('ajax/purchase_orders_list.php?po_id=' + id)
            .then(r => r.json())
            .then(res => openPOModal('Edit Purchase Order', 'Update', res));
    }
    if (e.target.matches('.deletePOBtn')) {
        if (!confirm('Delete this purchase order?')) return;
        let id = e.target.dataset.id;
        fetch('ajax/purchase_order_form.php', {
            method: 'POST',
            body: new URLSearchParams({action: 'delete', po_id: id})
        }).then(r => r.json()).then(res => {
            if (res.success) {
                refreshPOTable();
                showAlert(res.message || 'Deleted!', 'success');
            } else {
                showAlert(res.error || 'Delete failed', 'error');
            }
        });
    }
});
document.getElementById('poForm').addEventListener('submit', function(e) {
    e.preventDefault();
    let form = e.target;
    let formData = new FormData(form);
    fetch('ajax/purchase_order_form.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            closePOModal();
            refreshPOTable();
            showAlert(res.message || 'Saved!', 'success');
        } else {
            document.getElementById('poModalError').textContent = res.error || 'Error';
        }
    })
    .catch(error => {
        document.getElementById('poModalError').textContent = "AJAX Error: " + error.message;
    });
});
refreshPOTable();
</script>
</body>
</html>