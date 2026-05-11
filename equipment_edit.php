<?php
require_once 'db_connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipment_code = $_POST['equipment_code'];
    $equipment_name = $_POST['equipment_name'];
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $serial_number = $_POST['serial_number'];
    $category_id = $_POST['category_id'];
    $location_id = $_POST['location_id'];
    $campus = $_POST['campus'];
    $responsible_person = $_POST['responsible_person'];
    $status = $_POST['status'];
    $entry_date = $_POST['entry_date'];
    $remark = $_POST['remark'];

    // จัดการวันที่อัปเดตสถานะ
    if ($status == 'พร้อมใช้งาน') {
        $status_updated_at_sql = "NULL"; 
    } else {
        $date_val = !empty($_POST['status_updated_at']) ? $_POST['status_updated_at'] : date('Y-m-d');
        $time_val = date('H:i:s');
        $status_updated_at_sql = "'" . $conn->real_escape_string($date_val . ' ' . $time_val) . "'";
    }

    // 🌟 เพิ่มการบันทึก updated_at = NOW() ตรงนี้ครับ
    $sql_update = "UPDATE equipments SET 
                    equipment_code = '$equipment_code',
                    equipment_name = '$equipment_name',
                    brand = '$brand',
                    model = '$model',
                    serial_number = '$serial_number',
                    category_id = '$category_id',
                    location_id = '$location_id',
                    campus = '$campus',
                    responsible_person = '$responsible_person',
                    status = '$status',
                    status_updated_at = $status_updated_at_sql,
                    updated_at = NOW(), 
                    entry_date = '$entry_date',
                    remark = '$remark'
                   WHERE id = $id";

    if ($conn->query($sql_update)) {
        header("Location: equipments.php?msg=success");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
} 

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM equipments WHERE id = $id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        echo "<script>alert('ไม่พบข้อมูลที่ต้องการแก้ไข'); window.location.href='equipments.php';</script>";
        exit();
    }
} else {
    header("Location: equipments.php");
    exit();
}

