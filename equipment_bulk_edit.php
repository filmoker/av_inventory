<?php
session_start(); // เปิด Session เพื่อตรวจเช็กสิทธิ์และดึงชื่อคนแก้ไขไปลงประวัติ

// ป้องกันคนแอบดึง URL นี้มาเปิดใช้งานโดยไม่ผ่านประตูเข้าล็อกอิน
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

$ids_str = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : '';
if (empty($ids_str)) { header("Location: equipments.php"); exit; }

$ids_array = explode(',', $ids_str);
$count = count($ids_array);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_update'])) {
    $updates = [];
    
    // จัดการฟิลด์ข้อมูลทั่วไป
    $fields = [
        'equipment_name', 'brand', 'model', 'category_id', 
        'location_id', 'unit_id', 'campus', 'responsible_person', 'entry_date'
    ];

    foreach ($fields as $field) {
        if (!empty($_POST[$field])) {
            $val = $conn->real_escape_string($_POST[$field]);
            $updates[] = "$field = '$val'";
        }
    }

    //  ระบบจัดการอัปโหลดรูปภาพ (แก้ไขหลายรายการ) 
    if (isset($_FILES['equipment_image']) && $_FILES['equipment_image']['error'] == 0) {
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['equipment_image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed_types)) {
            $new_filename = uniqid('eq_bulk_') . '.' . $ext;
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['equipment_image']['tmp_name'], $upload_path)) {
                // (ทางเลือก) ลบรูปภาพเก่าของทุกรายการที่ถูกเลือกเพื่อประหยัดพื้นที่
                $old_images_sql = "SELECT image FROM equipments WHERE id IN ($ids_str) AND image IS NOT NULL AND image != ''";
                $old_images_res = $conn->query($old_images_sql);
                if ($old_images_res) {
                    while($old_img = $old_images_res->fetch_assoc()) {
                        if(file_exists('uploads/' . $old_img['image'])) {
                            unlink('uploads/' . $old_img['image']);
                        }
                    }
                }
                
                // เพิ่มคำสั่งอัปเดตรูปภาพเข้าไปในชุดคำสั่ง
                $updates[] = "image = '" . $conn->real_escape_string($new_filename) . "'";
            }
        }
    }

    if (!empty($updates)) {
        // เพิ่มการบันทึกเวลาอัปเดตข้อมูลเข้าไปด้วย
        $updates[] = "updated_at = NOW()";
        
        // ดึงรหัสครุภัณฑ์ทั้งหมดที่ถูกเลือกออกมาก่อน สั่ง UPDATE เพื่อเอาไปทำ Log
        $code_query = $conn->query("SELECT equipment_code FROM equipments WHERE id IN ($ids_str)");
        $edited_codes = [];
        if ($code_query) {
            while ($c_row = $code_query->fetch_assoc()) {
                $edited_codes[] = $c_row['equipment_code'];
            }
        }
        $codes_string = implode(', ', $edited_codes);

        $sql = "UPDATE equipments SET " . implode(', ', $updates) . " WHERE id IN ($ids_str)";
        if ($conn->query($sql)) {
            
            // 🌟 3. สั่งบันทึกประวัติแก้ไขกลุ่มลงตาราง activity_logs 
            $username = $_SESSION['username'];
            save_log($conn, $username, 'แก้ไขข้อมูล', "แก้ไขข้อมูลแบบกลุ่มจำนวน {$count} รายการ (รหัส: {$codes_string})");

            header("Location: equipments.php?msg=bulk_success");
            exit;
        }
    } else {
        // ถ้าไม่มีการแก้อะไรเลย ให้เด้งกลับไปหน้าแรก
        header("Location: equipments.php");
        exit;
    }
}

$result_categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
$result_locations = $conn->query("SELECT * FROM locations ORDER BY location_name ASC");
$brand_query = "SELECT DISTINCT TRIM(brand) AS brand FROM equipments WHERE brand IS NOT NULL AND TRIM(brand) != '' ORDER BY brand ASC";
$brand_result = $conn->query($brand_query);

