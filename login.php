<?php
session_start();
include 'db.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && ($password === $admin['password'] || password_verify($password, $admin['password']))) {
        $_SESSION['admin_logged'] = true;
        header("Location: admin.php");
        exit;
    }
    $error = "Tài khoản hoặc mật khẩu không chính xác!";
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đăng Nhập Quản Trị</title>
    <style>
        body {
            background: #f5f6fa;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        h2 {
            text-align: center;
            color: #2f3640;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #dcdde1;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            background: #487eb0;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #40739e;
        }

        .error {
            color: #e84118;
            text-align: center;
            font-size: 14px;
        }

        .back {
            text-align: center;
            margin-top: 15px;
        }

        .back a {
            color: #718093;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <h2>🔐 Đăng Nhập Chủ Tiệm</h2>
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <form method="POST">
            <label>Tài khoản:</label>
            <input type="text" name="username" required>
            <label>Mật khẩu:</label>
            <input type="password" name="password" required>
            <button type="submit">Đăng Nhập</button>
        </form>
        <div class="back"><a href="index.php">← Quay lại trang khách hàng</a></div>
    </div>
</body>

</html>