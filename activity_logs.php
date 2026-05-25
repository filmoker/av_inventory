<?php
session_start();
// เช็กสิทธิ์การล็อกอิน
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

// ดึงข้อมูลประวัติการใช้งาน เรียงจากล่าสุดไปเก่าสุด (จำกัด 200 รายการล่าสุดเพื่อไม่ให้หน้าเว็บโหลดช้า)
$sql = "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 200";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการใช้งานระบบ - ระบบบริหารจัดการครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .log-container { max-width: 1200px; margin: 0 auto; }
        .table th { background-color: #1e2b3c; color: white; border-bottom: none; }
        .table td { vertical-align: middle; }
    </style>
</head>
<body class="p-4">

<div class="log-container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">ประวัติการเข้าใช้งานระบบ (Activity Logs)</h4>
            <span class="text-muted small">แสดงประวัติการเปลี่ยนแปลงข้อมูลและการเข้าสู่ระบบ 200 รายการล่าสุด</span>
        </div>
        <a href="index.php" class="btn btn-secondary px-4 fw-bold">
            <i class="fas fa-arrow-left me-2"></i>กลับหน้าหลัก
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover table-striped m-0">
                    <thead class="sticky-top">
                        <tr>
                            <th width="80" class="text-center">ลำดับ</th>
                            <th width="200">วัน-เวลา</th>
                            <th width="150">บัญชีผู้ใช้ (SWU)</th>
                            <th width="150" class="text-center">การกระทำ</th>
                            <th>รายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php 
                            $i = 1;
                            while($row = $result->fetch_assoc()): 
                                // แปลงรูปแบบวันที่ให้อ่านง่าย
                                $date = new DateTime($row['created_at']);
                                $formatted_date = $date->format('d/m/Y') . ' เวลา ' . $date->format('H:i:s');
                                
                                // กำหนดสีของ Badge ตามประเภท Action
                                $badge_class = 'bg-secondary';
                                if ($row['action'] == 'เข้าสู่ระบบ') $badge_class = 'bg-info text-dark';
                                if ($row['action'] == 'เพิ่มข้อมูล') $badge_class = 'bg-success';
                                if ($row['action'] == 'แก้ไขข้อมูล') $badge_class = 'bg-warning text-dark';
                                if ($row['action'] == 'ลบข้อมูล') $badge_class = 'bg-danger';
                            ?>
                                <tr>
                                    <td class="text-center text-muted"><?php echo $i++; ?></td>
                                    <td><small class="fw-bold text-secondary"><i class="fas fa-clock me-1"></i><?php echo $formatted_date; ?></small></td>
                                    <td class="fw-bold text-primary"><i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $badge_class; ?> px-2 py-1" style="font-size: 0.85em;">
                                            <?php echo htmlspecialchars($row['action']); ?>
                                        </span>
                                    </td>
                                    <td><span class="text-dark"><?php echo htmlspecialchars($row['details']); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                                    <br>ยังไม่มีประวัติการทำงานในระบบ
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>