<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: /login.php');
    exit;
}
$can_manage_suppliers = in_array($_SESSION['role'], ['admin', 'manager']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supplier Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">📇 Supplier Management</h1>
        <div id="alertArea"></div>
        <div class="mb-4">
            <?php if ($can_manage_suppliers): ?>
                <button id="addSupplierBtn" class="bg-blue-600 text-white px-4 py-2 rounded">Add Supplier</button>
            <?php endif; ?>
        </div>
        <div id="supplierTable"></div>

        <!-- Modal -->
        <div id="supplierModal" class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden">
            <div class="flex items-center justify-center w-full h-full">
                <div class="bg-white w-full max-w-md rounded shadow-lg relative flex flex-col max-h-[90vh]">
                    <button type="button" onclick="closeSupplierModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl z-10">&times;</button>
                    <div class="overflow-y-auto p-6 flex-1">
                        <h2 id="modalTitle" class="text-lg font-bold mb-4"></h2>
                        <form id="supplierForm" class="space-y-4">
                            <input type="hidden" name="id" id="supplierId" />
                            <input type="hidden" name="action" id="formAction" value="add">
                            <div>
                                <label class="block font-medium">Name</label>
                                <input name="name" id="supplierName" required class="w-full border rounded px-3 py-2" />
                            </div>
                            <div>
                                <label class="block font-medium">Contact</label>
                                <input name="contact" id="supplierContact" class="w-full border rounded px-3 py-2" />
                            </div>
                            <div>
                                <label class="block font-medium">Email</label>
                                <input type="email" name="email" id="supplierEmail" class="w-full border rounded px-3 py-2" />
                            </div>
                            <div>
                                <label class="block font-medium">Address</label>
                                <textarea name="address" id="supplierAddress" class="w-full border rounded px-3 py-2"></textarea>
                            </div>
                            <div>
                                <label class="block font-medium">Supplied Products</label>
                                <div id="productCheckboxes" class="flex flex-wrap gap-2"></div>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="submit" id="saveBtn" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition"></button>
                                <button type="button" onclick="closeSupplierModal()" class="ml-3 text-gray-600 underline">Cancel</button>
                            </div>
                        </form>
                        <div id="modalError" class="text-red-600 mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
<script>
function refreshTable() {
    fetch('ajax/suppliers_list.php')
        .then(r=>r.text())
        .then(html => document.getElementById('supplierTable').innerHTML = html);
}
function showAlert(msg, type='success') {
    document.getElementById('alertArea').innerHTML =
        `<div class="mb-4 p-3 ${type==='success'?'bg-green-100 text-green-700':'bg-red-100 text-red-700'} rounded">${msg}</div>`;
    setTimeout(() => document.getElementById('alertArea').innerHTML = '', 5000);
}
function openSupplierModal(title, btnLabel, data={}) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('saveBtn').textContent = btnLabel;
    document.getElementById('modalError').textContent = '';
    document.getElementById('supplierForm').reset();
    document.getElementById('formAction').value = data.id ? 'edit' : 'add';
    document.getElementById('supplierId').value = data.id || '';
    document.getElementById('supplierName').value = data.name || '';
    document.getElementById('supplierContact').value = data.contact || '';
    document.getElementById('supplierEmail').value = data.email || '';
    document.getElementById('supplierAddress').value = data.address || '';
    // Load product checkboxes
    fetch('ajax/supplier_products_list.php' + (data.id ? '?supplier_id=' + data.id : ''))
        .then(r=>r.text())
        .then(html => document.getElementById('productCheckboxes').innerHTML = html);
    document.getElementById('supplierModal').classList.remove('hidden');
}
function closeSupplierModal() {
    document.getElementById('supplierModal').classList.add('hidden');
}
document.getElementById('addSupplierBtn')?.addEventListener('click', function() {
    openSupplierModal('Add Supplier', 'Add');
});
document.body.addEventListener('click', function(e) {
    if (e.target.matches('.editSupplierBtn')) {
        let id = e.target.dataset.id;
        fetch('ajax/suppliers_list.php?id=' + id)
            .then(r => r.json())
            .then(res => openSupplierModal('Edit Supplier', 'Update', res.supplier));
    }
    if (e.target.matches('.toggleActiveBtn')) {
        let id = e.target.dataset.id, action = e.target.dataset.active == "1" ? "deactivate" : "activate";
        fetch('ajax/supplier_form.php', {
            method: 'POST',
            body: new URLSearchParams({action, id})
        }).then(r => r.json()).then(res => {
            if (res.success) refreshTable();
            else showAlert(res.error || 'Action failed', 'error');
        });
    }
    if (e.target.matches('.deleteSupplierBtn')) {
        if (!confirm('Delete this supplier?')) return;
        let id = e.target.dataset.id;
        fetch('ajax/supplier_form.php', {
            method: 'POST',
            body: new URLSearchParams({action: 'delete', id})
        }).then(r => r.json()).then(res => {
            if (res.success) refreshTable();
            else showAlert(res.error || 'Delete failed', 'error');
        });
    }
});
document.getElementById('supplierForm').addEventListener('submit', function(e) {
    e.preventDefault();
    let form = e.target;
    let formData = new FormData(form);
    // Collect checked products
    document.querySelectorAll('#productCheckboxes input[type=checkbox]:checked').forEach(cb => {
        formData.append('products[]', cb.value);
    });
    fetch('ajax/supplier_form.php', {
        method: 'POST',
        body: formData
    })
    .then(r => {
        if (!r.ok) throw new Error('Network/server error: ' + r.status);
        return r.json();
    })
    .then(res => {
        if (res.success) {
            closeSupplierModal();
            refreshTable();
            showAlert(res.message || 'Supplier saved!', 'success');
        } else {
            document.getElementById('modalError').textContent = res.error || 'Error';
        }
    })
    .catch(error => {
        document.getElementById('modalError').textContent = "AJAX Error: " + error.message;
    });
});
refreshTable();
</script>
</body>
</html>