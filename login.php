<?php
session_start();
require_once 'db_connect.php';

// ถ้า Login อยู่แล้ว ให้เด้งไปหน้าแรกเลย
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password']; // ในระบบจริงควรใช้การ password_hash

    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['full_name'] = $row['full_name'];
        header("Location: index.php");
    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ - ระบบบริหารครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #1e2b3c; height: 100vh; display: flex; align-items: center; }
        .login-card { max-width: 400px; width: 100%; border-radius: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card login-card mx-auto shadow-lg border-0">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-primary">ระบบบริหารครุภัณฑ์</h4>
                    <p class="text-muted">กรุณาเข้าสู่ระบบเพื่อใช้งาน</p>
                </div>
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger py-2 text-center small"><?php echo $error; ?></div>
                <?php endif; ?>
                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">ชื่อผู้ใช้งาน</label>
                        <input type="text" name="username" class="form-control" required placeholder="Username">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">รหัสผ่าน</label>
                        <input type="password" name="password" class="form-control" required placeholder="Password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm">เข้าสู่ระบบ</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 