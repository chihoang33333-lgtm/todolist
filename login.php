<?php
// Bao gồm file config.php (đã có session_start())
require_once 'config.php';

// Khởi tạo biến lưu thông báo
$message = '';
$msg_class = '';

// Kiểm tra xem người dùng đã đăng nhập chưa, nếu rồi thì chuyển hướng
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php"); 
    exit;
}

// Kiểm tra khi form được gửi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = "Vui lòng nhập cả tên đăng nhập và mật khẩu.";
        $msg_class = "error";
    } else {
        // 1. Chuẩn bị câu lệnh SQL (Chống SQL Injection)
        $sql = "SELECT id, username, password FROM users WHERE username = ?";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username]);
            
            // 2. Lấy thông tin người dùng
            $user = $stmt->fetch();

            // 3. Xác thực người dùng và mật khẩu (YÊU CẦU BẮT BUỘC)
            // dùng password_verify() để so sánh mật khẩu nhập vào với MẬT KHẨU ĐÃ BĂM
            if ($user && password_verify($password, $user['password'])) {
                
                // 4. Đăng nhập thành công: Lưu thông tin vào SESSION
                
                $_SESSION["user_id"] = $user['id'];
                $_SESSION["username"] = $user['username'];

                // 5. Chuyển hướng đến trang quản lý công việc
                header("Location: dashboard.php");
                exit; // Luôn dùng exit sau khi chuyển hướng
            } else {
                // Đăng nhập thất bại
                $message = "Tên đăng nhập hoặc mật khẩu không chính xác.";
                $msg_class = "error";
            }

        } catch (PDOException $e) {
            $message = "Đã xảy ra lỗi: " . $e->getMessage();
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
    <title>Đăng nhập - Simple list to do</title> <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="header">
        <h1>📝 Simple list to do</h1> </div>

    <div class="container">
        <h2>Đăng nhập tài khoản</h2>

        <?php 
        // Hiển thị thông báo (lỗi hoặc thành công) nếu có
        if (!empty($message)): 
        ?>
            <div class="<?php echo $msg_class; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Tên đăng nhập:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mật khẩu:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn">Đăng nhập</button>
        </form>

        <p style="text-align: center; margin-top: 15px;">
            Chưa có tài khoản? <a href="register.php">Đăng ký tại đây</a>
        </p>
    </div>

</body>
</html>
