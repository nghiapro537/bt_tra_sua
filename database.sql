CREATE DATABASE IF NOT EXISTS tra_sua_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tra_sua_db;

-- Bảng Quản trị viên
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Mật khẩu mặc định: admin123 (đã mã hóa password_hash hoặc lưu thuần túy tùy ý, ở đây code dùng chuỗi so sánh đơn giản hoặc password_verify)
INSERT INTO admin (username, password) VALUES ('admin', 'admin123') ON DUPLICATE KEY UPDATE username=username;

-- Bảng Menu Món ăn/Trà sữa
CREATE TABLE IF NOT EXISTS menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price INT NOT NULL,
    image VARCHAR(255) NOT NULL,
    description TEXT
);

-- Dữ liệu mẫu cho Menu
INSERT INTO menu (name, price, image, description) VALUES
('Trà sữa trân châu đường đen', 30000, 'https://images.unsplash.com/photo-1541658016709-82535e94bc69?w=400', 'Trà sữa đậm vị kết hợp trân châu mềm dẻo số 1.'),
('Trà sữa thái xanh', 25000, 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?w=400', 'Thơm mùi lá trà thái đặc trưng, mát lạnh sảng khoái.'),
('Hồng trà kem cheese', 35000, 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=400', 'Hồng trà thơm kết hợp lớp kem phô mai béo ngậy.'),
('Trà đào cam sả', 30000, 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=400', 'Giải nhiệt cực tốt với đào miếng giòn ngọt và sả tươi.');

-- Bảng Đơn hàng
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    items TEXT NOT NULL,
    total_price INT NOT NULL,
    status VARCHAR(50) DEFAULT 'Chờ xác nhận',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);