$result_categories = $conn->query("SELECT * FROM categories");
$result_locations = $conn->query("SELECT * FROM locations");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลครุภัณฑ์ - ระบบบริหารครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
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
                <a href="equipments.php" class="active"> <i class="fas fa-desktop me-2"></i> รายการครุภัณฑ์</a>    
                <a href="locations.php"><i class="fas fa-map-marker-alt me-2"></i> จัดการสถานที่</a>
                <a href="categories.php"><i class="fas fa-tags me-2"></i> จัดการหมวดหมู่</a>
                <a href="report.php"><i class="fas fa-print me-2"></i> พิมพ์รายงานสรุปยอด</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a>
            </nav>
        </div>

        <div class="p-4 bg-light flex-grow-1" style="min-width: 0; overflow-x: auto;">
            <div class="mx-auto" style="max-width: 800px;">
                <div class="mb-4 mt-3">
                    <h4>แก้ไขข้อมูลครุภัณฑ์</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="equipments.php">รายการครุภัณฑ์</a></li>
                            <li class="breadcrumb-item active" aria-current="page">แก้ไขข้อมูล</li>
                        </ol>
                    </nav>
                </div>

                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-body p-4">
                        <form action="equipment_edit.php?id=<?php echo $id; ?>" method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-dark fw-bold">รหัสครุภัณฑ์ <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="equipment_code" value="<?php echo htmlspecialchars($row['equipment_code']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-dark fw-bold">หมายเลขซีเรียล (S/N)</label>
                                    <input type="text" class="form-control" name="serial_number" value="<?php echo htmlspecialchars($row['serial_number']); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-dark fw-bold">ชื่อครุภัณฑ์ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="equipment_name" value="<?php echo htmlspecialchars($row['equipment_name']); ?>" required>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-dark fw-bold">แบรนด์/ยี่ห้อ</label>
                                    <input type="text" class="form-control" name="brand" value="<?php echo htmlspecialchars($row['brand']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-dark fw-bold">รุ่นสินค้า</label>
                                    <input type="text" class="form-control" name="model" value="<?php echo htmlspecialchars($row['model']); ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-dark fw-bold">หมวดหมู่ <span class="text-danger">*</span></label>
                                    <select class="form-select" name="category_id" required>
                                        <?php while($cat = $result_categories->fetch_assoc()): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo ($row['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                <?php echo $cat['category_name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-dark fw-bold">สถานที่จัดเก็บ <span class="text-danger">*</span></label>
                                    <select class="form-select" name="location_id" required>
                                        <?php while($loc = $result_locations->fetch_assoc()): ?>
                                            <option value="<?php echo $loc['id']; ?>" <?php echo ($row['location_id'] == $loc['id']) ? 'selected' : ''; ?>>
                                                <?php echo $loc['location_name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-dark fw-bold">วิทยาเขต <span class="text-danger">*</span></label>
                                    <select class="form-select" name="campus" required>
                                        <option value="ประสานมิตร" <?php echo ($row['campus'] == 'ประสานมิตร') ? 'selected' : ''; ?>>มศว ประสานมิตร</option>
                                        <option value="องครักษ์" <?php echo ($row['campus'] == 'องครักษ์') ? 'selected' : ''; ?>>มศว องครักษ์</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-dark fw-bold">ผู้ครอบครอง</label>
                                    <input type="text" class="form-control" name="responsible_person" value="<?php echo htmlspecialchars($row['responsible_person']); ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-dark fw-bold">สถานะ <span class="text-danger">*</span></label>
                                    <select class="form-select" name="status" id="statusSelect" required>
                                        <option value="พร้อมใช้งาน" <?php echo ($row['status'] == 'พร้อมใช้งาน') ? 'selected' : ''; ?>>พร้อมใช้งาน</option>
                                        <option value="ชำรุด" <?php echo ($row['status'] == 'ชำรุด') ? 'selected' : ''; ?>>ชำรุด</option>
                                        <option value="กำลังซ่อม" <?php echo ($row['status'] == 'กำลังซ่อม') ? 'selected' : ''; ?>>กำลังซ่อม</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-dark fw-bold">วันที่รับเข้า <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="entry_date" value="<?php echo $row['entry_date']; ?>" required>
                                </div>
                            </div>
                            
                            <?php 
                                $only_date = "";
                                if (!empty($row['status_updated_at']) && strtotime($row['status_updated_at']) > 0) {
                                    $only_date = date('Y-m-d', strtotime($row['status_updated_at']));
                                }
                            ?>
                            <div class="row mb-3" id="dateGroup" style="display: <?php echo ($row['status'] == 'ชำรุด' || $row['status'] == 'กำลังซ่อม') ? 'flex' : 'none'; ?>;">
                                <div class="col-md-6"></div>
                                <div class="col-md-6">
                                    <label class="form-label text-danger fw-bold">วันที่แจ้งชำรุด/ส่งซ่อม <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control border-danger" name="status_updated_at" id="dateInput" value="<?php echo $only_date; ?>">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-dark fw-bold">หมายเหตุ</label>
                                <textarea class="form-control" name="remark" rows="2"><?php echo htmlspecialchars($row['remark']); ?></textarea>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-end gap-2">
                                <a href="equipments.php" class="btn btn-secondary px-4">ยกเลิก</a>
                                <button type="submit" class="btn btn-warning px-4 fw-bold"> บันทึกการเปลี่ยนแปลง</button>
                            </div>
                        </form> 
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('statusSelect');
    const dateGroup = document.getElementById('dateGroup');
    const dateInput = document.getElementById('dateInput');

    function toggleDateField() {
        if (statusSelect.value === 'ชำรุด' || statusSelect.value === 'กำลังซ่อม') {
            dateGroup.style.display = 'flex';
            dateInput.required = true;
            if (!dateInput.value) {
                dateInput.value = new Date().toISOString().split('T')[0];
            }
        } else {
            dateGroup.style.display = 'none';
            dateInput.required = false;
        }
    }
    statusSelect.addEventListener('change', toggleDateField);
    toggleDateField();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>