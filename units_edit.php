<?php
require_once 'db_connect.php';

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (!isset($_GET['id']) && !isset($_POST['id'])) {
    header("Location: units.php");
    exit();
}

$id = isset($_GET['id']) ? $_GET['id'] : $_POST['id'];
$id = $conn->real_escape_string($id);

// จัดการการอัปเดตข้อมูลเมื่อมีการกดบันทึก
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_unit'])) {
    $unit_name = $conn->real_escape_string(trim($_POST['unit_name']));
    
    if (!empty($unit_name)) {
        $sql_update = "UPDATE units SET unit_name = '$unit_name' WHERE id = $id";
        if ($conn->query($sql_update) === TRUE) {
            echo "<script>alert('แก้ไขชื่อหน่วยงานสำเร็จ!'); window.location.href='units.php';</script>";
            exit();
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    } else {
        $error = "กรุณากรอกชื่อหน่วยงาน";
    }
}

// ดึงข้อมูลหน่วยงานเดิมมาแสดง
$sql = "SELECT * FROM units WHERE id = $id";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    header("Location: units.php");
    exit();
}
$unit = $result->fetch_assoc();

// ดึงข้อมูลหน่วยงานสำหรับ Sidebar
$units_sidebar = [];
$units_res = @$conn->query("SELECT * FROM units ORDER BY id ASC");
if ($units_res && $units_res->num_rows > 0) {
    while($row = $units_res->fetch_assoc()) {
        $units_sidebar[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขหน่วยงาน - ระบบบริหารครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f7f6; }
        .sidebar { background-color: #1e2b3c; min-height: 100vh; color: #fff; width: 220px; }
        .sidebar a { color: #c2c7d0; text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid #2b3c53; }
        .sidebar a:hover, .sidebar a.active { background-color: #2b3c53; color: #fff; }
        .hover-white:hover { color: #ffffff !important; }
        
        /* สไตล์พิเศษสำหรับหน้าแก้ไข */
        .edit-card { border-radius: 12px; }
        .info-alert { border-radius: 8px; border-left: 4px solid #ffc107 !important; background-color: #ffffff; }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="d-flex flex-nowrap">
        
        <div class="sidebar p-0 flex-shrink-0">
            <div class="p-4 text-center border-bottom border-secondary">
                    <h5 class="m-0"><i class="fas fa-boxes"></i> AMDAT</h5>
                </a>
            </div>
            <nav class="mt-3">
                <a href="index.php"><i class="fas fa-home me-2"></i> หน้าแรก</a>
                <a href="#equipmentMenu" data-bs-toggle="collapse" class="text-white-50 hover-white">
                    <i class="fas fa-desktop me-2"></i> รายการครุภัณฑ์
                </a>
                <div class="collapse" id="equipmentMenu" style="background-color: #16202c;">
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
                <a href="locations.php"><i class="fas fa-map-marker-alt me-2"></i> จัดการสถานที่</a>
                <a href="categories.php"><i class="fas fa-tags me-2"></i> จัดการหมวดหมู่</a>
                <a href="units.php"><i class="fas fa-layer-group me-2"></i> จัดการหน่วยงาน</a>
                <a href="report.php"><i class="fas fa-print me-2"></i> พิมพ์รายงานสรุปยอด</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a>
            </nav>
        </div>

        <div class="p-5 flex-grow-1" style="background-color: #f4f7f6; min-width: 0;">
            
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-8 col-md-10">
                    
                    <div class="mb-4">
                        <h4>แก้ไขชื่อหน่วยงาน</h4>
                        <div class="text-muted small">
                            <a href="units.php" class="text-decoration-none">จัดการหน่วยงาน</a> <span class="mx-1">/</span> แก้ไขข้อมูล
                        </div>
                    </div>

                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="card shadow-sm border-0 mb-4 edit-card">
                        <div class="card-body p-4 p-md-5">
                            <form method="POST" action="unit_edit.php">
                                <input type="hidden" name="id" value="<?php echo $unit['id']; ?>">
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold">ชื่อหน่วยงาน <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg fs-6" name="unit_name" 
                                           value="<?php echo htmlspecialchars($unit['unit_name']); ?>" required>
                                    <div class="form-text mt-2 text-muted">
                                        ระบุชื่อหน่วยงานให้ชัดเจนเพื่อความสะดวกในการตรวจสอบ
                                    </div>
                                </div>
                                
                                <hr class="text-muted mb-4 opacity-25">
                                
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="units.php" class="btn btn-secondary px-4 text-white">ยกเลิก</a>
                                    <button type="submit" name="update_unit" class="btn btn-warning px-4 text-dark fw-medium">บันทึกการเปลี่ยนแปลง</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm info-alert">
                        <div class="card-body p-3 text-muted small" style="line-height: 1.6;">
                            <i class="fas fa-info-circle text-warning me-1"></i> <strong>คำแนะนำ:</strong> การแก้ไขชื่อหน่วยงานที่นี่ จะทำให้อุปกรณ์ทุกชิ้นที่สังกัดในหน่วยงานนี้อัปเดตชื่อใหม่ตามโดยอัตโนมัติ
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