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
    <title>All Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <?php include 'admin_sidebar.php'; ?>
    <main class="flex-1 p-8">
        <h1 class="text-3xl font-bold mb-6">All Products</h1>
        <!-- Search and Add Product -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-4">
            <input id="search" type="text" class="p-2 border rounded w-full md:w-1/3" placeholder="Search by name, SKU, category, supplier...">
            <a href="product_form.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">+ Add Product</a>
        </div>
        <!-- Table -->
        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="min-w-full table-auto" id="products-table">
                <thead class="bg-blue-100">
                    <tr>
                        <th class="px-4 py-2 text-left">#</th>
                        <th class="px-4 py-2 text-left">Name</th>
                        <th class="px-4 py-2 text-left">SKU</th>
                        <th class="px-4 py-2 text-left">Barcode</th>
                        <th class="px-4 py-2 text-left">Category</th>
                        <th class="px-4 py-2 text-left">Supplier</th>
                        <th class="px-4 py-2 text-left">Unit</th>
                        <th class="px-4 py-2 text-left">Stock</th>
                        <th class="px-4 py-2 text-left">Reorder Level</th>
                        <th class="px-4 py-2 text-left">Cost</th>
                        <th class="px-4 py-2 text-left">Price</th>
                        <th class="px-4 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody id="products-body">
                    <!-- AJAX content -->
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div id="pagination" class="flex justify-end mt-4"></div>
    </main>
    <script>
    let page = 1;
    let query = '';
    function loadProducts() {
        fetch(`ajax/admin/get_products.php?page=${page}&q=${encodeURIComponent(query)}`)
            .then(r => r.json())
            .then(data => {
                // Populate table
                let rows = '';
                data.products.forEach((p, idx) => {
                    rows += `<tr class="border-b hover:bg-blue-50">
                        <td class="px-4 py-2">${data.start + idx}</td>
                        <td class="px-4 py-2">${p.name}</td>
                        <td class="px-4 py-2">${p.sku}</td>
                        <td class="px-4 py-2">${p.barcode}</td>
                        <td class="px-4 py-2">${p.category}</td>
                        <td class="px-4 py-2">${p.supplier}</td>
                        <td class="px-4 py-2">${p.unit}</td>
                        <td class="px-4 py-2">${p.stock}</td>
                        <td class="px-4 py-2">${p.reorder_level}</td>
                        <td class="px-4 py-2">${p.cost}</td>
                        <td class="px-4 py-2">${p.price}</td>
                        <td class="px-4 py-2 space-x-2">
                            <a href="product_form.php?id=${p.id}" class="text-blue-600 hover:underline">Edit</a>
                            <button onclick="deleteProduct(${p.id})" class="text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>`;
                });
                document.getElementById('products-body').innerHTML = rows || '<tr><td colspan="12" class="text-center py-8 text-gray-500">No products found.</td></tr>';

                // Pagination
                let pag = '';
                for (let i = 1; i <= data.pages; i++) {
                    pag += `<button onclick="gotoPage(${i})" class="px-3 py-1 rounded mx-1 ${i === page ? 'bg-blue-600 text-white' : 'bg-gray-200'}">${i}</button>`;
                }
                document.getElementById('pagination').innerHTML = pag;
            });
    }
    function gotoPage(p) {
        page = p;
        loadProducts();
    }
    function deleteProduct(id) {
        if (!confirm('Delete this product?')) return;
        fetch('ajax/admin/delete_product.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) loadProducts();
            else alert('Delete failed!');
        });
    }
    document.getElementById('search').addEventListener('input', function() {
        query = this.value;
        page = 1;
        loadProducts();
    });
    loadProducts();
    </script>
</body>
</html>