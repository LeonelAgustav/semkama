<?php
session_start();
include 'db.php';

// Pastikan pengguna sudah login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Ambil username dari session
$username = $_SESSION['username']; 

// Cek jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    // Ambil name dari tabel users berdasarkan username
    $userSql = "SELECT name FROM users WHERE username = ?";
    $stmt = $conn->prepare($userSql);
    if ($stmt === false) {
        die('Error preparing query: ' . $conn->error);
    }
    $stmt->bind_param("s", $username); // Bind username ke query
    $stmt->execute();
    $userResult = $stmt->get_result();

    // Pastikan name ditemukan
    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();
        $name = $user['name']; // Ambil name

        // Ambil customer_id dari tabel customers berdasarkan name
        $customerSql = "SELECT cid FROM customers WHERE fullname = ?";
        $stmt = $conn->prepare($customerSql);
        if ($stmt === false) {
            die('Error preparing query: ' . $conn->error);
        }
        $stmt->bind_param("s", $name); // Bind name ke query
        $stmt->execute();
        $customerResult = $stmt->get_result();

        // Pastikan customer ditemukan
        if ($customerResult->num_rows > 0) {
            $customer = $customerResult->fetch_assoc();
            $customer_id = $customer['cid']; // Ambil customer_id

            // Ambil data produk dari tabel products
            $productSql = "SELECT * FROM products WHERE pid = ?";
            $stmt = $conn->prepare($productSql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $productResult = $stmt->get_result();
            
            // Pastikan produk ditemukan
            if ($productResult->num_rows > 0) {
                $product = $productResult->fetch_assoc();
                $product_price = $product['price'];

                // Hitung total amount
                $total_amount = $product_price * $quantity;

                // Insert data ke tabel orders
                $order_date = date('Y-m-d H:i:s');
                $orderSql = "INSERT INTO orders (customer_id, product_id, quantity, order_date, total_amount) 
                             VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($orderSql);
                $stmt->bind_param("iiiss", $customer_id, $product_id, $quantity, $order_date, $total_amount);
                $stmt->execute();

                // Cek apakah insert berhasil
                if ($stmt->affected_rows > 0) {
                    // Berikan pesan sukses
                    $_SESSION['success_message'] = "Order placed successfully!";
                } else {
                    // Berikan pesan gagal
                    $_SESSION['error_message'] = "Failed to place the order.";
                }
            } else {
                $_SESSION['error_message'] = "Product not found.";
            }
        } else {
            $_SESSION['error_message'] = "Customer not found.";
        }
    } else {
        $_SESSION['error_message'] = "Name not found.";
    }
    
    // Redirect kembali ke halaman order untuk menampilkan pesan
    header("Location: order.php");
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
    <title>Semkama - Order</title>
</head>
<body class="bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 min-h-screen flex">

    <!-- Sidebar -->
    <div class="w-64 bg-white shadow-lg flex flex-col min-h-screen">
        <div class="bg-gray-300 rounded-t-lg py-6 px-4 text-center">
            <h2 class="text-4xl font-bold text-gray-600 mb-4">Menu</h2>
        </div>
        <div class="bg-gray-200 rounded-b-lg flex flex-col py-6 px-4 h-full">
            <a href="dashboard.php" class="block bg-indigo-500 text-white text-center px-6 py-2 rounded-lg shadow-md hover:bg-indigo-600 transition mb-4">Dashboard</a>
            <a href="order.php" class="block bg-indigo-500 text-white text-center px-6 py-2 rounded-lg shadow-md hover:bg-indigo-600 transition mb-4">Order Product</a>
            <a href="logout.php" class="block bg-indigo-500 text-white text-center px-6 py-2 rounded-lg shadow-md hover:bg-indigo-600 transition">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 px-6 py-10">
        <!-- Welcome Section -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-10 text-center">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Order Product</h1>
        </div>

        <!-- Order Form Section -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Place Your Order</h2>

            <!-- Menampilkan pesan sukses atau error -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-500 text-white p-4 mb-4 rounded">
                    <?php echo $_SESSION['success_message']; ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php elseif (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-500 text-white p-4 mb-4 rounded">
                    <?php echo $_SESSION['error_message']; ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <form action="order.php" method="POST">
                <div class="mb-4">
                    <label for="product_id" class="block text-gray-600">Product</label>
                    <select name="product_id" id="product_id" class="w-full mt-2 p-2 border rounded focus:outline-none focus:ring focus:ring-indigo-300" required>
                        <option value="">Select a product</option>
                        <?php
                        $sql = "SELECT * FROM products";
                        $result = $conn->query($sql);
                        while ($product = $result->fetch_assoc()) {
                            echo "<option value='" . $product['pid'] . "'>" . $product['productname'] . " - Rp " . number_format($product['price'], 0, ',', '.') . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="quantity" class="block text-gray-600">Quantity</label>
                    <input type="number" id="quantity" name="quantity" min="1" class="w-full mt-2 p-2 border rounded focus:outline-none focus:ring focus:ring-indigo-300" required>
                </div>

                <button type="submit" class="w-full bg-indigo-500 text-white py-2 rounded hover:bg-indigo-600">Place Order</button>
            </form>
        </div>
    </div>
</body>
</html>
