<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}
include 'db.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$action_msg = "";
if (isset($_POST['save_menu'])) {
    $id = $_POST['menu_id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $image = $_POST['image'];
    $description = $_POST['description'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE menu SET name=?, price=?, image=?, description=? WHERE id=?");
        $stmt->execute([$name, $price, $image, $description, $id]);
        $action_msg = "Cập nhật món thành công!";
    } else {
        $stmt = $conn->prepare("INSERT INTO menu (name, price, image, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $price, $image, $description]);
        $action_msg = "Thêm món mới thành công!";
    }
}

if (isset($_GET['delete_menu'])) {
    $id = $_GET['delete_menu'];
    $stmt = $conn->prepare("DELETE FROM menu WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin.php?tab=menu");
    exit;
}

if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    $action_msg = "Cập nhật trạng thái đơn hàng thành công!";
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'orders';
$menu_result = $conn->query("SELECT * FROM menu ORDER BY id DESC")->fetchAll();
$orders_result = $conn->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();

$stats_week = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK) AND status != 'Đã hủy'")->fetch()['total'] ?? 0;
$stats_month = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) AND status != 'Đã hủy'")->fetch()['total'] ?? 0;
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Trang Quản Trị Chủ Tiệm</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f1f2f6;
            color: #333;
        }

        header {
            background: #2f3640;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav {
            background: #353b48;
            padding: 10px 30px;
            display: flex;
            gap: 15px;
        }

        nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
        }

        nav a.active,
        nav a:hover {
            background: #487eb0;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card:nth-child(1) {
            background: #00b894;
        }

        .stat-card:nth-child(2) {
            background: #0984e3;
        }

        .stat-card:nth-child(3) {
            background: #e17055;
        }

        .stat-card h3 {
            margin: 0 0 10px;
            font-size: 18px;
        }

        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #dcdde1;
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #f5f6fa;
        }

        button {
            background: #4cd137;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #44bd32;
        }

        .btn-danger {
            background: #e84118;
        }

        .btn-danger:hover {
            background: #c23616;
        }

        input,
        select,
        textarea {
            padding: 6px;
            width: 100%;
            margin: 5px 0 10px;
            border: 1px solid #dcdde1;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-box {
            background: #fdfdfd;
            border: 1px solid #dcdde1;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <header>
        <h2>🛠️ Khu Vực Quản Trị Tiệm Trà Sữa</h2>
        <a href="admin.php?logout=true" style="color: #ff7675; text-decoration: none; font-weight: bold;">Đăng Xuất</a>
    </header>

    <nav>
        <a href="admin.php?tab=orders" class="<?= $tab == 'orders' ? 'active' : '' ?>">Quản Lý Đơn Hàng</a>
        <a href="admin.php?tab=menu" class="<?= $tab == 'menu' ? 'active' : '' ?>">Quản Lý Menu Món</a>
        <a href="admin.php?tab=stats" class="<?= $tab == 'stats' ? 'active' : '' ?>">Thống Kê Doanh Thu</a>
        <a href="index.php" target="_blank" style="margin-left: auto; background: #e17055;">Xem Trang Khách Hàng ↗</a>
    </nav>

    <div class="container">
        <?php if ($action_msg): ?>
            <div class="alert"><?= $action_msg ?></div>
        <?php endif; ?>

        <?php if ($tab == 'orders'): ?>
            <h2>📦 Danh Sách Đơn Hàng Khách Đặt</h2>
            <table>
                <tr>
                    <th>Mã</th>
                    <th>Khách hàng</th>
                    <th>SĐT & Địa chỉ</th>
                    <th>Món đặt</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Thời gian</th>
                </tr>
                <?php foreach ($orders_result as $ord): ?>
                    <tr>
                        <td>#<?= $ord['id'] ?></td>
                        <td><b><?= htmlspecialchars($ord['customer_name']) ?></b></td>
                        <td><?= htmlspecialchars($ord['phone']) ?><br><small><?= htmlspecialchars($ord['address']) ?></small></td>
                        <td><?= nl2br(htmlspecialchars($ord['items'])) ?></td>
                        <td><b><?= number_format($ord['total_price']) ?> đ</b></td>
                        <td>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                <select name="status" onchange="this.form.submit()" style="margin:0; font-weight: bold;">
                                    <option value="Chờ xác nhận" <?= $ord['status'] == 'Chờ xác nhận' ? 'selected' : '' ?>>Chờ xác nhận</option>
                                    <option value="Đang chuẩn bị" <?= $ord['status'] == 'Đang chuẩn bị' ? 'selected' : '' ?>>Đang chuẩn bị</option>
                                    <option value="Đang giao hàng" <?= $ord['status'] == 'Đang giao hàng' ? 'selected' : '' ?>>Đang giao hàng</option>
                                    <option value="Hoàn thành" <?= $ord['status'] == 'Hoàn thành' ? 'selected' : '' ?>>Hoàn thành</option>
                                    <option value="Đã hủy" <?= $ord['status'] == 'Đã hủy' ? 'selected' : '' ?>>Đã hủy</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </td>
                        <td><?= $ord['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

        <?php elseif ($tab == 'menu'): ?>
            <h2>☕ Quản Lý Thực Đơn (Thêm / Sửa / Xóa)</h2>

            <?php
            $edit_item = ['id' => '', 'name' => '', 'price' => '', 'image' => '', 'description' => ''];
            if (isset($_GET['edit_menu'])) {
                $edit_id = $_GET['edit_menu'];
                $stmt = $conn->prepare("SELECT * FROM menu WHERE id = ?");
                $stmt->execute([$edit_id]);
                $res = $stmt->fetch();
                if ($res) $edit_item = $res;
            }
            ?>

            <div class="form-box">
                <h3><?= $edit_item['id'] ? '✏️ Sửa Món #' . $edit_item['id'] : '➕ Thêm Món Mới' ?></h3>
                <form method="POST">
                    <input type="hidden" name="menu_id" value="<?= $edit_item['id'] ?>">
                    <label>Tên món:</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($edit_item['name']) ?>" required>
                    <label>Giá tiền (VNĐ):</label>
                    <input type="number" name="price" value="<?= $edit_item['price'] ?>" required>
                    <label>Link hình ảnh (URL):</label>
                    <input type="text" name="image" value="<?= htmlspecialchars($edit_item['image']) ?>" required>
                    <label>Mô tả ngắn:</label>
                    <textarea name="description"><?= htmlspecialchars($edit_item['description']) ?></textarea>
                    <button type="submit" name="save_menu">Lưu Món</button>
                    <?php if ($edit_item['id']): ?>
                        <a href="admin.php?tab=menu" style="margin-left: 10px; text-decoration: none; color: #555;">Hủy sửa</a>
                    <?php endif; ?>
                </form>
            </div>

            <table>
                <tr>
                    <th>Hình ảnh</th>
                    <th>Tên món</th>
                    <th>Giá</th>
                    <th>Mô tả</th>
                    <th>Thao tác</th>
                </tr>
                <?php foreach ($menu_result as $m): ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($m['image']) ?>" width="60" height="60" style="object-fit:cover; border-radius:4px;"></td>
                        <td><b><?= htmlspecialchars($m['name']) ?></b></td>
                        <td><?= number_format($m['price']) ?> đ</td>
                        <td><?= htmlspecialchars($m['description']) ?></td>
                        <td>
                            <a href="admin.php?tab=menu&edit_menu=<?= $m['id'] ?>"><button type="button">Sửa</button></a>
                            <a href="admin.php?delete_menu=<?= $m['id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa món này?')"><button type="button" class="btn-danger">Xóa</button></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

        <?php elseif ($tab == 'stats'): ?>
            <h2>📈 Thống Kê Doanh Thu & Đơn Hàng</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Doanh Thu Tuần Này</h3>
                    <p><?= number_format($stats_week) ?> đ</p>
                </div>
                <div class="stat-card">
                    <h3>Doanh Thu Tháng Này</h3>
                    <p><?= number_format($stats_month) ?> đ</p>
                </div>
                <div class="stat-card">
                    <h3>Tổng Đơn Hàng</h3>
                    <p><?= number_format($total_orders) ?> đơn</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>