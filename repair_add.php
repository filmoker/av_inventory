<?php
require_once 'db_connect.php';

// ตรวจสอบว่ามีส่ง ID ครุภัณฑ์มาหรือไม่
if (!isset($_GET['eq_id']) && !isset($_POST['equipment_id'])) {
    header("Location: equipments.php");
    exit();
}

$eq_id = isset($_GET['eq_id']) ? $_GET['eq_id'] : $_POST['equipment_id'];
$eq_id = $conn->real_escape_string($eq_id);

// ดึงข้อมูลครุภัณฑ์มาโชว์บนหัวฟอร์ม
$eq_sql = "SELECT equipment_code, equipment_name FROM equipments WHERE id = $eq_id";
$eq_result = $conn->query($eq_sql);
if ($eq_result->num_rows == 0) {
    header("Location: equipments.php");
    exit();
}
$eq_data = $eq_result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_repair'])) {
    $equipment_id = $conn->real_escape_string($_POST['equipment_id']);
    $repair_detail = $conn->real_escape_string(trim($_POST['repair_detail']));
    $reported_date = $conn->real_escape_string($_POST['reported_date']);
    $completed_date = !empty($_POST['completed_date']) ? "'" . $conn->real_escape_string($_POST['completed_date']) . "'" : "NULL";
    $repair_cost = !empty($_POST['repair_cost']) ? $conn->real_escape_string($_POST['repair_cost']) : "NULL";
    $repair_status = $conn->real_escape_string($_POST['repair_status']);
    $technician_name = $conn->real_escape_string(trim($_POST['technician_name']));

    // บันทึกลงตารางประวัติการซ่อม
    $sql_insert = "INSERT INTO repair_history (equipment_id, repair_detail, reported_date, completed_date, repair_cost, repair_status, technician_name) 
                   VALUES ($equipment_id, '$repair_detail', '$reported_date', $completed_date, $repair_cost, '$repair_status', '$technician_name')";

    if ($conn->query($sql_insert) === TRUE) {
        $new_eq_status = ($repair_status == 'ซ่อมเสร็จแล้ว') ? 'พร้อมใช้งาน' : 'กำลังซ่อม';
        $update_eq = "UPDATE equipments SET status = '$new_eq_status', status_updated_at = '$reported_date' WHERE id = $equipment_id";
        $conn->query($update_eq);

        echo "<script>alert('บันทึกประวัติการซ่อมสำเร็จ!'); window.location.href='equipment_view.php?id=$equipment_id';</script>";
        exit();
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มบันทึกการซ่อม - ระบบบริหารครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f7f6; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-warning py-3">
                    <h5>เพิ่มบันทึกการแจ้งซ่อม</h5>
                </div>
                <div class="card-body p-4">
                    
                    <div class="alert alert-light border shadow-sm mb-4">
                        <small class="text-muted d-block">กำลังทำรายการของครุภัณฑ์:</small>
                        <span class="fw-bold fs-5 text-primary"><?php echo htmlspecialchars($eq_data['equipment_name']); ?></span> 
                        <span class="badge bg-dark ms-2"><?php echo htmlspecialchars($eq_data['equipment_code']); ?></span>
                    </div>

                    <?php if(isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

                    <form method="POST" action="repair_add.php">
                        <input type="hidden" name="equipment_id" value="<?php echo $eq_id; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">วันที่แจ้งซ่อม <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="reported_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">อาการชำรุด / รายละเอียดการซ่อม <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="repair_detail" rows="3" required placeholder="เช่น หน้าจอเปิดไม่ติด, เปลี่ยนอะไหล่..."></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">สถานะการซ่อม <span class="text-danger">*</span></label>
                                <select class="form-select" name="repair_status" required>
                                    <option value="กำลังซ่อม">กำลังซ่อม / รอคิว</option>
                                    <option value="ซ่อมเสร็จแล้ว">ซ่อมเสร็จแล้ว</option>
                                    <option value="ส่งซ่อมภายนอก">ส่งซ่อมภายนอกร้าน</option>
                                    <option value="ซ่อมไม่ได้/รอจำหน่าย">ซ่อมไม่ได้ / รอจำหน่าย</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">วันที่ซ่อมเสร็จ (ถ้ามี)</label>
                                <input type="date" class="form-control" name="completed_date">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">ชื่อช่างผู้ซ่อม / ผู้รับผิดชอบ</label>
                                <input type="text" class="form-control" name="technician_name" placeholder="ระบุชื่อช่าง หรือชื่อร้าน">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">ค่าใช้จ่าย (บาท)</label>
                                <input type="number" step="0.01" class="form-control" name="repair_cost" placeholder="0.00">
                            </div>
                        </div>

                        <hr class="text-muted opacity-25">

                        <div class="text-end mt-3">
                            <a href="equipment_view.php?id=<?php echo $eq_id; ?>" class="btn btn-secondary px-5 py-2">กลับไปหน้ารายละเอียด</a>
                            <button type="submit" name="save_repair" class="btn btn-warning fw-bold text-dark px-5 py-2"> บันทึกข้อมูล
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>