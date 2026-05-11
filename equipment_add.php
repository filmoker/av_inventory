<?php
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $equipment_code = $_POST['equipment_code'];
    
    $check_sql = "SELECT id FROM equipments WHERE equipment_code = '$equipment_code'";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        echo "<script>alert('❌ บันทึกไม่ได้: รหัสครุภัณฑ์ \"$equipment_code\" มีอยู่ในระบบแล้วกรุณาตรวจสอบอีกครั้ง'); window.history.back();</script>";
        exit();
    }

    $equipment_name = $_POST['equipment_name'];
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $serial_number = $_POST['serial_number'];
    $category_id = $_POST['category_id'];
    $location_id = $_POST['location_id'];
    $campus = $_POST['campus']; 
    $status = $_POST['status'];
    $entry_date = $_POST['entry_date'];
    $remark = isset($_POST['remark']) ? $_POST['remark'] : '';

    // จัดการวันที่อัปเดตสถานะ
    if ($status == 'พร้อมใช้งาน') {
        $status_updated_at_sql = "NULL"; 
    } else {
        $date_val = !empty($_POST['status_updated_at']) ? $_POST['status_updated_at'] : date('Y-m-d');
        $time_val = date('H:i:s'); // แอบเพิ่มเวลาให้ตรงกับตอนบันทึก
        $status_updated_at_sql = "'" . $conn->real_escape_string($date_val . ' ' . $time_val) . "'";
    }

    // 🌟 เพิ่ม created_at และ NOW() ลงไปในคำสั่งบันทึก
    $sql_insert = "INSERT INTO equipments (equipment_code, equipment_name, brand, model, serial_number, category_id, location_id, campus, status, status_updated_at, entry_date, remark, created_at) 
                   VALUES ('$equipment_code', '$equipment_name', '$brand', '$model', '$serial_number', '$category_id', '$location_id', '$campus', '$status', $status_updated_at_sql, '$entry_date', '$remark', NOW())";

    try {
        if ($conn->query($sql_insert) === TRUE) {
            header("Location: equipments.php?status=success");
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        echo "<script>alert('เกิดข้อผิดพลาดระบบฐานข้อมูล: " . $e->getMessage() . "'); window.history.back();</script>";
        exit();
    }
}

