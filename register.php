<?php
// Bao gồm file config.php
require_once 'config.php';

// Khởi tạo biến để lưu trữ thông báo
$message = '';

// Kiểm tra xem form đã được gửi đi chưa (Khi người dùng nhấn nút "Đăng ký")
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Lấy dữ liệu từ form
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email']; // Email này có thể NULL theo CSDL [cite: 14]

    // 2. Validate dữ liệu cơ bản (Đảm bảo không rỗng)
    if (empty($username) || empty($password)) {
        $message = "Tên đăng nhập và Mật khẩu là bắt buộc!";
        $msg_class = "error";
    } else {
        
        // 3. Băm mật khẩu (YÊU CẦU BẮT BUỘC CỦA BÀI TẬP) 
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 4. Chuẩn bị câu lệnh SQL (Sử dụng Prepared Statements để chống SQL Injection) [cite: 51]
        $sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
        
        try {
            // 5. Thực thi câu lệnh
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $hashed_password, $email]);

            // 6. Hiển thị thông báo thành công
            $message = "Đăng ký tài khoản thành công! Bạn có thể đăng nhập ngay bây giờ.";
            $msg_class = "success";

        } catch (PDOException $e) {
            // 7. Xử lý lỗi (ví dụ: Tên đăng nhập hoặc Email đã tồn tại) [cite: 12, 14]
            if ($e->getCode() == 23000) { // Mã lỗi 23000 là lỗi UNIQUE (trùng lặp)
                $message = "Tên đăng nhập hoặc Email đã tồn tại. Vui lòng chọn tên khác.";
            } else {
                $message = "Đã xảy ra lỗi: " . $e->getMessage();
            }
            $msg_class = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Simple list to do</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="header">
        <h1>📝 Simple list to do</h1>
    </div>

    <div class="container">
        <h2>Tạo tài khoản mới</h2>
        <p>Tham gia và quản lý công việc của bạn một cách hiệu quả.</p>

        <?php 
        // Hiển thị thông báo (lỗi hoặc thành công) nếu có
        if (!empty($message)): 
        ?>
            <div class="<?php echo $msg_class; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="username">Tên đăng nhập:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email (Tùy chọn):</label>
                <input type="email" id="email" name="email">
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn">Đăng ký</button>
        </form>

        <p style="text-align: center; margin-top: 15px;">
            Đã có tài khoản? <a href="login.php">Đăng nhập tại đây</a>
        </p>
    </div>

</body>
</html>