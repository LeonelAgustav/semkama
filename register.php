<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $location = $conn->real_escape_string($_POST['location']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // Cek apakah username sudah ada
    $sql_check = "SELECT * FROM users WHERE username = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $username);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $_SESSION['error_message'] = 'Username sudah digunakan, silakan pilih username lain.';
        header("Location: register.php");
        exit;
    }

    // Generate customercode
    $prefix = "std";
    $sql_last_customer = "SELECT customercode FROM customers ORDER BY cid DESC LIMIT 1";
    $result_last_customer = $conn->query($sql_last_customer);
    if ($result_last_customer->num_rows > 0) {
        $row = $result_last_customer->fetch_assoc();
        $last_code = str_replace($prefix, '', $row['customercode']);
        $next_number = (int)$last_code + 1;
        $customercode = $prefix . str_pad($next_number, 1, '0', STR_PAD_LEFT);
    } else {
        $customercode = $prefix . "1"; 
    }

    // Mulai transaksi
    $conn->begin_transaction();

    try {
        // Insert ke tabel `users`
        $sql_users = "INSERT INTO users (name, location, phone, username, password, usertype) VALUES (?, ?, ?, ?, ?, 'CUSTOMER')";
        $stmt_users = $conn->prepare($sql_users);
        $stmt_users->bind_param("sssss", $fullname, $location, $phone, $username, $password);
        $stmt_users->execute();

        // Insert ke tabel `customers`
        $sql_customers = "INSERT INTO customers (customercode, fullname, location, phone) VALUES (?, ?, ?, ?)";
        $stmt_customers = $conn->prepare($sql_customers);
        $stmt_customers->bind_param("ssss", $customercode, $fullname, $location, $phone);
        $stmt_customers->execute();

        // Commit transaksi
        $conn->commit();

        $_SESSION['success_message'] = 'Registrasi berhasil! Silakan login.';
        header("Location: register.php");  // Arahkan ke halaman register setelah sukses
        exit;
    } catch (mysqli_sql_exception $e) {
        // Rollback jika terjadi kesalahan
        $conn->rollback();
        $_SESSION['error_message'] = 'Terjadi kesalahan, coba lagi nanti.';
        header("Location: register.php");  // Arahkan ke halaman register setelah gagal
        exit;
    } finally {
        $stmt_users->close();
        $stmt_customers->close();
        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/style.css">
    <title>Semkama - Register</title>
</head>
<body class="bg-gray-100">
    <div id="register-page" class="min-h-screen flex items-center justify-center">
        <div class="w-full max-w-md bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold text-center text-gray-700">Semkama Register</h1>
            
            <!-- Modal Popup untuk Pesan -->
            <?php if (isset($_SESSION['success_message']) || isset($_SESSION['error_message'])): ?>
                <div id="message-modal" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50">
                    <div class="bg-white rounded-lg p-8 w-96">
                        <div class="mb-4">
                            <?php if (isset($_SESSION['success_message'])): ?>
                                <div class="text-green-500 font-semibold text-lg">
                                    <?php echo $_SESSION['success_message']; ?>
                                </div>
                            <?php elseif (isset($_SESSION['error_message'])): ?>
                                <div class="text-red-500 font-semibold text-lg">
                                    <?php echo $_SESSION['error_message']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button onclick="closeModal()" class="w-full bg-indigo-500 text-white py-2 rounded hover:bg-indigo-600">Tutup</button>
                    </div>
                </div>
                <?php unset($_SESSION['success_message']); unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <form action="register.php" method="POST" class="mt-4">
                <div class="mb-4">
                    <label for="fullname" class="block text-gray-600">Nama Panjang</label>
                    <input type="text" id="fullname" name="fullname" class="w-full mt-2 p-2 border rounded focus:outline-none focus:ring focus:ring-indigo-300" required>
                </div>
                <div class="mb-4">
                    <label for="location" class="block text-gray-600">Location</label>
                    <input type="text" id="location" name="location" class="w-full mt-2 p-2 border rounded focus:outline-none focus:ring focus:ring-indigo-300" required>
                </div>
                <div class="mb-4">
                    <label for="phone" class="block text-gray-600">Nomor Telepon</label>
                    <input type="text" id="phone" name="phone" class="w-full mt-2 p-2 border rounded focus:outline-none focus:ring focus:ring-indigo-300" required>
                </div>
                <div class="mb-4">
                    <label for="username" class="block text-gray-600">Username</label>
                    <input type="text" id="username" name="username" class="w-full mt-2 p-2 border rounded focus:outline-none focus:ring focus:ring-indigo-300" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-600">Password</label>
                    <input type="password" id="password" name="password" class="w-full mt-2 p-2 border rounded focus:outline-none focus:ring focus:ring-indigo-300" required>
                </div>
                <button type="submit" class="w-full bg-indigo-500 text-white py-2 rounded hover:bg-indigo-600">Register</button>
            </form>
            <p class="text-sm text-center text-gray-500 mt-4">Sudah punya akun? <a href="login.php" class="text-indigo-500">Login di sini</a></p>
        </div>
    </div>

    <script>
        // Fungsi untuk menutup modal
        function closeModal() {
            document.getElementById('message-modal').style.display = 'none';
            // Redirect setelah modal ditutup
            window.location.href = 'login.php';
        }
    </script>
</body>
</html>
