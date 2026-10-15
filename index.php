<?php
include 'db.php';
$success_msg = "";

// Xử lý đặt hàng bằng PDO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $customer_name = $_POST['customer_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $cart_data = $_POST['cart_data'];
    $total_price = $_POST['total_price'];

    if (!empty($cart_data) && !empty($phone)) {
        try {
            $stmt = $conn->prepare("INSERT INTO orders (customer_name, phone, address, items, total_price, status) VALUES (?, ?, ?, ?, ?, 'Chờ xác nhận')");
            $stmt->execute([$customer_name, $phone, $address, $cart_data, $total_price]);
            $success_msg = "Đặt hàng thành công! Mã đơn hàng của bạn đã được ghi nhận.";
        } catch (PDOException $e) {
            $success_msg = "Lỗi đặt hàng: " . $e->getMessage();
        }
    }
}

// Lấy danh sách menu bằng PDO
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM menu WHERE name LIKE ?");
    $stmt->execute(["%$search%"]);
} else {
    $stmt = $conn->prepare("SELECT * FROM menu ORDER BY id DESC");
    $stmt->execute();
}
$result = $stmt->fetchAll();

// Tra cứu đơn hàng bằng PDO
$search_phone = isset($_GET['search_phone']) ? $_GET['search_phone'] : '';
$orders_result = null;
if ($search_phone) {
    $stmt_ord = $conn->prepare("SELECT * FROM orders WHERE phone = ? ORDER BY id DESC");
    $stmt_ord->execute([$search_phone]);
    $orders_result = $stmt_ord->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Tiệm Trà Sữa Online</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #fff5f2;
            color: #333;
        }

        header {
            background: #3ea7ee;
            color: white;
            padding: 20px;
            text-align: center;
        }

        nav {
            background: #3057d6;
            padding: 10px;
            text-align: center;
        }

        nav a {
            color: white;
            margin: 0 15px;
            text-decoration: none;
            font-weight: bold;
        }

        .container {
            max-width: 1100px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .menu-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            text-align: center;
            padding-bottom: 15px;
        }

        .menu-item img {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }

        .menu-item h3 {
            margin: 10px 0 5px;
            color: #d63031;
        }

        .menu-item p {
            color: #666;
            font-size: 14px;
            padding: 0 10px;
        }

        .price {
            font-weight: bold;
            color: #e17055;
            margin-bottom: 10px;
        }

        button {
            background: #00b894;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #00cec9;
        }

        .cart-section,
        .lookup-section {
            margin-top: 40px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        input[type="text"],
        input[type="tel"],
        textarea {
            width: 100%;
            padding: 8px;
            margin: 5px 0 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #fab1a0;
        }

        .alert {
            background: #55efc4;
            color: #00563b;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>

<body>

    <header>
        <h1>🧋 Tiệm Trà Sữa Nhỏ Xinh</h1>
        <p>Thơm ngon đậm vị - Giao hàng tận nơi</p>
    </header>
    <nav>
        <a href="index.php">Trang Chủ & Menu</a>
        <a href="#cart">Giỏ Hàng (<span id="cart-count">0</span>)</a>
        <a href="#lookup">Tra Cứu Đơn Hàng</a>
        <a href="login.php" style="float: right;">Quản Trị Chủ Tiệm</a>
    </nav>

    <div class="container">
        <?php if ($success_msg): ?>
            <div class="alert"><?= $success_msg ?></div>
        <?php endif; ?>

        <form method="GET" style="display: flex; gap: 10px;">
            <input type="text" name="search" placeholder="Nhập tên món cần tìm..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" style="height: 38px; margin-top: 5px;">Tìm Kiếm</button>
        </form>

        <h2>📜 Thực Đơn</h2>
        <div class="menu-grid">
            <?php foreach ($result as $row): ?>
                <div class="menu-item">
                    <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                    <h3><?= htmlspecialchars($row['name']) ?></h3>
                    <p><?= htmlspecialchars($row['description']) ?></p>
                    <div class="price"><?= number_format($row['price']) ?> đ</div>
                    <button onclick="addToCart(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>', <?= $row['price'] ?>)">Thêm vào giỏ</button>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="cart" class="cart-section">
            <h2>🛒 Giỏ Hàng Của Bạn</h2>
            <div id="cart-items">Giỏ hàng trống.</div>
            <h3>Tổng tiền: <span id="cart-total">0</span> đ</h3>

            <form method="POST" onsubmit="return prepareOrder()">
                <input type="hidden" name="cart_data" id="cart_data">
                <input type="hidden" name="total_price" id="total_price_input">

                <h3>Thông tin giao hàng</h3>
                <label>Họ tên:</label>
                <input type="text" name="customer_name" required>
                <label>Số điện thoại:</label>
                <input type="tel" name="phone" id="order_phone" required>
                <label>Địa chỉ nhận hàng:</label>
                <textarea name="address" required></textarea>

                <button type="submit" name="place_order" style="background: #02902a; font-size: 16px; padding: 10px 20px;">Xác Nhận Đặt Món</button>
            </form>
        </div>

        <div id="lookup" class="lookup-section">
            <h2>🔍 Tra Cứu Đơn Hàng Bằng Số Điện Thoại</h2>
            <form method="GET">
                <input type="tel" name="search_phone" placeholder="Nhập số điện thoại của bạn..." value="<?= htmlspecialchars($search_phone) ?>" required>
                <button type="submit">Tra Cứu</button>
            </form>

            <?php if ($search_phone !== ''): ?>
                <h3>Kết quả tra cứu cho số: <?= htmlspecialchars($search_phone) ?></h3>
                <?php if (count($orders_result) > 0): ?>
                    <table>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Danh sách món</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thời gian</th>
                        </tr>
                        <?php foreach ($orders_result as $ord): ?>
                            <tr>
                                <td>#<?= $ord['id'] ?></td>
                                <td><?= nl2br(htmlspecialchars($ord['items'])) ?></td>
                                <td><?= number_format($ord['total_price']) ?> đ</td>
                                <td><b><?= htmlspecialchars($ord['status']) ?></b></td>
                                <td><?= $ord['created_at'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p>Không tìm thấy đơn hàng nào với số điện thoại này.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let cart = [];

        function addToCart(id, name, price) {
            let item = cart.find(i => i.id === id);
            if (item) {
                item.qty++;
            } else {
                cart.push({
                    id,
                    name,
                    price,
                    qty: 1
                });
            }
            updateCartUI();
        }

        function updateCartUI() {
            let container = document.getElementById('cart-items');
            let totalSpan = document.getElementById('cart-total');
            let countSpan = document.getElementById('cart-count');
            if (cart.length === 0) {
                container.innerHTML = "Giỏ hàng trống.";
                totalSpan.innerText = "0";
                countSpan.innerText = "0";
                return;
            }
            let html = "<table><tr><th>Tên món</th><th>Giá</th><th>Số lượng</th><th>Thành tiền</th><th>Thao tác</th></tr>";
            let total = 0,
                count = 0;
            cart.forEach((item, index) => {
                let subtotal = item.price * item.qty;
                total += subtotal;
                count += item.qty;
                html += `<tr><td>${item.name}</td><td>${item.price.toLocaleString()} đ</td><td><button type="button" onclick="changeQty(${index}, -1)">-</button> ${item.qty} <button type="button" onclick="changeQty(${index}, 1)">+</button></td><td>${subtotal.toLocaleString()} đ</td><td><button type="button" style="background:#d63031;" onclick="removeItem(${index})">Xóa</button></td></tr>`;
            });
            html += "</table>";
            container.innerHTML = html;
            totalSpan.innerText = total.toLocaleString();
            countSpan.innerText = count;
        }

        function changeQty(index, delta) {
            cart[index].qty += delta;
            if (cart[index].qty <= 0) cart.splice(index, 1);
            updateCartUI();
        }

        function removeItem(index) {
            cart.splice(index, 1);
            updateCartUI();
        }

        function prepareOrder() {
            if (cart.length === 0) {
                alert("Giỏ hàng của bạn đang trống!");
                return false;
            }
            document.getElementById('cart_data').value = cart.map(i => `${i.name} (SL: ${i.qty})`).join("\n");
            document.getElementById('total_price_input').value = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
            return true;
        }
    </script>
</body>

</html>