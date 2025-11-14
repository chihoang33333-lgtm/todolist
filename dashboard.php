<?php
// Bao gồm file config.php (để bắt đầu session và kết nối CSDL)
require_once 'config.php';

// ---- YÊU CẦU BẢO MẬT: Kiểm tra truy cập ----
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Lấy ID người dùng từ session
$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
$message = '';
$msg_class = '';

// --- LOGIC XỬ LÝ (CREATE, UPDATE, DELETE) ---

// Kiểm tra xem có hành động (action) được gửi lên không
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- C (CREATE): Thêm công việc mới ---
    if (isset($_POST['action']) && $_POST['action'] == 'add_task') {
        $title = $_POST['title'];
        $description = $_POST['description']; // có thể NULL
        $due_date = $_POST['due_date']; // có thể NULL

        // Gán NULL nếu rỗng
        $description = empty($description) ? NULL : $description;
        $due_date = empty($due_date) ? NULL : $due_date;

        if (!empty($title)) {
            try {
                // Sử dụng Prepared Statements để chống SQL Injection
                $sql = "INSERT INTO tasks (user_id, title, description, due_date) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                // Công việc phải được liên kết với user_id của người đang đăng nhập
                $stmt->execute([$user_id, $title, $description, $due_date]);
                
                $message = "Thêm công việc thành công!";
                $msg_class = "success";

            } catch (PDOException $e) {
                $message = "Lỗi khi thêm công việc: " . $e->getMessage();
                $msg_class = "error";
            }
        } else {
            $message = "Tiêu đề công việc không được để trống.";
            $msg_class = "error";
        }
    }
}

// --- Xử lý cho UPDATE (Toggle Status) và DELETE (qua phương thức GET) ---
if (isset($_GET['action'])) {
    $task_id = $_GET['task_id'];

    // --- U (UPDATE): Đánh dấu hoàn thành (Toggle) ---
    // (Đây là cách đơn giản để thay đổi trạng thái)
    if ($_GET['action'] == 'toggle' && !empty($task_id)) {
        try {
            // Cập nhật trạng thái: nếu là 'completed' thì đổi thành 'pending', và ngược lại
            // Quan trọng: Phải kiểm tra task này thuộc về user_id này
            $sql = "UPDATE tasks 
                    SET status = IF(status = 'completed', 'pending', 'completed') 
                    WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$task_id, $user_id]);

        } catch (PDOException $e) {
            $message = "Lỗi khi cập nhật trạng thái.";
            $msg_class = "error";
        }
    }

    // --- D (DELETE): Xóa công việc ---
    if ($_GET['action'] == 'delete' && !empty($task_id)) {
        try {
            // Quan trọng: Phải kiểm tra task này thuộc về user_id này
            $sql = "DELETE FROM tasks WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$task_id, $user_id]);
            
            $message = "Đã xóa công việc!";
            $msg_class = "success";
            
        } catch (PDOException $e) {
            $message = "Lỗi khi xóa công việc.";
            $msg_class = "error";
        }
    }
}


// --- R (READ): Lấy danh sách công việc CỦA NGƯỜI DÙNG NÀY ---
// Xử lý Lọc và Sắp xếp
$filter_status = $_GET['filter_status'] ?? 'all'; // Lấy từ URL, mặc định là 'all'
$sort_by = $_GET['sort_by'] ?? 'due_date'; // Mặc định sắp xếp theo ngày hết hạn

// Xây dựng câu lệnh SQL
$sql_select = "SELECT id, title, description, due_date, status, created_at 
               FROM tasks 
               WHERE user_id = ?"; // Chỉ lấy task của user đang đăng nhập

// Thêm điều kiện Lọc (Filter)
$params = [$user_id];
if ($filter_status != 'all') {
    $sql_select .= " AND status = ?";
    $params[] = $filter_status;
}

// Thêm điều kiện Sắp xếp (Sort)
if ($sort_by == 'due_date') {
    $sql_select .= " ORDER BY due_date ASC";
} elseif ($sort_by == 'created_at') {
    $sql_select .= " ORDER BY created_at DESC";
}

// Thực thi câu lệnh SELECT
$stmt_select = $pdo->prepare($sql_select);
$stmt_select->execute($params);
$tasks = $stmt_select->fetchAll();

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Simple list to do</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="header">
        <h1>📝 Simple list to do</h1>
        <div class="user-info">
            Chào, <strong><?php echo htmlspecialchars($username); ?></strong>!
            <a href="logout.php" class="logout-btn">(Đăng xuất)</a>
        </div>
    </div>

    <div class="container">

        <div class="task-form">
            <h2>Thêm công việc mới</h2>
            
            <?php // Hiển thị thông báo nếu có
            if (!empty($message)) {
                echo "<div class='{$msg_class}'>{$message}</div>";
            }
            ?>

            <form action="dashboard.php" method="POST">
                <input type="hidden" name="action" value="add_task">
                
                <div class="form-group">
                    <label for="title">Tiêu đề (Bắt buộc):</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="description">Mô tả (Tùy chọn):</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="due_date">Ngày hết hạn (Tùy chọn):</label>
                    <input type="date" id="due_date" name="due_date">
                </div>
                <button type="submit" class="btn">Thêm công việc</button>
            </form>
        </div>

        <hr style="margin: 30px 0;">

        <div class="task-list">
            <h2>Danh sách công việc của bạn</h2>

            <form action="dashboard.php" method="GET" class="filter-form">
                <div class="form-group">
                    <label for="filter_status">Lọc theo trạng thái:</label>
                    <select id="filter_status" name="filter_status">
                        <option value="all" <?php if($filter_status == 'all') echo 'selected'; ?>>Tất cả</option>
                        <option value="pending" <?php if($filter_status == 'pending') echo 'selected'; ?>>Đang chờ</option>
                        <option value="in_progress" <?php if($filter_status == 'in_progress') echo 'selected'; ?>>Đang làm</option>
                        <option value="completed" <?php if($filter_status == 'completed') echo 'selected'; ?>>Hoàn thành</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sort_by">Sắp xếp theo:</label>
                    <select id="sort_by" name="sort_by">
                        <option value="due_date" <?php if($sort_by == 'due_date') echo 'selected'; ?>>Ngày hết hạn</option>
                        <option value="created_at" <?php if($sort_by == 'created_at') echo 'selected'; ?>>Ngày tạo mới nhất</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-filter">Lọc / Sắp xếp</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Trạng thái</th>
                        <th>Tiêu đề</th>
                        <th>Mô tả</th>
                        <th>Ngày hết hạn</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Bạn chưa có công việc nào. Hãy thêm một công việc mới!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <tr class="task-item <?php echo $task['status']; ?>">
                                
                                <td>
                                    <a href="dashboard.php?action=toggle&task_id=<?php echo $task['id']; ?>" class="btn-status">
                                        <?php 
                                            if ($task['status'] == 'completed') echo '✅ Hoàn thành';
                                            elseif ($task['status'] == 'in_progress') echo '⏳ Đang làm';
                                            else echo '🕒 Đang chờ';
                                        ?>
                                    </a>
                                </td>
                                
                                <td><?php echo htmlspecialchars($task['title']); ?></td>
                                <td><?php echo htmlspecialchars($task['description'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($task['due_date'] ?? 'N/A'); ?></td>
                                
                                <td class="actions">
                                    <a href="edit_task.php?task_id=<?php echo $task['id']; ?>" class="btn-edit">Sửa</a> 
                                   <a href="dashboard.php?action=delete&task_id=<?php echo $task['id']; ?>" 
                                       class="btn-delete" 
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa công việc này?');">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>