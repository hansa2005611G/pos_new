<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: /login.php');
    exit;
}
$can_manage_categories = in_array($_SESSION['role'], ['admin', 'manager']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Category Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">📂 Category Management</h1>
        <div id="alertArea"></div>
        <div class="mb-4 flex items-center gap-2">
            <?php if ($can_manage_categories): ?>
                <button id="addCatBtn" class="bg-blue-600 text-white px-4 py-2 rounded">Add Category</button>
            <?php endif; ?>
        </div>
        <div id="categoryTable"></div>
        <!-- Modal -->
        <div id="categoryModal" class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden">
            <div class="flex items-center justify-center w-full h-full">
                <div class="bg-white w-full max-w-md rounded shadow-lg relative flex flex-col max-h-[90vh]">
                    <button type="button" onclick="closeCategoryModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl z-10">&times;</button>
                    <div class="overflow-y-auto p-6 flex-1">
                        <h2 id="modalTitle" class="text-lg font-bold mb-4"></h2>
                        <form id="categoryForm" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="id" id="catId" />
                            <input type="hidden" name="action" id="formAction" value="add">
                            <div>
                                <label class="block font-medium">Category Name</label>
                                <input name="name" id="catName" required class="w-full border rounded px-3 py-2" />
                            </div>
                            <div>
                                <label class="block font-medium">Description</label>
                                <textarea name="description" id="catDesc" class="w-full border rounded px-3 py-2"></textarea>
                            </div>
                            <div>
                                <label class="block font-medium">Icon (optional)</label>
                                <input type="file" name="icon" id="catIcon" accept="image/*" class="w-full border rounded px-3 py-2" />
                                <div id="iconPreview" class="mt-2"></div>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="submit" id="saveBtn" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition"></button>
                                <button type="button" onclick="closeCategoryModal()" class="ml-3 text-gray-600 underline">Cancel</button>
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
    fetch('ajax/categories_list.php')
        .then(r=>r.text())
        .then(html => document.getElementById('categoryTable').innerHTML = html);
}
function showAlert(msg, type='success') {
    document.getElementById('alertArea').innerHTML =
        `<div class="mb-4 p-3 ${type==='success'?'bg-green-100 text-green-700':'bg-red-100 text-red-700'} rounded">${msg}</div>`;
    setTimeout(() => document.getElementById('alertArea').innerHTML = '', 5000);
}
function openCategoryModal(title, btnLabel, data={}) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('saveBtn').textContent = btnLabel;
    document.getElementById('modalError').textContent = '';
    document.getElementById('categoryForm').reset();
    document.getElementById('formAction').value = data.id ? 'edit' : 'add';
    document.getElementById('catId').value = data.id || '';
    document.getElementById('catName').value = data.name || '';
    document.getElementById('catDesc').value = data.description || '';
    document.getElementById('iconPreview').innerHTML = data.icon ? `<img src="/uploads/category_icons/${data.icon}" class="h-12">` : '';
    document.getElementById('categoryModal').classList.remove('hidden');
}
function closeCategoryModal() {
    document.getElementById('categoryModal').classList.add('hidden');
}
document.getElementById('addCatBtn')?.addEventListener('click', function() {
    openCategoryModal('Add Category', 'Add');
});
document.body.addEventListener('click', function(e) {
    if (e.target.matches('.editCatBtn')) {
        let id = e.target.dataset.id;
        fetch('ajax/categories_list.php?id=' + id)
            .then(r => {
                if (!r.ok) throw new Error('Network/server error: ' + r.status);
                return r.json();
            })
            .then(res => openCategoryModal('Edit Category', 'Update', res.category))
            .catch(error => {
                showAlert("AJAX Error: " + error.message, 'error');
            });
    }
    if (e.target.matches('.deleteCatBtn')) {
        if (!confirm('Delete this category?')) return;
        let id = e.target.dataset.id;
        fetch('ajax/category_form.php', {
            method: 'POST',
            body: new URLSearchParams({action: 'delete', id})
        })
        .then(r => {
            if (!r.ok) throw new Error('Network/server error: ' + r.status);
            return r.json();
        })
        .then(res => {
            if (res.success) {
                refreshTable();
                showAlert(res.message || 'Deleted!', 'success');
            } else {
                showAlert(res.error || 'Delete failed', 'error');
            }
        })
        .catch(error => {
            showAlert("AJAX Error: " + error.message, 'error');
        });
    }
});
document.getElementById('catIcon').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        let reader = new FileReader();
        reader.onload = e => {
            document.getElementById('iconPreview').innerHTML = `<img src="${e.target.result}" class="h-12">`;
        };
        reader.readAsDataURL(this.files[0]);
    }
});
document.getElementById('categoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    let form = e.target;
    let formData = new FormData(form);
    fetch('ajax/category_form.php', {
        method: 'POST',
        body: formData
    })
    .then(r => {
        if (!r.ok) throw new Error('Network/server error: ' + r.status);
        return r.json();
    })
    .then(res => {
        if (res.success) {
            closeCategoryModal();
            refreshTable();
            showAlert(res.message || 'Category saved!', 'success');
        } else {
            let errorMsg = res.error || (res.errors ? res.errors.join(", ") : 'Unknown error');
            document.getElementById('modalError').textContent = errorMsg;
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