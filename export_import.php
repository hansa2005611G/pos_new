<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: /login.php');
    exit;
}
$can_manage = in_array($_SESSION['role'], ['admin', 'manager']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>🧾 Export / Import</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-10">
  <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">🧾 Export / Import</h1>
  <div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-8">
    <!-- Export -->
    <div class="bg-white shadow rounded p-6">
      <h2 class="text-lg font-semibold mb-4">Export Data</h2>
      <form id="exportForm" class="space-y-4">
        <label class="block">
          <span class="font-medium">Export:</span>
          <select name="type" class="border rounded px-3 py-2 ml-2">
            <option value="products">Products</option>
            <option value="suppliers">Suppliers</option>
          </select>
        </label>
        <label class="block">
          <span class="font-medium">Format:</span>
          <select name="format" class="border rounded px-3 py-2 ml-2">
            <option value="csv">CSV</option>
            <option value="xlsx">Excel (.xlsx)</option>
            <option value="pdf">PDF</option>
          </select>
        </label>
        <button class="bg-blue-600 text-white px-5 py-2 rounded" type="submit">Download</button>
      </form>
      <div id="exportMsg" class="mt-4 text-green-700"></div>
    </div>
    <!-- Import -->
    <div class="bg-white shadow rounded p-6">
      <h2 class="text-lg font-semibold mb-4">Import Data</h2>
      <form id="importForm" enctype="multipart/form-data" class="space-y-4">
        <label class="block">
          <span class="font-medium">Import:</span>
          <select name="type" class="border rounded px-3 py-2 ml-2">
            <option value="products">Products</option>
            <option value="stock">Stock Update</option>
          </select>
        </label>
        <input type="file" name="file" accept=".csv, application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required class="block border py-2 px-3 rounded w-full"/>
        <button class="bg-green-600 text-white px-5 py-2 rounded" type="submit">Import</button>
      </form>
      <div id="importMsg" class="mt-4"></div>
      <div class="mt-2 text-sm text-gray-600">
        <div>Download sample: 
          <a href="sample/sample_products.csv" class="text-blue-700 underline">Products CSV</a>
          |
          <a href="sample/sample_stock.csv" class="text-blue-700 underline">Stock CSV</a>
        </div>
      </div>
    </div>
  </div>
</main>
<script>
// Export AJAX (download via link)
document.getElementById('exportForm').onsubmit = function(e){
  e.preventDefault();
  let form = e.target;
  let type = form.type.value;
  let format = form.format.value;
  document.getElementById('exportMsg').textContent = "Preparing download...";
  fetch(`ajax/export_data.php?type=${type}&format=${format}`, {method:'GET'})
    .then(r=>{
      if (!r.ok) throw new Error("Failed to export");
      return r.blob();
    })
    .then(blob=>{
      let ext = format==='xlsx' ? 'xlsx' : format;
      let fname = `${type}_${(new Date()).toISOString().slice(0,10)}.${ext}`;
      let url = window.URL.createObjectURL(blob);
      let a = document.createElement('a');
      a.href = url; a.download = fname;
      document.body.appendChild(a);
      a.click();
      a.remove();
      document.getElementById('exportMsg').textContent = "Download started.";
    })
    .catch(()=>document.getElementById('exportMsg').textContent="Failed to export.");
};

// Import AJAX
document.getElementById('importForm').onsubmit = function(e){
  e.preventDefault();
  let formData = new FormData(e.target);
  document.getElementById('importMsg').textContent = "Uploading...";
  fetch('ajax/import_data.php', {
    method: 'POST',
    body: formData
  })
  .then(r=>r.json())
  .then(res=>{
    if(res.success){
      document.getElementById('importMsg').innerHTML = `<span class="text-green-700">${res.message}</span>`;
    }else{
      document.getElementById('importMsg').innerHTML = `<span class="text-red-700">${res.error}</span>`;
    }
  })
  .catch(()=>document.getElementById('importMsg').textContent="Import failed.");
};
</script>
</body>
</html>