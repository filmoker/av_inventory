<?php
require_once 'db_connect.php';

if (!isset($_GET['id']) && !isset($_POST['id'])) {
    header("Location: equipments.php");
    exit();
}

$cat_result = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
$loc_result = $conn->query("SELECT * FROM locations ORDER BY location_name ASC");
$brand_query = "SELECT DISTINCT brand FROM equipments WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC";
$brand_result = $conn->query($brand_query);

$units = [];
$units_result = @$conn->query("SELECT * FROM units ORDER BY id ASC");
if ($units_result && $units_result->num_rows > 0) {
    while($row = $units_result->fetch_assoc()) { $units[] = $row; }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    
    // ดึงข้อมูลเดิมมาเก็บไว้ก่อน (เพื่อเช็ครูปภาพเก่า)
    $old_eq = $conn->query("SELECT image FROM equipments WHERE id = $id")->fetch_assoc();
    
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
    $status = $conn->real_escape_string($_POST['status']);
    
    if ($status == 'ชำรุด' || $status == 'กำลังซ่อม') {
        $status_updated_at = !empty($_POST['status_updated_at']) ? "'".$conn->real_escape_string($_POST['status_updated_at'])."'" : "NULL";
    } else {
        $status_updated_at = "NULL";
    }

    $entry_date = !empty($_POST['entry_date']) ? "'".$conn->real_escape_string($_POST['entry_date'])."'" : "NULL";
    $remark = $conn->real_escape_string(trim($_POST['remark']));

    //  ระบบจัดการอัปโหลดรูปภาพ 
    $image_update_sql = "";
    if (isset($_FILES['equipment_image']) && $_FILES['equipment_image']['error'] == 0) {
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['equipment_image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed_types)) {
            $new_filename = uniqid('eq_') . '.' . $ext;
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['equipment_image']['tmp_name'], $upload_path)) {
                $image_update_sql = ", image = '" . $conn->real_escape_string($new_filename) . "'";
                
                // ลบรูปภาพเก่าทิ้งเพื่อประหยัดพื้นที่ (ถ้ามี)
                if (!empty($old_eq['image']) && file_exists('uploads/' . $old_eq['image'])) {
                    unlink('uploads/' . $old_eq['image']);
                }
            }
        }
    }

    $check_sql = "SELECT id FROM equipments WHERE equipment_code = '$equipment_code' AND id != $id";
    if ($conn->query($check_sql)->num_rows > 0) {
        $error = "รหัสครุภัณฑ์นี้มีอยู่ในระบบแล้ว กรุณาใช้รหัสอื่น";
    } else {
        $sql = "UPDATE equipments SET 
                    equipment_code = '$equipment_code',
                    equipment_name = '$equipment_name',
                    brand = '$brand',
                    model = '$model',
                    serial_number = '$serial_number',
                    category_id = $category_id,
                    unit_id = $unit_id,
                    location_id = $location_id,
                    campus = '$campus',
                    responsible_person = '$responsible_person',
                    status = '$status',
                    status_updated_at = $status_updated_at,
                    entry_date = $entry_date,
                    remark = '$remark'
                    $image_update_sql,
                    updated_at = NOW()
                WHERE id = $id";

        if ($conn->query($sql) === TRUE) {
            header("Location: equipments.php?msg=edit_success");
            exit();
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

$id = isset($_GET['id']) ? $_GET['id'] : $_POST['id'];
$sql = "SELECT * FROM equipments WHERE id = $id";
$result = $conn->query($sql);
if ($result->num_rows == 0) { header("Location: equipments.php"); exit(); }
$eq = $result->fetch_assoc();

$display_code = preg_replace('/^สห\./', '', $eq['equipment_code']);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขครุภัณฑ์ - ระบบบริหารครุภัณฑ์</title>
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
                <h4>แก้ไขข้อมูลครุภัณฑ์</h4>
            </div>

            <?php if(isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="equipment_edit.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $eq['id']; ?>">
                        
                        <h5 class="border-bottom pb-2 mb-4 text-warning">ข้อมูลพื้นฐานและรูปภาพ</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="equipment_code_number" class="form-label">รหัสครุภัณฑ์ <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-dark fw-bold">สห.</span>
                                    <input type="text" class="form-control" id="equipment_code_number" name="equipment_code_number" value="<?php echo htmlspecialchars($display_code); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="equipment_name" class="form-label">ชื่อครุภัณฑ์ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="equipment_name" name="equipment_name" value="<?php echo htmlspecialchars($eq['equipment_name']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="brand_select" class="form-label">ยี่ห้อ (Brand)</label>
                                <select class="form-select" id="brand_select" name="brand_select">
                                    <option value="">-- ไม่ระบุ --</option>
                                    <?php 
                                    $brand_found = false;
                                    $brand_result->data_seek(0);
                                    while($b = $brand_result->fetch_assoc()): 
                                        $selected = ($eq['brand'] == $b['brand']) ? 'selected' : '';
                                        if($selected) $brand_found = true;
                                    ?>
                                        <option value="<?php echo htmlspecialchars($b['brand']); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($b['brand']); ?></option>
                                    <?php endwhile; ?>
                                    <?php if(!empty($eq['brand']) && !$brand_found): ?>
                                        <option value="<?php echo htmlspecialchars($eq['brand']); ?>" selected><?php echo htmlspecialchars($eq['brand']); ?></option>
                                    <?php endif; ?>
                                    <option value="other" class="fw-bold text-primary">+ อื่นๆ (ระบุยี่ห้อใหม่)</option>
                                </select>
                                <div class="input-group" id="brand_other_group" style="display: none;">
                                    <input type="text" class="form-control border-primary" id="brand_other" name="brand_other" placeholder="พิมพ์ยี่ห้อใหม่...">
                                    <button class="btn btn-outline-danger" type="button" id="btn_cancel_brand" title="ยกเลิก"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="model" class="form-label">รุ่น (Model)</label>
                                <input type="text" class="form-control" id="model" name="model" value="<?php echo htmlspecialchars($eq['model']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="serial_number" class="form-label">หมายเลขซีเรียล (S/N)</label>
                                <input type="text" class="form-control" id="serial_number" name="serial_number" value="<?php echo htmlspecialchars($eq['serial_number']); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="category_id" class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">-- เลือกหมวดหมู่ --</option>
                                    <?php while($row = $cat_result->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>" <?php echo ($eq['category_id'] == $row['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($row['category_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="entry_date" class="form-label">วันที่รับเข้า <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="entry_date" name="entry_date" value="<?php echo $eq['entry_date'] ? date('Y-m-d', strtotime($eq['entry_date'])) : ''; ?>" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="equipment_image" class="form-label">เปลี่ยนรูปภาพ (เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน)</label>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if(!empty($eq['image'])): ?>
                                        <img src="uploads/<?php echo $eq['image']; ?>" class="rounded border" style="width: 40px; height: 40px; object-fit: cover;" title="รูปเดิม">
                                    <?php endif; ?>
                                    <input class="form-control" type="file" id="equipment_image" name="equipment_image" accept="image/*">
                                </div>
                            </div>
                        </div>

                        <h5 class="border-bottom pb-2 mt-4 mb-4 text-warning">ข้อมูลการจัดการ</h5>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="campus" class="form-label">วิทยาเขต <span class="text-danger">*</span></label>
                                <select class="form-select" id="campus" name="campus" required>
                                    <option value="ประสานมิตร" <?php echo ($eq['campus'] == 'ประสานมิตร') ? 'selected' : ''; ?>>ประสานมิตร</option>
                                    <option value="องครักษ์" <?php echo ($eq['campus'] == 'องครักษ์') ? 'selected' : ''; ?>>องครักษ์</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="unit_id" class="form-label">หน่วยงาน <span class="text-danger">*</span></label>
                                <select class="form-select" id="unit_id" name="unit_id" required>
                                    <option value="">-- เลือกหน่วยงาน --</option>
                                    <?php foreach($units as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" <?php echo (isset($eq['unit_id']) && $eq['unit_id'] == $u['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['unit_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="location_id" class="form-label">สถานที่จัดเก็บ</label>
                                <select class="form-select" id="location_id" name="location_id">
                                    <option value="">-- เลือกสถานที่ --</option>
                                    <?php while($row = $loc_result->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>" <?php echo ($eq['location_id'] == $row['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($row['location_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="responsible_person" class="form-label">ผู้ครอบครอง</label>
                                <input type="text" class="form-control" id="responsible_person" name="responsible_person" value="<?php echo htmlspecialchars($eq['responsible_person']); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">สถานะ <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" onchange="toggleStatusDate(this.value)" required>
                                    <option value="พร้อมใช้งาน" <?php echo ($eq['status'] == 'พร้อมใช้งาน') ? 'selected' : ''; ?>>พร้อมใช้งาน</option>
                                    <option value="ชำรุด" <?php echo ($eq['status'] == 'ชำรุด') ? 'selected' : ''; ?>>ชำรุด</option>
                                    <option value="กำลังซ่อม" <?php echo ($eq['status'] == 'กำลังซ่อม') ? 'selected' : ''; ?>>กำลังซ่อม</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="status_date_div" style="<?php echo ($eq['status'] == 'ชำรุด' || $eq['status'] == 'กำลังซ่อม') ? 'display:block;' : 'display:none;'; ?>">
                                <label for="status_updated_at" class="form-label text-danger">วันที่แจ้งชำรุด/ส่งซ่อม <span class="text-danger">*</span></label>
                                <input type="date" class="form-control border-danger" id="status_updated_at" name="status_updated_at" value="<?php echo $eq['status_updated_at'] ? date('Y-m-d', strtotime($eq['status_updated_at'])) : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="remark" class="form-label">หมายเหตุ</label>
                            <textarea class="form-control" id="remark" name="remark" rows="3"><?php echo htmlspecialchars($eq['remark']); ?></textarea>
                        </div>

                        <hr>
                        <div class="text-end">
                            <a href="equipments.php" class="btn btn-secondary btn-lg px-5">ยกเลิก</a>
                            <button type="submit" class="btn btn-warning btn-lg px-5 fw-bold text-dark"> บันทึกการเปลี่ยนแปลง</button>
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
        dateDiv.style.display = 'block'; dateInput.required = true;
    } else {
        dateDiv.style.display = 'none'; dateInput.required = false; dateInput.value = '';
    }
}
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
    brandSelect.style.display = 'block';
    if(brandSelect.options.length > 0) { brandSelect.selectedIndex = 0; }
});
</script>
</body>
</html>