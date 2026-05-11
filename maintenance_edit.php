<?php

require_once 'db_connect.php';

// ส่วนที่ 1: จัดการเมื่อมีการกดปุ่ม "บันทึกการอัปเดต"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $repair_status = $_POST['repair_status'];
    $fixed_date = ($_POST['repair_status'] == 'ซ่อมเสร็จแล้ว') ? $_POST['fixed_date'] : NULL;
    
    // อัปเดตตาราง maintenance_logs
    $sql_update = "UPDATE maintenance_logs SET 
                    repair_status = '$repair_status', 
                    fixed_date = " . ($fixed_date ? "'$fixed_date'" : "NULL") . " 
                   WHERE id = $id";

    if ($conn->query($sql_update) === TRUE) {
        // 💡 พิเศษ: ถ้าซ่อมเสร็จแล้ว ให้ไปอัปเดตสถานะในตารางหลัก (equipments) เป็น "พร้อมใช้งาน" อัตโนมัติ
        if ($repair_status == 'ซ่อมเสร็จแล้ว') {
            $equipment_id = $_POST['equipment_id'];
            $conn->query("UPDATE equipments SET status = 'พร้อมใช้งาน' WHERE id = $equipment_id");
        }
        echo "<script>alert('อัปเดตสถานะการซ่อมเรียบร้อยแล้ว!'); window.location.href='maintenance.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาด: " . $conn->error . "');</script>";
    }
}

// ส่วนที่ 2: ดึงข้อมูลประวัติการซ่อมเดิมมาแสดง
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT m.*, e.equipment_code, e.name_model 
            FROM maintenance_logs m 
            JOIN equipments e ON m.equipment_id = e.id 
            WHERE m.id = $id";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
} else {
    header("Location: maintenance.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัปเดตการซ่อม - ระบบบริหารครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .sidebar { background-color: #1e2b3c; min-height: 100vh; color: #fff; }
        .sidebar a { color: #c2c7d0; text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid #2b3c53; }
        .sidebar a:hover { background-color: #2b3c53; color: #fff; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-0">
            <div class="p-4 text-center border-bottom border-secondary">
                <h5 class="m-0"><i class="fas fa-boxes"></i> ระบบครุภัณฑ์</h5>
            </div>
            <nav class="mt-3">
                <a href="index.php"><i class="fas fa-home me-2"></i> หน้าแรก</a>
                <a href="equipments.php"><i class="fas fa-desktop me-2"></i> รายการครุภัณฑ์</a>
                <a href="maintenance.php" class="active"><i class="fas fa-tools me-2"></i> ประวัติซ่อมบำรุง</a>
                <a href="locations.php"><i class="fas fa-map-marker-alt me-2"></i> จัดการสถานที่</a>
                <a href="categories.php"><i class="fas fa-tags me-2"></i> จัดการหมวดหมู่</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a>
            </nav>
        </div>

        <div class="col-md-10 p-4">
            <div class="mx-auto" style="max-width: 600px;">
                <h4 class="mb-4"><i class="fas fa-tools me-2"></i> อัปเดตสถานะการซ่อมบำรุง</h4>
                
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <form action="maintenance_edit.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="equipment_id" value="<?php echo $row['equipment_id']; ?>">

                            <div class="mb-3">
                                <label class="form-label text-muted">รหัสครุภัณฑ์</label>
                                <input type="text" class="form-control bg-light" value="<?php echo $row['equipment_code']; ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">อุปกรณ์</label>
                                <input type="text" class="form-control bg-light" value="<?php echo $row['name_model']; ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">อาการเสียที่แจ้งไว้</label>
                                <textarea class="form-control bg-light" rows="2" readonly><?php echo $row['issue_detail']; ?></textarea>
                            </div>

                            <hr>

                            <div class="mb-3">
                                <label class="form-label">สถานะการซ่อมปัจจุบัน <span class="text-danger">*</span></label>
                                <select class="form-select" name="repair_status" id="repair_status" required onchange="toggleFixedDate()">
                                    <option value="รอตรวจสอบ" <?php if($row['repair_status'] == 'รอตรวจสอบ') echo 'selected'; ?>>รอตรวจสอบ</option>
                                    <option value="กำลังซ่อม" <?php if($row['repair_status'] == 'กำลังซ่อม') echo 'selected'; ?>>กำลังซ่อม</option>
                                    <option value="ซร็จแล้ว" <?php if($row['repair_status'] == 'ซ่อมเสร็จแล้ว') echo 'selected'; ?>>ซ่อมเสร็จแล้ว</option>
                                </select>
                            </div>

                            <div class="mb-4" id="fixed_date_div" style="display: <?php echo ($row['repair_status'] == 'ซ่อมเสร็จแล้ว') ? 'block' : 'none'; ?>;">
                                <label class="form-label">วันที่ซ่อมเสร็จจริง <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="fixed_date" value="<?php echo $row['fixed_date'] ? $row['fixed_date'] : date('Y-m-d'); ?>">
                            </div>

                            <div class="d-flex justify-content-end">
                                <a href="maintenance.php" class="btn btn-secondary me-2">ยกเลิก</a>
                                <button type="submit" class="btn btn-info text-white"> บันทึกการอัปเดต</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ฟังก์ชันเปิด-ปิด ช่องกรอกวันที่ซ่อมเสร็จ ตามสถานะ
    function toggleFixedDate() {
        const status = document.getElementById('repair_status').value;
        const dateDiv = document.getElementById('fixed_date_div');
        dateDiv.style.display = (status === 'ซ่อมเสร็จแล้ว') ? 'block' : 'none';
    }
</script>
</body>
</html>