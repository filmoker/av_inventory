<?php
require_once 'db_connect.php';

if (!isset($_GET['id']) && !isset($_POST['id'])) {
    header("Location: equipments.php");
    exit();
}

// ดึงข้อมูลหมวดหมู่
$cat_result = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");

// ดึงข้อมูลสถานที่
$loc_result = $conn->query("SELECT * FROM locations ORDER BY location_name ASC");

// 🌟 ดึงข้อมูลยี่ห้อ (Brand) ทั้งหมดที่มีในระบบ (ไม่ซ้ำกัน) และเรียง A-Z
$brand_query = "SELECT DISTINCT brand FROM equipments WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC";
$brand_result = $conn->query($brand_query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    
    // 🌟 รับค่าตัวเลขรหัส แล้วเอา 'สห.' ไปต่อข้างหน้า
    $equipment_code_num = trim($_POST['equipment_code_number']);
    $equipment_code = $conn->real_escape_string('สห.' . $equipment_code_num);
    
    $equipment_name = $conn->real_escape_string(trim($_POST['equipment_name']));
    
    // 🌟 เช็คว่าเลือกยี่ห้อที่มีอยู่ หรือระบุยี่ห้อใหม่
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
    
    // จัดการวันที่ส่งซ่อม
    if ($status == 'ชำรุด' || $status == 'กำลังซ่อม') {
        $status_updated_at = !empty($_POST['status_updated_at']) ? "'".$conn->real_escape_string($_POST['status_updated_at'])."'" : "NULL";
    } else {
        $status_updated_at = "NULL";
    }

    $entry_date = !empty($_POST['entry_date']) ? "'".$conn->real_escape_string($_POST['entry_date'])."'" : "NULL";
    $remark = $conn->real_escape_string(trim($_POST['remark']));

    // ตรวจสอบรหัสซ้ำ (ยกเว้นตัวเอง)
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
                    location_id = $location_id,
                    campus = '$campus',
                    responsible_person = '$responsible_person',
                    status = '$status',
                    status_updated_at = $status_updated_at,
                    entry_date = $entry_date,
                    remark = '$remark',
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
if ($result->num_rows == 0) {
    header("Location: equipments.php");
    exit();
}
$eq = $result->fetch_assoc();

// 🌟 ตัดคำว่า "สห." ออก เพื่อเอาไปแสดงในช่องกรอก (ถ้ามี)
$display_code = preg_replace('/^สห\./', '', $eq['equipment_code']);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขครุภัณฑ์ - ระบบบริหารครุภัณฑ์</title>
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
                <h4><i class="fas fa-edit text-warning me-2"></i> แก้ไขข้อมูลครุภัณฑ์</h4>
                <a href="equipments.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> กลับหน้ารายการ</a>
            </div>

            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="equipment_edit.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo $eq['id']; ?>">
                        
                        <h5 class="border-bottom pb-2 mb-4 text-warning">ข้อมูลพื้นฐาน</h5>
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
                                        $selected = '';
                                        if ($eq['brand'] == $b['brand']) {
                                            $selected = 'selected';
                                            $brand_found = true;
                                        }
                                    ?>
                                        <option value="<?php echo htmlspecialchars($b['brand']); ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($b['brand']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                    
                                    <?php if(!empty($eq['brand']) && !$brand_found): ?>
                                        <option value="<?php echo htmlspecialchars($eq['brand']); ?>" selected>
                                            <?php echo htmlspecialchars($eq['brand']); ?>
                                        </option>
                                    <?php endif; ?>
                                    
                                    <option value="other" class="fw-bold text-primary">+ อื่นๆ (ระบุยี่ห้อใหม่)</option>
                                </select>
                                <input type="text" class="form-control mt-2 border-primary" id="brand_other" name="brand_other" style="display: none;" placeholder="พิมพ์ยี่ห้อใหม่ที่นี่...">
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
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">หมวดหมู่</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">-- เลือกหมวดหมู่ --</option>
                                    <?php while($row = $cat_result->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>" <?php echo ($eq['category_id'] == $row['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($row['category_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="entry_date" class="form-label">วันที่รับเข้า <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="entry_date" name="entry_date" value="<?php echo $eq['entry_date'] ? date('Y-m-d', strtotime($eq['entry_date'])) : ''; ?>" required>
                            </div>
                        </div>

                        <h5 class="border-bottom pb-2 mt-4 mb-4 text-warning">ข้อมูลการจัดการ</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="campus" class="form-label">วิทยาเขต <span class="text-danger">*</span></label>
                                <select class="form-select" id="campus" name="campus" required>
                                    <option value="ประสานมิตร" <?php echo ($eq['campus'] == 'ประสานมิตร') ? 'selected' : ''; ?>>ประสานมิตร</option>
                                    <option value="องครักษ์" <?php echo ($eq['campus'] == 'องครักษ์') ? 'selected' : ''; ?>>องครักษ์</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="location_id" class="form-label">สถานที่จัดเก็บ</label>
                                <select class="form-select" id="location_id" name="location_id">
                                    <option value="">-- เลือกสถานที่ --</option>
                                    <?php while($row = $loc_result->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>" <?php echo ($eq['location_id'] == $row['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($row['location_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="responsible_person" class="form-label">ผู้ครอบครอง/รับผิดชอบ</label>
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
                            <button type="submit" class="btn btn-warning btn-lg px-5 fw-bold text-dark"><i class="fas fa-save me-2"></i> บันทึกการเปลี่ยนแปลง</button>
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

// 🌟 ควบคุมการซ่อน/โชว์ช่องพิมพ์ยี่ห้อใหม่
document.getElementById('brand_select').addEventListener('change', function() {
    var otherInput = document.getElementById('brand_other');
    if (this.value === 'other') {
        otherInput.style.display = 'block';
        otherInput.required = true;
        otherInput.focus();
    } else {
        otherInput.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
    }
});
</script>
</body>
</html>