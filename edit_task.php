<?php
// Bao gồm file config.php
require_once 'config.php';

// ---- YÊU CẦU BẢO MẬT 1: Kiểm tra đăng nhập ----
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Lấy user_id từ session
$user_id = $_SESSION["user_id"];
$message = '';
$msg_class = '';
$task = null; // Biến để lưu thông tin công việc

// 1. Lấy task_id từ URL
if (!isset($_GET['task_id']) || empty($_GET['task_id'])) {
    // Nếu không có task_id, đá về dashboard
    header("Location: dashboard.php");
    exit;
}
$task_id = $_GET['task_id'];


// 2. XỬ LÝ KHI NGƯỜI DÙNG NHẤN NÚT "CẬP NHẬT" (Phương thức POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu đã chỉnh sửa từ form
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];

    // Gán NULL nếu rỗng
    $description = empty($description) ? NULL : $description;
    $due_date = empty($due_date) ? NULL : $due_date;

    if (empty($title)) {
        $message = "Tiêu đề không được để trống!";
        $msg_class = "error";
    } else {
        try {
            // Chuẩn bị câu lệnh UPDATE
            // ---- YÊU CẦU BẢO MẬT 2: Chỉ update task của ĐÚNG user này ----
            $sql = "UPDATE tasks SET title = ?, description = ?, due_date = ?, status = ? 
                    WHERE id = ? AND user_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $description, $due_date, $status, $task_id, $user_id]);

            // Cập nhật thành công, chuyển hướng về dashboard
            header("Location: dashboard.php");
            exit;

        } catch (PDOException $e) {
            $message = "Lỗi khi cập nhật công việc: " . $e->getMessage();
            $msg_class = "error";
        }
    }
}


// 3. LẤY THÔNG TIN CÔNG VIỆC ĐỂ HIỂN THỊ RA FORM (Phương thức GET)
try {
    // ---- YÊU CẦU BẢO MẬT 3: Chỉ lấy task của ĐÚNG user này ----
    $sql_select = "SELECT * FROM tasks WHERE id = ? AND user_id = ?";
    $stmt_select = $pdo->prepare($sql_select);
    $stmt_select->execute([$task_id, $user_id]);
    
    $task = $stmt_select->fetch();

    // Nếu không tìm thấy công việc (hoặc công việc không thuộc về user này)
    if (!$task) {
        // Chuyển hướng về dashboard
        header("Location: dashboard.php");
        exit;
    }

} catch (PDOException $e) {
    // Có lỗi CSDL, dừng lại
    die("Lỗi: không thể tải công việc. " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa công việc - Simple list to do</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="header">
        <h1>📝 Simple list to do</h1>
        <div class="user-info">
            Chào, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong>!
            <a href="logout.php" class="logout-btn">(Đăng xuất)</a>
        </div>
    </div>

    <div class="container">
        <h2>Chỉnh sửa công việc</h2>
        
        <?php // Hiển thị thông báo nếu có
        if (!empty($message)) {
            echo "<div class'{$msg_class}'>{$message}</div>";
        }
        ?>

        <form action="edit_task.php?task_id=<?php echo $task['id']; ?>" method="POST">
            
            <div class="form-group">
                <label for="title">Tiêu đề (Bắt buộc):</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($task['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Mô tả (Tùy chọn):</label>
                <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($task['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="due_date">Ngày hết hạn (Tùy chọn):</label>
                <input type="date" id="due_date" name="due_date" value="<?php echo htmlspecialchars($task['due_date'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="status">Trạng thái:</label>
                <select id="status" name="status">
                    <option value="pending" <?php if ($task['status'] == 'pending') echo 'selected'; ?>>🕒 Đang chờ</option>
                    <option value="in_progress" <?php if ($task['status'] == 'in_progress') echo 'selected'; ?>>⏳ Đang làm</option>
                    <option value="completed" <?php if ($task['status'] == 'completed') echo 'selected'; ?>>✅ Hoàn thành</option>
                </select>
            </div>

            <button type="submit" class="btn">Cập nhật công việc</button>
            <a href="dashboard.php" style="display: block; text-align: center; margin-top: 15px;">Hủy bỏ và quay lại</a>
        </form>

    </div>

</body>
</html>