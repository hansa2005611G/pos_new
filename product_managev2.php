<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: /login.php');
    exit;
}
$can_manage_products = in_array($_SESSION['role'], ['admin', 'manager']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">📦 Product Management</h1>
        <div class="mb-4 flex flex-wrap gap-2">
            <button id="addProductBtn" class="bg-blue-600 text-white px-4 py-2 rounded">Add Product</button>
        </div>
        <div id="alertArea"></div>
        <div id="productTable"><?php include 'ajax/products_list.php'; ?></div>

        <!-- Modal Form (hidden by default) -->
        <div id="productModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
            <div class="bg-white w-full max-w-xl rounded shadow-lg p-6 relative">
                <button type="button" onclick="closeProductModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
                <h2 id="modalTitle" class="text-lg font-bold mb-4"></h2>
                <form id="productForm" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="id" id="productId" />
                    <input type="hidden" name="action" id="formAction" value="add">
                    <div>
                        <label class="block font-medium">Product Name</label>
                        <input name="name" id="name" required class="w-full border rounded px-3 py-2" />
                    </div>
                    <div>
                        <label class="block font-medium">SKU</label>
                        <input name="sku" id="sku" required class="w-full border rounded px-3 py-2" />
                    </div>
                    <div>
                        <label class="block font-medium">Barcode</label>
                        <input name="barcode" id="barcode" class="w-full border rounded px-3 py-2" />
                        <div id="barcodePreview" class="mt-2"></div>
                    </div>
                    <div>
                        <label class="block font-medium">Category</label>
                        <select name="category_id" id="category_id" class="w-full border rounded px-3 py-2"></select>
                    </div>
                    <div>
                        <label class="block font-medium">Unit</label>
                        <select name="unit_id" id="unit_id" class="w-full border rounded px-3 py-2"></select>
                    </div>
                    <div>
                        <label class="block font-medium">Stock Level</label>
                        <input type="number" name="stock" id="stock" min="0" required class="w-full border rounded px-3 py-2" />
                    </div>
                    <div>
                        <label class="block font-medium">Reorder Level</label>
                        <input type="number" name="reorder_level" id="reorder_level" min="0" required class="w-full border rounded px-3 py-2" />
                    </div>
                    <div>
                        <label class="block font-medium">Description</label>
                        <textarea name="description" id="description" class="w-full border rounded px-3 py-2"></textarea>
                    </div>
                    <div>
                        <label class="block font-medium">Image</label>
                        <input type="file" name="image" id="image" accept="image/*" class="w-full border rounded px-3 py-2" />
                        <div id="imagePreview" class="mt-2"></div>
                    </div>
                    <div>
                        <button type="submit" id="saveBtn" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition"></button>
                        <button type="button" onclick="closeProductModal()" class="ml-3 text-gray-600 underline">Cancel</button>
                    </div>
                </form>
                <div id="modalError" class="text-red-600 mt-2"></div>
            </div>
        </div>
    </main>
    <script>
    // AJAX helpers and modal logic
    let editingId = null;
    function fetchCatsUnits(cb) {
        fetch('ajax/meta.php')
        .then(r => r.json())
        .then(data => {
            let catSel = document.getElementById('category_id');
            catSel.innerHTML = `<option value="">-- Select --</option>` +
                data.categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
            let unitSel = document.getElementById('unit_id');
            unitSel.innerHTML = `<option value="">-- Select --</option>` +
                data.units.map(u => `<option value="${u.id}">${u.name} (${u.abbreviation})</option>`).join('');
            if (cb) cb();
        });
    }
    function openProductModal(title, btnLabel, data={}) {
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('saveBtn').textContent = btnLabel;
        document.getElementById('modalError').textContent = '';
        document.getElementById('productForm').reset();
        document.getElementById('formAction').value = data.id ? 'edit' : 'add';
        editingId = data.id||'';
        fetchCatsUnits(() => {
            if (data.category_id) document.getElementById('category_id').value = data.category_id;
            if (data.unit_id) document.getElementById('unit_id').value = data.unit_id;
        });
        // Fill values
        document.getElementById('productId').value = data.id || '';
        document.getElementById('name').value = data.name || '';
        document.getElementById('sku').value = data.sku || '';
        document.getElementById('barcode').value = data.barcode || '';
        document.getElementById('stock').value = data.stock || 0;
        document.getElementById('reorder_level').value = data.reorder_level || 0;
        document.getElementById('description').value = data.description || '';
        document.getElementById('imagePreview').innerHTML = data.image ? `<img src="/uploads/products/${data.image}" class="h-12">` : '';
        document.getElementById('barcodePreview').innerHTML = data.barcode ? `<img src="ajax/barcode.php?barcode=${encodeURIComponent(data.barcode)}" class="h-8 inline-block">` : '';
        document.getElementById('productModal').classList.remove('hidden');
    }
    function closeProductModal() {
        document.getElementById('productModal').classList.add('hidden');
        editingId = null;
    }
    document.getElementById('addProductBtn').addEventListener('click', function() {
        openProductModal('Add Product', 'Add');
    });
    // Edit button (delegated)
    document.body.addEventListener('click', function(e) {
        if (e.target.matches('.editBtn')) {
            let id = e.target.dataset.id;
            fetch('ajax/products_list.php?id=' + id)
                .then(r => r.json())
                .then(res => openProductModal('Edit Product', 'Update', res.product));
        }
        if (e.target.matches('.deleteBtn')) {
            if (!confirm('Delete this product?')) return;
            let id = e.target.dataset.id;
            fetch('ajax/product_form.php', {
                method: 'POST',
                body: new URLSearchParams({action: 'delete', id})
            }).then(r => r.json()).then(res => {
                if (res.success) refreshTable();
                else alert(res.error || 'Delete failed');
            });
        }
    });
    // Barcode preview
    document.getElementById('barcode').addEventListener('input', function() {
        let val = this.value.trim();
        document.getElementById('barcodePreview').innerHTML = val ? `<img src="ajax/barcode.php?barcode=${encodeURIComponent(val)}" class="h-8 inline-block">` : '';
    });
    // Image preview
    document.getElementById('image').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            let reader = new FileReader();
            reader.onload = e => {
                document.getElementById('imagePreview').innerHTML = `<img src="${e.target.result}" class="h-12">`;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
    // Product form submit (add/edit)
    document.getElementById('productForm').addEventListener('submit', function(e) {
        e.preventDefault();
        let form = e.target;
        let formData = new FormData(form);
        if (editingId) formData.append('id', editingId);
        fetch('ajax/product_form.php', {
            method: 'POST',
            body: formData
        }).then(r => r.json()).then(res => {
            if (res.success) {
                closeProductModal();
                refreshTable();
                showAlert('Product saved!', 'success');
            } else {
                document.getElementById('modalError').textContent = res.error || 'Error';
            }
        });
    });
    function refreshTable() {
        fetch('ajax/products_list.php')
            .then(r=>r.text())
            .then(html => document.getElementById('productTable').innerHTML = html);
    }
    function showAlert(msg, type = 'success') {
        document.getElementById('alertArea').innerHTML = 
            `<div class="mb-4 p-3 ${type==='success'?'bg-green-100 text-green-700':'bg-red-100 text-red-700'} rounded">${msg}</div>`;
        setTimeout(() => document.getElementById('alertArea').innerHTML = '', 3000);
    }
    </script>
</body>
</html>