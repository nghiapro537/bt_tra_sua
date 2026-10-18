<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}
include 'db.php';

// Đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$action_msg = "";

// Xử lý Thêm / Sửa Món
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

// Xóa món
if (isset($_GET['delete_menu'])) {
    $id = $_GET['delete_menu'];
    $stmt = $conn->prepare("DELETE FROM menu WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin.php?tab=menu");
    exit;
}

// Cập nhật trạng thái đơn hàng
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    $action_msg = "Cập nhật trạng thái đơn hàng thành công!";
}

// Tab hiện tại (Mặc định là orders)
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'orders';

// Lấy dữ liệu
$menu_result = $conn->query("SELECT * FROM menu ORDER BY id DESC")->fetchAll();
$orders_result = $conn->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();

// Thống kê doanh thu
$stats_week = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK) AND status != 'Đã hủy'")->fetch()['total'] ?? 0;
$stats_month = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) AND status != 'Đã hủy'")->fetch()['total'] ?? 0;
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản Trị Tiệm Trà Sữa</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f4f6f9;
            color: #333;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar bên trái */
        .sidebar {
            width: 250px;
            background: #2f3640;
            color: white;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            padding: 20px;
            font-size: 18px;
            font-weight: bold;
            background: #1e272e;
            text-align: center;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex: 1;
        }

        .sidebar-menu li a {
            display: block;
            padding: 15px 20px;
            color: #dcdde1;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: 0.2s;
        }

        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background: #353b48;
            color: #fff;
            border-left-color: #00b894;
        }

        .sidebar-footer {
            padding: 15px 20px;
            background: #1e272e;
        }

        .sidebar-footer a {
            color: #ff7675;
            text-decoration: none;
            font-weight: bold;
        }

        /* Nội dung chính bên phải */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .top-header {
            background: white;
            padding: 15px 30px;
            border-bottom: 1px solid #dcdde1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-visit {
            background: #e17055;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        .btn-visit:hover {
            background: #d63031;
        }

        .content-body {
            padding: 25px;
        }

        /* Thống kê doanh thu */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 15px;
        }

        .stat-card {
            color: white;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
            font-size: 16px;
            font-weight: normal;
        }

        .stat-card p {
            font-size: 26px;
            font-weight: bold;
            margin: 0;
        }

        /* Bảng dữ liệu & Form */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            border: 1px solid #f1f2f6;
            padding: 12px 15px;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #f8f9fa;
            color: #2f3640;
        }

        button {
            background: #00b894;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #00cec9;
        }

        .btn-danger {
            background: #d63031;
        }

        .btn-danger:hover {
            background: #ff7675;
        }

        input,
        select,
        textarea {
            padding: 8px;
            width: 100%;
            margin: 5px 0 12px;
            border: 1px solid #dcdde1;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-box {
            background: white;
            border: 1px solid #dcdde1;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .alert {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <!-- Sidebar bên trái -->
    <div class="sidebar">
        <div class="sidebar-brand">🧋 Quản Trị Trà Sữa</div>
        <ul class="sidebar-menu">
            <li><a href="admin.php?tab=orders" class="<?= $tab == 'orders' ? 'active' : '' ?>">📦 Quản Lý Đơn Hàng</a></li>
            <li><a href="admin.php?tab=menu" class="<?= $tab == 'menu' ? 'active' : '' ?>">☕ Quản Lý Thực Đơn</a></li>
            <li><a href="admin.php?tab=stats" class="<?= $tab == 'stats' ? 'active' : '' ?>">📈 Thống Kê Doanh Thu</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="admin.php?logout=true">Đăng Xuất</a>
        </div>
    </div>

    <!-- Nội dung chính bên phải -->
    <div class="main-content">
        <!-- Header có nút chuyển đến trang khách ở góc trên bên phải -->
        <div class="top-header">
            <h3 style="margin: 0;">Bảng Điều Khiển (Dashboard)</h3>
            <div style="display: flex; align-items: center; gap: 20px;">
                <span>Xin chào, <b>Chủ Tiệm</b></span>
                <a href="index.php" target="_blank" class="btn-visit">🌐 Xem Trang Khách Hàng ↗</a>
            </div>
        </div>

        <div class="content-body">
            <?php if ($action_msg): ?>
                <div class="alert"><?= $action_msg ?></div>
            <?php endif; ?>

            <!-- TAB 1: QUẢN LÝ ĐƠN HÀNG -->
            <?php if ($tab == 'orders'): ?>
                <h2>Danh Sách Đơn Hàng</h2>
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

                <!-- TAB 2: QUẢN LÝ THỰC ĐƠN -->
            <?php elseif ($tab == 'menu'): ?>
                <h2>Quản Lý Thực Đơn Món</h2>

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
                        <label>Link hình ảnh (URL hoặc đường dẫn thư mục):</label>
                        <input type="text" name="image" value="<?= htmlspecialchars($edit_item['image']) ?>" placeholder="uploads/hinh.jpg hoặc https://..." required>
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
                            <td><img src="<?= htmlspecialchars($m['image']) ?>" width="50" height="50" style="object-fit:cover; border-radius:4px;"></td>
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

                <!-- TAB 3: THỐNG KÊ DOANH THU -->
            <?php elseif ($tab == 'stats'): ?>
                <h2>Thống Kê Doanh Thu & Đơn Hàng</h2>
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
    </div>

</body>

</html>