// ดึงข้อมูลหน่วยงานเพื่อมาแสดงใน Dropdown และ Sidebar
$units_sidebar = [];
$result_units = @$conn->query("SELECT * FROM units ORDER BY id ASC");
if ($result_units && $result_units->num_rows > 0) {
    while($u = $result_units->fetch_assoc()) {
        $units_sidebar[] = $u;
    }
    $result_units->data_seek(0); // Reset pointer เพื่อเอาไปวนลูปใน Dropdown อีกรอบ
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขหลายรายการพร้อมกัน - ระบบบริหารครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Sarabun', sans-serif; }
        .sidebar { background-color: #1e2b3c; min-height: 100vh; color: #fff; width: 220px; }
        .sidebar a { color: #c2c7d0; text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid #2b3c53; }
        .sidebar a:hover, .sidebar a.active { background-color: #2b3c53; color: #fff; }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .alert-info { border-left: 4px solid #0dcaf0; }
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
                    <a href="#menuPsm" data-bs-toggle="collapse" class="text-white-50 hover-white d-block" style="padding: 10px 20px 10px 45px; font-size: 0.9em;">
                        ประสานมิตร <i class="fas fa-caret-down float-end mt-1"></i>
                    </a>
                    <div class="collapse" id="menuPsm" style="background-color: #0f1722;">
                        <a href="equipments.php?location=ประสานมิตร" class="text-white-50 hover-white d-block py-2" style="padding-left: 55px; font-size: 0.85em;">
                            <i class="fas fa-list me-1"></i> ดูทั้งหมด
                        </a>
                        <?php foreach($units_sidebar as $u): ?>
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
                        <?php foreach($units_sidebar as $u): ?>
                        <a href="equipments.php?location=องครักษ์&unit_id=<?php echo $u['id']; ?>" class="text-white-50 hover-white d-block py-2" style="padding-left: 55px; font-size: 0.75em; line-height: 1.4;">
                            - <?php echo htmlspecialchars($u['unit_name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <a href="units.php"><i class="fas fa-layer-group me-2"></i> จัดการหน่วยงาน</a>
                <a href="locations.php"><i class="fas fa-map-marker-alt me-2"></i> จัดการสถานที่</a>
                <a href="categories.php"><i class="fas fa-tags me-2"></i> จัดการหมวดหมู่</a>
                <a href="report.php"><i class="fas fa-print me-2"></i> พิมพ์รายงานสรุปยอด</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a>
            </nav>
        </div>

        <div class="p-5 flex-grow-1" style="min-width: 0;">
            <div class="row justify-content-center">
                <div class="col-xl-8 col-lg-10">
                    <div class="card">
                        <div class="card-header bg-warning py-3 d-flex justify-content-between align-items-center">
                            <h5> แก้ไขข้อมูลหลายรายการ (จำนวน <?php echo $count; ?> รายการ)</h5>
                        </div>
                        <div class="card-body p-4 p-md-5">
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i> <strong>คำแนะนำ:</strong> กรอกเฉพาะช่องที่ต้องการเปลี่ยนให้เหมือนกันทุกรายการ <u>ช่องที่เว้นว่างไว้จะคงค่าเดิมในฐานข้อมูล</u>
                            </div>

                            <form method="POST" action="equipment_bulk_edit.php" enctype="multipart/form-data">
                                <input type="hidden" name="selected_ids" value="<?php echo htmlspecialchars($ids_str); ?>">
                                <input type="hidden" name="confirm_update" value="1">

                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">ชื่อครุภัณฑ์</label>
                                        <input type="text" name="equipment_name" class="form-control form-control-lg fs-6" placeholder="เว้นว่างไว้หากไม่ต้องการเปลี่ยน">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">แบรนด์/ยี่ห้อ</label>
                                        <select name="brand" class="form-select">
                                            <option value="">-- ไม่เปลี่ยนแปลง --</option>
                                            <?php 
                                            $brand_result->data_seek(0);
                                            while($b = $brand_result->fetch_assoc()): 
                                            ?>
                                                <option value="<?php echo htmlspecialchars($b['brand']); ?>">
                                                    <?php echo htmlspecialchars($b['brand']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">รุ่นสินค้า</label>
                                        <input type="text" name="model" class="form-control" placeholder="เว้นว่างไว้หากไม่ต้องการเปลี่ยน">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">หมวดหมู่</label>
                                        <select name="category_id" class="form-select">
                                            <option value="">-- ไม่เปลี่ยนแปลง --</option>
                                            <?php while($c = $result_categories->fetch_assoc()): ?>
                                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['category_name']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">วิทยาเขต</label>
                                        <select name="campus" class="form-select">
                                            <option value="">-- ไม่เปลี่ยนแปลง --</option>
                                            <option value="ประสานมิตร">ประสานมิตร</option>
                                            <option value="องครักษ์">องครักษ์</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">สถานที่จัดเก็บ</label>
                                        <select name="location_id" class="form-select">
                                            <option value="">-- ไม่เปลี่ยนแปลง --</option>
                                            
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

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">หน่วยงาน</label>
                                        <select name="unit_id" class="form-select">
                                            <option value="">-- ไม่เปลี่ยนแปลง --</option>
                                            <?php foreach($units_sidebar as $u): ?>
                                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['unit_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">ผู้ครอบครอง</label>
                                        <input type="text" name="responsible_person" class="form-control" placeholder="เว้นว่างไว้หากไม่ต้องการเปลี่ยน">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">วันที่รับเข้า</label>
                                        <input type="date" name="entry_date" class="form-control">
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold text-dark">รูปภาพครุภัณฑ์ (ตั้งค่ารูปให้ทุกรายการ)</label>
                                        <input class="form-control border-primary" type="file" name="equipment_image" accept="image/*">
                                        <div class="form-text text-danger"><i class="fas fa-exclamation-triangle"></i> หากอัปโหลดรูปภาพใหม่ รูปภาพนี้จะถูกใช้งานกับครุภัณฑ์ทุกชิ้นที่เลือก</div>
                                    </div>
                                </div>

                                <hr class="my-4 text-muted opacity-25">

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="equipments.php" class="btn btn-secondary px-4 text-white">ยกเลิก</a>
                                    <button type="submit" class="btn btn-warning px-5 fw-bold text-dark">อัปเดตข้อมูลทั้งหมด
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>