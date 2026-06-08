<?php
session_start(); // เปิด Session เพื่อให้ระบบจำได้ว่าใครกำลังเพิ่มข้อมูล

// ป้องกันคนก๊อปปี้ URL มาเข้าโดยไม่ได้ล็อกอิน
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

$cat_result = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
$loc_result = $conn->query("SELECT * FROM locations ORDER BY location_name ASC");
$brand_query = "SELECT DISTINCT TRIM(brand) AS brand FROM equipments WHERE brand IS NOT NULL AND TRIM(brand) != '' ORDER BY brand ASC";
$brand_result = $conn->query($brand_query);

$units = [];
$units_result = @$conn->query("SELECT * FROM units ORDER BY id ASC");
if ($units_result && $units_result->num_rows > 0) {
    while($row = $units_result->fetch_assoc()) { $units[] = $row; }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipment_code_num = trim($_POST['equipment_code_number']);
    $equipment_code = $conn->real_escape_string('สห.' . $equipment_code_num);
    $equipment_name = $conn->real_escape_string(trim($_POST['equipment_name']));
    
    $brand_val = $_POST['brand_select'];
    if ($brand_val === 'other') { $brand_val = trim($_POST['brand_other']); }
    $brand = $conn->real_escape_string($brand_val);
    
    $model = $conn->real_escape_string(trim($_POST['model']));
    $serial_number = $conn->real_escape_string(trim($_POST['serial_number']));
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : "NULL";
    $location_id = !empty($_POST['location_id']) ? $_POST['location_id'] : "NULL";
    $unit_id = !empty($_POST['unit_id']) ? $_POST['unit_id'] : "NULL";
    $campus = $conn->real_escape_string($_POST['campus']);
    $responsible_person = $conn->real_escape_string(trim($_POST['responsible_person']));
    
    $status = 'พร้อมใช้งาน';
    $status_updated_at = "NULL";
    
    $entry_date = !empty($_POST['entry_date']) ? "'".$conn->real_escape_string($_POST['entry_date'])."'" : "NULL";
    $remark = $conn->real_escape_string(trim($_POST['remark']));

    $image_val = "NULL";
    if (isset($_FILES['equipment_image']) && $_FILES['equipment_image']['error'] == 0) {
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['equipment_image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed_types)) {
            // ตั้งชื่อไฟล์ใหม่ด้วย uniqid เพื่อไม่ให้ชื่อซ้ำกัน
            $new_filename = uniqid('eq_') . '.' . $ext;
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['equipment_image']['tmp_name'], $upload_path)) {
                $image_val = "'" . $conn->real_escape_string($new_filename) . "'";
            }
        }
    }

    $check_sql = "SELECT id FROM equipments WHERE equipment_code = '$equipment_code'";
    if ($conn->query($check_sql)->num_rows > 0) {
        $error = "รหัสครุภัณฑ์นี้มีอยู่ในระบบแล้ว กรุณาใช้รหัสอื่น";
    } else {
        $sql = "INSERT INTO equipments (
                    equipment_code, equipment_name, brand, model, serial_number, 
                    category_id, unit_id, location_id, campus, responsible_person, status, 
                    status_updated_at, entry_date, remark, image, created_at, updated_at
                ) VALUES (
                    '$equipment_code', '$equipment_name', '$brand', '$model', '$serial_number', 
                    $category_id, $unit_id, $location_id, '$campus', '$responsible_person', '$status', 
                    $status_updated_at, $entry_date, '$remark', $image_val, NOW(), NOW()
                )";

        if ($conn->query($sql) === TRUE) {
            //  เพิ่มระบบบันทึกประวัติ (Log) ตรงนี้ เมื่อเพิ่มข้อมูลสำเร็จ
            $username = $_SESSION['username'];
            save_log($conn, $username, 'เพิ่มข้อมูล', "เพิ่มครุภัณฑ์ รหัส: {$equipment_code} ({$equipment_name})");

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
    <title>เพิ่มครุภัณฑ์ใหม่ - ระบบบริหารครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .sidebar { background-color: #1e2b3c; min-height: 100vh; color: #fff; width: 220px; }
        .sidebar a { color: #c2c7d0; text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid #2b3c53; }
        .sidebar a:hover, .sidebar a.active { background-color: #2b3c53; color: #fff; }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="d-flex flex-nowrap">
        
        <div class="sidebar p-0 flex-shrink-0">
            <div class="p-4 text-center border-bottom border-secondary">
                    <h5 class="m-0"><i class="fas fa-boxes"></i> ระบบครุภัณฑ์</h5>
                </a>
            </div>
            <nav class="mt-3">
                <a href="index.php"><i class="fas fa-home me-2"></i> หน้าแรก</a>
                
                <a href="#equipmentMenu" data-bs-toggle="collapse" class="text-white fw-bold active" aria-expanded="true">
                    <i class="fas fa-desktop me-2"></i> รายการครุภัณฑ์
                </a>
                
                <div class="collapse show" id="equipmentMenu" style="background-color: #16202c;">
                    <a href="#menuPsm" data-bs-toggle="collapse" class="text-white-50 hover-white d-block" style="padding: 10px 20px 10px 45px; font-size: 0.9em;">
                        ประสานมิตร <i class="fas fa-caret-down float-end mt-1"></i>
                    </a>
                    <div class="collapse" id="menuPsm" style="background-color: #0f1722;">
                        <a href="equipments.php?location=ประสานมิตร" class="text-white-50 hover-white d-block py-2" style="padding-left: 55px; font-size: 0.85em;">
                            <i class="fas fa-list me-1"></i> ดูทั้งหมด
                        </a>
                        <?php foreach($units as $u): ?>
                        <a href="equipments.php?location=ประสานมิตร&unit_id=<?php echo $u['id']; ?>" class="text-white-50 hover-white d-block py-2" style="padding-left: 55px; font-size: 0.75em; line-height: 1.4;">
                            - <?php echo htmlspecialchars($u['unit_name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <a href="#menuOkr" data-bs-toggle="collapse" class="text-white-50 hover-white d-block mt-1" style="padding: 10px 20px 10px 45px; font-size: 0.9em;">
                        องครักษ์ <i class="fas fa-caret-down float-end mt-1"></i>
                    </a>
                    <div class="collapse" id="menuOkr" style="background-color: #0f1722;">
                        <a href="equipments.php?location=องครักษ์" class="text-white-50 hover-white d-block py-2" style="padding-left: 55px; font-size: 0.85em;">
                            <i class="fas fa-list me-1"></i> ดูทั้งหมด
                        </a>
                        <?php foreach($units as $u): ?>
                        <a href="equipments.php?location=องครักษ์&unit_id=<?php echo $u['id']; ?>" class="text-white-50 hover-white d-block py-2" style="padding-left: 55px; font-size: 0.75em; line-height: 1.4;">
                            - <?php echo htmlspecialchars($u['unit_name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <a href="locations.php"><i class="fas fa-map-marker-alt me-2"></i> จัดการสถานที่</a>
                <a href="categories.php"><i class="fas fa-tags me-2"></i> จัดการหมวดหมู่</a>
                <a href="units.php"><i class="fas fa-layer-group me-2"></i> จัดการหน่วยงาน</a>
                <a href="report.php"><i class="fas fa-print me-2"></i> พิมพ์รายงานสรุปยอด</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a>
            </nav>
        </div>

        <div class="col-md-10 p-4 bg-light flex-grow-1" style="min-width: 0;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-plus-circle text-primary me-2"></i> ขึ้นทะเบียนครุภัณฑ์ใหม่</h4>
            </div>

            <?php if(isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="equipment_add.php" method="POST" enctype="multipart/form-data">
                        
                        <h5 class="border-bottom pb-2 mb-4 text-primary">ข้อมูลพื้นฐานและรูปภาพ</h5>
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
                            <div class="col-md-4 mb-3">
                                <label for="category_id" class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">-- เลือกหมวดหมู่ --</option>
                                    <?php while($row = $cat_result->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['category_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="entry_date" class="form-label">วันที่รับเข้า <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="entry_date" name="entry_date" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="equipment_image" class="form-label">รูปภาพครุภัณฑ์</label>
                                <input class="form-control" type="file" id="equipment_image" name="equipment_image" accept="image/*">
                            </div>
                        </div>

                        <h5 class="border-bottom pb-2 mt-4 mb-4 text-primary">ข้อมูลการจัดการ</h5>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="campus" class="form-label">วิทยาเขต <span class="text-danger">*</span></label>
                                <select class="form-select" id="campus" name="campus" required>
                                    <option value="" disabled selected>-- เลือกวิทยาเขต --</option>
                                    <option value="ประสานมิตร">ประสานมิตร</option>
                                    <option value="องครักษ์">องครักษ์</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="unit_id" class="form-label">หน่วยงาน <span class="text-danger">*</span></label>
                                <select class="form-select" id="unit_id" name="unit_id" required>
                                    <option value="">-- เลือกหน่วยงาน --</option>
                                    <?php foreach($units as $u): ?>
                                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['unit_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="location_id" class="form-label">สถานที่จัดเก็บ</label>
                                <select class="form-select" id="location_id" name="location_id">
                                    <option value="">-- เลือกสถานที่ --</option>
                                    
                                    <optgroup label="ประสานมิตร">
                                        <?php 
                                        $sql_psm = "SELECT * FROM locations WHERE campus = 'ประสานมิตร' ORDER BY location_name ASC";
                                        $res_psm = $conn->query($sql_psm);
                                        while($loc = $res_psm->fetch_assoc()) {
                                            echo "<option value='{$loc['id']}'>{$loc['location_name']}</option>";
                                        }
                                        ?>
                                    </optgroup>
                                    
                                    <optgroup label="องครักษ์">
                                        <?php 
                                        $sql_okr = "SELECT * FROM locations WHERE campus = 'องครักษ์' ORDER BY location_name ASC";
                                        $res_okr = $conn->query($sql_okr);
                                        while($loc = $res_okr->fetch_assoc()) {
                                            echo "<option value='{$loc['id']}'>{$loc['location_name']}</option>";
                                        }
                                        ?>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="responsible_person" class="form-label">ผู้ครอบครอง</label>
                                <input type="text" class="form-control" id="responsible_person" name="responsible_person">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">สถานะเริ่มต้น (ระบบกำหนดอัตโนมัติ)</label>
                                <input type="text" class="form-control bg-light text-success fw-bold" value="พร้อมใช้งาน" readonly>
                                <input type="hidden" name="status" value="พร้อมใช้งาน">
                                <div class="form-text mt-1 text-muted">
                                    <i class="fas fa-info-circle"></i> ครุภัณฑ์ที่ขึ้นทะเบียนใหม่จะพร้อมใช้งานเสมอ หากชำรุดในภายหลังให้ใช้เมนู "เพิ่มบันทึกการซ่อม" ในหน้ารายละเอียด
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="remark" class="form-label">หมายเหตุ</label>
                            <textarea class="form-control" id="remark" name="remark" rows="3"></textarea>
                        </div>

                        <hr>
                        <div class="text-end">
                            <a href="equipments.php" class="btn btn-secondary btn-lg px-5">ยกเลิก</a>
                            <button type="submit" class="btn btn-primary btn-lg px-5"> บันทึกข้อมูล</button>
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
const brandSelect = document.getElementById('brand_select');
const brandOtherGroup = document.getElementById('brand_other_group');
const brandOtherInput = document.getElementById('brand_other');
const btnCancelBrand = document.getElementById('btn_cancel_brand');

brandSelect.addEventListener('change', function() {
    if (this.value === 'other') {
        this.style.display = 'none'; brandOtherGroup.style.display = 'flex'; brandOtherInput.required = true; brandOtherInput.focus();
    }
});
btnCancelBrand.addEventListener('click', function() {
    brandOtherGroup.style.display = 'none'; brandOtherInput.required = false; brandOtherInput.value = '';
    brandSelect.style.display = 'block'; brandSelect.value = '';                
});
</script>
</body>
</html>