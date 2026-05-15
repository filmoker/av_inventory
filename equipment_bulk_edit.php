<?php
require_once 'db_connect.php';

$ids_str = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : '';
if (empty($ids_str)) { header("Location: equipments.php"); exit; }

$ids_array = explode(',', $ids_str);
$count = count($ids_array);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_update'])) {
    $updates = [];
    $fields = [
        'equipment_name', 'brand', 'model', 'category_id', 
        'location_id', 'campus', 'responsible_person', 'entry_date'
    ];

    foreach ($fields as $field) {
        if (!empty($_POST[$field])) {
            $val = $conn->real_escape_string($_POST[$field]);
            $updates[] = "$field = '$val'";
        }
    }

    if (!empty($updates)) {
        $sql = "UPDATE equipments SET " . implode(', ', $updates) . " WHERE id IN ($ids_str)";
        if ($conn->query($sql)) {
            header("Location: equipments.php?msg=bulk_success");
            exit;
        }
    }
}

$result_categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
$result_locations = $conn->query("SELECT * FROM locations ORDER BY location_name ASC");

// ดึงข้อมูลยี่ห้อ (Brand) ทั้งหมดที่มีในระบบ และเรียง A-Z
$brand_query = "SELECT DISTINCT brand FROM equipments WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC";
$brand_result = $conn->query($brand_query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขหลายรายการพร้อมกัน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Sarabun', sans-serif; }
        .card { border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .alert-info { border-left: 5px solid #0dcaf0; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning py-3">
                    <h5 class="mb-0 fw-bold">แก้ไขข้อมูลหลายรายการ (จำนวน <?php echo $count; ?> รายการ)</h5>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i> <strong>คำแนะนำ:</strong> กรอกเฉพาะช่องที่ต้องการเปลี่ยนให้เหมือนกันทุกรายการ <u>ช่องที่เว้นว่างไว้จะคงค่าเดิมในฐานข้อมูล</u>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="selected_ids" value="<?php echo $ids_str; ?>">
                        <input type="hidden" name="confirm_update" value="1">

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">ชื่อครุภัณฑ์</label>
                                <input type="text" name="equipment_name" class="form-control" placeholder="เว้นว่างไว้หากไม่ต้องการเปลี่ยน">
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
                                <label class="form-label fw-bold">สถานที่จัดเก็บ</label>
                                <select name="location_id" class="form-select">
                                    <option value="">-- ไม่เปลี่ยนแปลง --</option>
                                    <?php while($l = $result_locations->fetch_assoc()): ?>
                                        <option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['location_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">วิทยาเขต</label>
                                <select name="campus" class="form-select">
                                    <option value="">-- ไม่เปลี่ยนแปลง --</option>
                                    <option value="ประสานมิตร">มศว ประสานมิตร</option>
                                    <option value="องครักษ์">มศว องครักษ์</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">ผู้ครอบครอง</label>
                                <input type="text" name="responsible_person" class="form-control" placeholder="เว้นว่างไว้หากไม่ต้องการเปลี่ยน">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">วันที่รับเข้า</label>
                                <input type="date" name="entry_date" class="form-control">
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                            <a href="equipments.php" class="btn btn-light px-4">ยกเลิก</a>
                            <button type="submit" class="btn btn-warning px-4 fw-bold text-dark"> อัปเดตข้อมูลทั้งหมด
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>