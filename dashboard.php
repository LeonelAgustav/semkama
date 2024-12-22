<?php
// dashboard.php
include 'db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Default query to fetch all products
$sql = "SELECT * FROM products";
$result = $conn->query($sql);

// Search logic for AJAX request
if (isset($_GET['ajax_search'])) {
    $search = $_GET['ajax_search'];
    $sql = "SELECT * FROM products WHERE productname LIKE '%$search%' OR productcode LIKE '%$search%' OR category LIKE '%$search%'";
    
    // Execute query
    $result = $conn->query($sql);

    // Fetch products into an array
    $products = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    // Return JSON response
    echo json_encode($products);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/style.css">
    <title>Semkama - Dashboard</title>
    <script>
        // Function to fetch and render products
        function fetchProducts(query = "") {
            const xhr = new XMLHttpRequest();
            xhr.open("GET", `dashboard.php?ajax_search=${query}`, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const products = JSON.parse(xhr.responseText);
                    const tbody = document.querySelector("#product-list");
                    tbody.innerHTML = "";

                    if (products.length > 0) {
                        products.forEach(product => {
                            tbody.innerHTML += `
                                <tr class="hover:bg-gray-100 transition">
                                    <td class="border px-6 py-3 text-gray-700 font-medium">${product.pid}</td>
                                    <td class="border px-6 py-3 text-gray-700 font-medium">${product.productcode}</td>
                                    <td class="border px-6 py-3 text-gray-700">${product.productname}</td>
                                    <td class="border px-6 py-3 text-gray-700">${product.category}</td>
                                    <td class="border px-6 py-3 text-gray-700">${product.Satuan}</td>
                                    <td class="border px-6 py-3 text-gray-700">Rp ${parseInt(product.price).toLocaleString('id-ID')}</td>
                                </tr>
                            `;
                        });
                    } else {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="6" class="border px-6 py-3 text-center text-gray-500">No products found</td>
                            </tr>
                        `;
                    }
                }
            };
            xhr.send();
        }

        // Load products on page load
        window.onload = function () {
            fetchProducts(); // Fetch all products on initial load
        };
    </script>
</head>
<body class="bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 min-h-screen flex">
    <!-- Sidebar -->
    <div class="w-64 bg-white shadow-lg flex flex-col min-h-screen">
        <div class="bg-gray-300 rounded-t-lg py-6 px-4 text-center">
            <h2 class="text-4xl font-bold text-gray-600 text-center mb-4">Menu</h2>
        </div>

        <!-- Links Section -->
        <div class="bg-gray-200 rounded-b-lg flex flex-col py-6 px-4 h-full">
            <a href="dashboard.php" class="block bg-indigo-500 text-white text-center px-6 py-2 rounded-lg shadow-md hover:bg-indigo-600 transition mb-4">Dashboard</a>
            <a href="order.php" class="block bg-indigo-500 text-white text-center px-6 py-2 rounded-lg shadow-md hover:bg-indigo-600 transition mb-4">Order Product</a>
            <a href="logout.php" class="block bg-indigo-500 text-white text-center px-6 py-2 rounded-lg shadow-md hover:bg-indigo-600 transition">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div id="dashboard-page" class="flex-1 px-6 py-10">
        <!-- Welcome Section -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-10 text-center">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Welcome to Semkama</h1>
            <p class="text-lg text-gray-600">Hello, <span class="font-semibold text-indigo-500"><?php echo $_SESSION['username']; ?></span>!</p>
        </div>

        <!-- Search Section -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <input 
                type="text" 
                id="search-input"
                placeholder="Search for products..." 
                oninput="fetchProducts(this.value)" 
                class="w-full border border-gray-300 rounded-lg px-4 py-2"
            >
        </div>

        <!-- Product List Section -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Product List</h2>
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-200 text-gray-700">
                        <th class="border px-6 py-3 text-left">ID</th>
                        <th class="border px-6 py-3 text-left">Product Code</th>
                        <th class="border px-6 py-3 text-left">Product Name</th>
                        <th class="border px-6 py-3 text-left">Category</th>
                        <th class="border px-6 py-3 text-left">Unit</th>
                        <th class="border px-6 py-3 text-left">Price</th>
                    </tr>
                </thead>
                <tbody id="product-list" class="divide-y">
                    <!-- Product rows will be dynamically updated here -->
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
