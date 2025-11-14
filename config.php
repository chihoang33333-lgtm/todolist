<?php
/*
 * File cấu hình kết nối CSDL (Sử dụng PDO)
 */

// Bắt đầu session - Bắt buộc cho tính năng đăng nhập [cite: 31]
// Đặt ở đây để mọi trang gọi config.php đều tự động khởi động session
session_start();

// Thông tin CSDL
$db_host = 'localhost';     
$db_name = 'db_todolist';   
$db_user = 'root';          
$db_pass = '';              
$charset = 'utf8mb4';       

// Cấu hình Data Source Name (DSN)
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$charset";

// Cấu hình các tùy chọn cho PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Bật báo lỗi (giúp debug)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Đặt chế độ fetch mặc định là mảng kết hợp
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Tắt chế độ mô phỏng prepared statements (tăng bảo mật) [cite: 51]
];

// Thử kết nối
try {
    // Tạo một đối tượng PDO (đây là kết nối)
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    
} catch (\PDOException $e) {
    // Nếu kết nối thất bại, hiển thị lỗi và dừng chương trình
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

?>