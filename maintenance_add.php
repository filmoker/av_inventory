<?php

require_once 'db_connect.php';

// ส่วนที่ 1: จัดการเมื่อมีการกดปุ่ม "บันทึกการแจ้งซ่อม"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $equipment_id = $_POST['equipment_id'];
    $issue_detail = $_POST['issue_detail'];
    $reported_date = $_POST['reported_date'];

    // 1. เพิ่มข้อมูลลงในตาราง maintenance_logs
    $sql_insert = "INSERT INTO maintenance_logs (equipment_id, issue_detail, reported_date, repair_status) 
                   VALUES ('$equipment_id', '$issue_detail', '$reported_date', 'รอตรวจสอบ')";

    if ($conn->query($sql_insert) === TRUE) {
        // 2. อัปเดตสถานะในตาราง equipments ให้เป็น "กำลังซ่อม" อัตโนมัติ
        $conn->query("UPDATE equipments SET status = 'กำลังซ่อม' WHERE id = $equipment_id");
        
        echo "<script>alert('บันทึกการแจ้งซ่อมเรียบร้อยแล้ว!'); window.location.href='maintenance.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาด: " . $conn->error . "');</script>";
    }
}

// ส่วนที่ 2: ดึงรายชื่อครุภัณฑ์ที่สถานะเป็น 'ชำรุด' หรือ 'พร้อมใช้งาน' มาให้เลือกแจ้งซ่อม
$sql_equip = "SELECT id, equipment_code, name_model FROM equipments WHERE status != 'กำลังซ่อม' ORDER BY equipment_code ASC";
$result_equip = $conn->query($sql_equip);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งซ่อมครุภัณฑ์ - ระบบบริหารครุภัณฑ์</title>
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
        <!-- แถบเมนูด้านซ้าย -->
        <div class="col-md-2 sidebar p-0">
            <div class="p-4 text-center border-bottom border-secondary">
                <h5 class="m-0"><i class="fas fa-boxes"></i> AMDAT</h5>
            </div>
            <nav class="mt-3">
                <a href="index.php"><i class="fas fa-home me-2"></i> หน้าแรก</a>
                <a href="equipments.php"><i class="fas fa-desktop me-2"></i> รายการครุภัณฑ์</a>
                <a href="maintenance.php" class="active"><i class="fas fa-tools me-2"></i> ประวัติซ่อมบำรุง</a>
                <a href="locations.php"><i class="fas fa-map-marker-alt me-2"></i> จัดการสถานที่</a>
                <a href="categories.php"><i class="fas fa-tags me-2"></i> จัดการหมวดหมู่</a>
                <a href="units.php"><i class="fas fa-layer-group me-2"></i> จัดการหน่วยงาน</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a>
            </nav>
        </div>

        <!-- พื้นที่แสดงข้อมูลด้านขวา -->
        <div class="col-md-10 p-4">
            <div class="mx-auto" style="max-width: 700px;">
                <div class="mb-4">
                    <h4> บันทึกการแจ้งซ่อมครุภัณฑ์ใหม่</h4>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <form action="maintenance_add.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">เลือกครุภัณฑ์ที่ต้องการแจ้งซ่อม <span class="text-danger">*</span></label>
                                <select class="form-select" name="equipment_id" required>
                                    <option value="" disabled selected>-- ค้นหาและเลือกอุปกรณ์ --</option>
                                    <?php while($eq = $result_equip->fetch_assoc()): ?>
                                        <option value="<?php echo $eq['id']; ?>">
                                            <?php echo $eq['equipment_code'] . " - " . $eq['name_model']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="form-text text-muted">เฉพาะอุปกรณ์ที่ยังไม่ได้อยู่ในระหว่างการซ่อม</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">รายละเอียดอาการเสีย / ปัญหาที่พบ <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="issue_detail" rows="4" placeholder="ระบุอาการเสียโดยละเอียด เช่น เปิดเครื่องไม่ติด หรือภาพไม่ออกจอ" required></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">วันที่แจ้งซ่อม <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="reported_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-end">
                                <a href="maintenance.php" class="btn btn-secondary me-2">ยกเลิก</a>
                                <button type="submit" class="btn btn-warning text-dark"> ยืนยันการส่งซ่อม</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>