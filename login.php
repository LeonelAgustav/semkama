<?php
// login.php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // Check if the username exists
    $sql = "SELECT * FROM users WHERE username = ? AND usertype = 'CUSTOMER' AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);  // Binding only the username
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Debugging: Show the hashed password from the database
        // Remove this after you confirm it's working
        echo "Stored password hash: " . $user['password'];

        // Check if the password is correct
        if (($password === $user['password'])) {
            // Password is correct, store session data and redirect to dashboard
            $_SESSION['username'] = $username;
            header("Location: dashboard.php");
            exit;
        } else {
            $_SESSION['error_message'] = 'Password salah. Coba lagi.';
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION['error_message'] = 'Username tidak ditemukan.';
        header("Location: login.php");
        exit;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/style.css">
    <title>Semkama - Login</title>
</head>
<body class="bg-gray-100">
    <div id="login-page" class="min-h-screen flex items-center justify-center">
        <div class="w-full max-w-md bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold text-center text-gray-700">Semkama Login</h1>
            <form action="login.php" method="POST" class="mt-4">
                <div class="mb-4">
                    <label for="username" class="block text-gray-600">Username</label>
                    <input type="text" id="username" name="username" class="w-full mt-2 p-2 border rounded focus:outline-none focus:ring focus:ring-indigo-300" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-600">Password</label>
                    <input type="password" id="password" name="password" class="w-full mt-2 p-2 border rounded focus:outline-none focus:ring focus:ring-indigo-300" required>
                </div>
                <button type="submit" class="w-full bg-indigo-500 text-white py-2 rounded hover:bg-indigo-600">Login</button>
            </form>
            <p class="text-sm text-center text-gray-500 mt-4">Belum punya akun? <a href="register.php" class="text-indigo-500">Daftar sekarang</a></p>
        </div>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
        <script type="text/javascript">
            alert('<?php echo $_SESSION['error_message']; ?>');
            <?php unset($_SESSION['error_message']); ?>
        </script>
    <?php endif; ?>
</body>
</html>