$result_categories = $conn->query("SELECT * FROM categories");
$result_locations = $conn->query("SELECT * FROM locations");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มครุภัณฑ์ใหม่ - ระบบบริหารครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        
        /* 🌟 ล็อกความกว้าง Sidebar ไว้ที่ 220px */
        .sidebar { background-color: #1e2b3c; min-height: 100vh; color: #fff; width: 220px; }
        .sidebar a { color: #c2c7d0; text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid #2b3c53; }
        .sidebar a:hover, .sidebar a.active { background-color: #2b3c53; color: #fff; }
        .form-label { font-weight: 500; }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="d-flex flex-nowrap">
        
        <div class="sidebar p-0 flex-shrink-0">
            <div class="p-4 text-center border-bottom border-secondary">
                <h5 class="m-0"><i class="fas fa-boxes"></i> ระบบครุภัณฑ์</h5>
            </div>
            <nav class="mt-3">
                <a href="index.php"><i class="fas fa-home me-2"></i> หน้าแรก</a>
                <a href="equipments.php" class="active"><i class="fas fa-desktop me-2"></i> รายการครุภัณฑ์</a>
                <a href="locations.php"><i class="fas fa-map-marker-alt me-2"></i> จัดการสถานที่</a>
                <a href="categories.php"><i class="fas fa-tags me-2"></i> จัดการหมวดหมู่</a>
                <a href="report.php"><i class="fas fa-print me-2"></i> พิมพ์รายงานสรุปยอด</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a>
            </nav>
        </div>

        <div class="p-4 bg-light flex-grow-1" style="min-width: 0; overflow-x: auto;">
            <div class="mx-auto" style="max-width: 800px;">
                <div class="mb-4 mt-3">
                    <h4>ขึ้นทะเบียนครุภัณฑ์ใหม่</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="equipments.php">รายการครุภัณฑ์</a></li>
                            <li class="breadcrumb-item active" aria-current="page">เพิ่มครุภัณฑ์ใหม่</li>
                        </ol>
                    </nav>
                </div>
                
                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-body p-4">
                        <form action="equipment_add.php" method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">รหัสครุภัณฑ์ <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="equipment_code" placeholder="เช่น สห.67-009" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">หมายเลขซีเรียล (S/N)</label>
                                    <input type="text" class="form-control" name="serial_number" placeholder="เช่น SN-A1001">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">ชื่อครุภัณฑ์ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="equipment_name" placeholder="เช่น โทรทัศน์แอลอีดี (LED TV)" required>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">แบรนด์/ยี่ห้อ</label>
                                    <input type="text" class="form-control" name="brand" placeholder="เช่น Samsung">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">รุ่นสินค้า</label>
                                    <input type="text" class="form-control" name="model" placeholder="เช่น UA55AU7700">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                                    <select class="form-select" name="category_id" required>
                                        <option value="" disabled selected>-- เลือกหมวดหมู่ --</option>
                                        <?php while($cat = $result_categories->fetch_assoc()): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['category_name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">สถานที่จัดเก็บ <span class="text-danger">*</span></label>
                                    <select class="form-select" name="location_id" required>
                                        <option value="" disabled selected>-- เลือกสถานที่จัดเก็บ --</option>
                                        <?php while($loc = $result_locations->fetch_assoc()): ?>
                                            <option value="<?php echo $loc['id']; ?>"><?php echo $loc['location_name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">วิทยาเขต <span class="text-danger">*</span></label>
                                    <select class="form-select" name="campus" required>
                                        <option value="" disabled selected>-- เลือกวิทยาเขต --</option>
                                        <option value="ประสานมิตร">มศว ประสานมิตร</option>
                                        <option value="องครักษ์">มศว องครักษ์</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                                    <select class="form-select" name="status" id="statusSelect" required>
                                        <option value="พร้อมใช้งาน" selected>พร้อมใช้งาน</option>
                                        <option value="ชำรุด">ชำรุด</option>
                                        <option value="กำลังซ่อม">กำลังซ่อม</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">วันที่รับเข้า <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="entry_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6" id="dateGroup" style="display: none;">
                                    <label class="form-label text-danger fw-bold">วันที่แจ้งชำรุด/ส่งซ่อม <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control border-danger" name="status_updated_at" id="dateInput">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">หมายเหตุ (กรณีกำลังซ่อมหรือชำรุด)</label>
                                <textarea class="form-control" name="remark" rows="2" placeholder="ระบุรายละเอียดเพิ่มเติม (ถ้ามี)"></textarea>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-end gap-2">
                                <a href="equipments.php" class="btn btn-secondary px-4">ยกเลิก</a>
                                <button type="submit" class="btn btn-success px-4"> บันทึกข้อมูล</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// สคริปต์ซ่อน/แสดงช่องวันที่
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('statusSelect');
    const dateGroup = document.getElementById('dateGroup');
    const dateInput = document.getElementById('dateInput');

    function toggleDateField() {
        if (statusSelect.value === 'ชำรุด' || statusSelect.value === 'กำลังซ่อม') {
            dateGroup.style.display = 'block';
            dateInput.required = true;
            if (!dateInput.value) {
                dateInput.value = new Date().toISOString().split('T')[0]; // ใส่วันที่ปัจจุบันอัตโนมัติ
            }
        } else {
            dateGroup.style.display = 'none';
            dateInput.required = false;
            dateInput.value = '';
        }
    }
    statusSelect.addEventListener('change', toggleDateField);
    toggleDateField(); 
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>