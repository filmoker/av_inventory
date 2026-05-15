<?php
require_once 'db_connect.php';

$cat_result = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
$loc_result = $conn->query("SELECT * FROM locations ORDER BY location_name ASC");

// ดึงข้อมูลยี่ห้อ (Brand) ทั้งหมดที่มีในระบบ (ไม่ซ้ำกัน) และเรียง A-Z
$brand_query = "SELECT DISTINCT brand FROM equipments WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC";
$brand_result = $conn->query($brand_query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipment_code_num = trim($_POST['equipment_code_number']);
    $equipment_code = $conn->real_escape_string('สห.' . $equipment_code_num);
    $equipment_name = $conn->real_escape_string(trim($_POST['equipment_name']));
    
    $brand_val = $_POST['brand_select'];
    if ($brand_val === 'other') {
        $brand_val = trim($_POST['brand_other']); 
    }
    $brand = $conn->real_escape_string($brand_val);
    
    $model = $conn->real_escape_string(trim($_POST['model']));
    $serial_number = $conn->real_escape_string(trim($_POST['serial_number']));
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : "NULL";
    $location_id = !empty($_POST['location_id']) ? $_POST['location_id'] : "NULL";
    $campus = $conn->real_escape_string($_POST['campus']);
    $responsible_person = $conn->real_escape_string(trim($_POST['responsible_person']));
    $status = $conn->real_escape_string($_POST['status']);
    
    if ($status == 'ชำรุด' || $status == 'กำลังซ่อม') {
        $status_updated_at = !empty($_POST['status_updated_at']) ? "'".$conn->real_escape_string($_POST['status_updated_at'])."'" : "NULL";
    } else {
        $status_updated_at = "NULL";
    }
    
    $entry_date = !empty($_POST['entry_date']) ? "'".$conn->real_escape_string($_POST['entry_date'])."'" : "NULL";
    $remark = $conn->real_escape_string(trim($_POST['remark']));

    $check_sql = "SELECT id FROM equipments WHERE equipment_code = '$equipment_code'";
    if ($conn->query($check_sql)->num_rows > 0) {
        $error = "รหัสครุภัณฑ์นี้มีอยู่ในระบบแล้ว กรุณาใช้รหัสอื่น";
    } else {
        $sql = "INSERT INTO equipments (
                    equipment_code, equipment_name, brand, model, serial_number, 
                    category_id, location_id, campus, responsible_person, status, 
                    status_updated_at, entry_date, remark, created_at, updated_at
                ) VALUES (
                    '$equipment_code', '$equipment_name', '$brand', '$model', '$serial_number', 
                    $category_id, $location_id, '$campus', '$responsible_person', '$status', 
                    $status_updated_at, $entry_date, '$remark', NOW(), NOW()
                )";

        if ($conn->query($sql) === TRUE) {
            header("Location: equipments.php?msg=add_success");
            exit();
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}
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
        .sidebar { background-color: #1e2b3c; min-height: 100vh; color: #fff; width: 220px; }
        .sidebar a { color: #c2c7d0; text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid #2b3c53; }
        .sidebar a:hover, .sidebar a.active { background-color: #2b3c53; color: #fff; }
        .hover-white:hover { color: #ffffff !important; }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="d-flex flex-nowrap">
        <div class="sidebar p-0 flex-shrink-0">
            <div class="p-4 text-center border-bottom border-secondary">
                <a href="index.php" class="text-white text-decoration-none d-block">
                    <h5 class="m-0"><i class="fas fa-boxes"></i> ระบบครุภัณฑ์</h5>
                </a>
            </div>
            <nav class="mt-3">
                <a href="index.php"><i class="fas fa-home me-2"></i> หน้าแรก</a>
                <a href="#equipmentMenu" data-bs-toggle="collapse" class="text-white fw-bold active">
                    <i class="fas fa-desktop me-2"></i> รายการครุภัณฑ์
                </a>
                <div class="collapse show" id="equipmentMenu" style="background-color: #16202c;">
                    <a href="equipments.php?location=ประสานมิตร" class="text-white-50 hover-white" style="padding-left: 45px;">มศว ประสานมิตร</a>
                    <a href="equipments.php?location=องครักษ์" class="text-white-50 hover-white" style="padding-left: 45px;">มศว องครักษ์</a>
                </div>
                <a href="locations.php"><i class="fas fa-map-marker-alt me-2"></i> จัดการสถานที่</a>
                <a href="categories.php"><i class="fas fa-tags me-2"></i> จัดการหมวดหมู่</a>
                <a href="report.php"><i class="fas fa-print me-2"></i> พิมพ์รายงานสรุปยอด</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a>
            </nav>
        </div>

        <div class="col-md-10 p-4 bg-light flex-grow-1" style="min-width: 0;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-plus-circle text-primary me-2"></i> ขึ้นทะเบียนครุภัณฑ์ใหม่</h4>
                <a href="equipments.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> กลับหน้ารายการ</a>
            </div>

            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="equipment_add.php" method="POST">
                        <h5 class="border-bottom pb-2 mb-4 text-primary">ข้อมูลพื้นฐาน</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="equipment_code_number" class="form-label">รหัสครุภัณฑ์ <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-dark fw-bold">สห.</span>
                                    <input type="text" class="form-control" id="equipment_code_number" name="equipment_code_number" required placeholder="กรอกเฉพาะตัวเลข เช่น 330000010041">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="equipment_name" class="form-label">ชื่อครุภัณฑ์ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="equipment_name" name="equipment_name" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="brand_select" class="form-label">ยี่ห้อ (Brand)</label>
                                <select class="form-select" id="brand_select" name="brand_select">
                                    <option value="">-- ไม่ระบุ --</option>
                                    <?php 
                                    $brand_result->data_seek(0);
                                    while($b = $brand_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($b['brand']); ?>"><?php echo htmlspecialchars($b['brand']); ?></option>
                                    <?php endwhile; ?>
                                    <option value="other" class="fw-bold text-primary">+ อื่นๆ (ระบุยี่ห้อใหม่)</option>
                                </select>
                                
                                <div class="input-group" id="brand_other_group" style="display: none;">
                                    <input type="text" class="form-control border-primary" id="brand_other" name="brand_other" placeholder="พิมพ์ยี่ห้อใหม่...">
                                    <button class="btn btn-outline-danger" type="button" id="btn_cancel_brand" title="ยกเลิก"><i class="fas fa-times"></i></button>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="model" class="form-label">รุ่น (Model)</label>
                                <input type="text" class="form-control" id="model" name="model">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="serial_number" class="form-label">หมายเลขซีเรียล (S/N)</label>
                                <input type="text" class="form-control" id="serial_number" name="serial_number">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">หมวดหมู่</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">-- เลือกหมวดหมู่ --</option>
                                    <?php while($row = $cat_result->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['category_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="entry_date" class="form-label">วันที่รับเข้า <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="entry_date" name="entry_date" required>
                            </div>
                        </div>

                        <h5 class="border-bottom pb-2 mt-4 mb-4 text-primary">ข้อมูลการจัดการ</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="campus" class="form-label">วิทยาเขต <span class="text-danger">*</span></label>
                                <select class="form-select" id="campus" name="campus" required>
                                    <option value="ประสานมิตร">ประสานมิตร</option>
                                    <option value="องครักษ์">องครักษ์</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="location_id" class="form-label">สถานที่จัดเก็บ</label>
                                <select class="form-select" id="location_id" name="location_id">
                                    <option value="">-- เลือกสถานที่ --</option>
                                    <?php while($row = $loc_result->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['location_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="responsible_person" class="form-label">ผู้ครอบครอง/รับผิดชอบ</label>
                                <input type="text" class="form-control" id="responsible_person" name="responsible_person">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">สถานะ <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" onchange="toggleStatusDate(this.value)" required>
                                    <option value="พร้อมใช้งาน">พร้อมใช้งาน</option>
                                    <option value="ชำรุด">ชำรุด</option>
                                    <option value="กำลังซ่อม">กำลังซ่อม</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="status_date_div" style="display: none;">
                                <label for="status_updated_at" class="form-label text-danger">วันที่แจ้งชำรุด/ส่งซ่อม <span class="text-danger">*</span></label>
                                <input type="date" class="form-control border-danger" id="status_updated_at" name="status_updated_at">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="remark" class="form-label">หมายเหตุ</label>
                            <textarea class="form-control" id="remark" name="remark" rows="3"></textarea>
                        </div>

                        <hr>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary btn-lg px-5"><i class="fas fa-save me-2"></i> บันทึกข้อมูล</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleStatusDate(status) {
    const dateDiv = document.getElementById('status_date_div');
    const dateInput = document.getElementById('status_updated_at');
    if (status === 'ชำรุด' || status === 'กำลังซ่อม') {
        dateDiv.style.display = 'block';
        dateInput.required = true;
    } else {
        dateDiv.style.display = 'none';
        dateInput.required = false;
        dateInput.value = '';
    }
}

// 🌟 JS จัดการสลับหน้าตายี่ห้อให้ดูสวยและอยู่ในบรรทัดเดิม
const brandSelect = document.getElementById('brand_select');
const brandOtherGroup = document.getElementById('brand_other_group');
const brandOtherInput = document.getElementById('brand_other');
const btnCancelBrand = document.getElementById('btn_cancel_brand');

brandSelect.addEventListener('change', function() {
    if (this.value === 'other') {
        this.style.display = 'none';           // ซ่อน Dropdown
        brandOtherGroup.style.display = 'flex'; // โชว์ช่องพิมพ์ + ปุ่ม X
        brandOtherInput.required = true;
        brandOtherInput.focus();
    }
});

btnCancelBrand.addEventListener('click', function() {
    brandOtherGroup.style.display = 'none'; // ซ่อนช่องพิมพ์
    brandOtherInput.required = false;
    brandOtherInput.value = '';
    brandSelect.style.display = 'block';    // โชว์ Dropdown กลับมา
    brandSelect.value = '';                 // รีเซ็ตค่าเป็นหน้าแรก
});
</script>
</body>
</html>