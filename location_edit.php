<?php
require_once 'db_connect.php';

// 1. รับ ID ของสถานที่ที่ต้องการแก้ไขจาก URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 2. จัดการเมื่อมีการกดปุ่ม "บันทึกการเปลี่ยนแปลง"
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $location_name = $conn->real_escape_string($_POST['location_name']);

    // คำสั่ง SQL อัปเดตชื่อสถานที่
    $sql_update = "UPDATE locations SET location_name = '$location_name' WHERE id = $id";

    if ($conn->query($sql_update)) {
        // บันทึกสำเร็จ ให้เด้งการแจ้งเตือนและกลับไปหน้าจัดการสถานที่
        echo "<script>
                alert('อัปเดตชื่อสถานที่เรียบร้อยแล้ว!'); 
                window.location.href='locations.php';
              </script>";
        exit();
    } else {
        echo "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// 3. ดึงข้อมูลเดิมจากฐานข้อมูลมาโชว์ในช่อง Input
$sql = "SELECT * FROM locations WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
} else {
    // ถ้าไม่พบ ID นี้ในระบบ ให้ตีกลับไปหน้าหลัก
    echo "<script>alert('ไม่พบข้อมูลสถานที่ที่ต้องการแก้ไข'); window.location.href='locations.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขสถานที่ - ระบบบริหารครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; margin: 0; }
        .main-content { padding: 50px 20px; }
        .card { border-radius: 15px; }
        .btn-warning { color: #000; font-weight: 600; }
    </style>
</head>
<body>

<div class="container main-content">
    <div class="mx-auto" style="max-width: 600px;">
        
        <div class="mb-4">
            <h4>แก้ไขชื่อสถานที่จัดเก็บ</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="locations.php">จัดการสถานที่</a></li>
                    <li class="breadcrumb-item active" aria-current="page">แก้ไขข้อมูล</li>
                </ol>
            </nav>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form action="location_edit.php?id=<?php echo $id; ?>" method="POST">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">ชื่อสถานที่ / ห้องจัดเก็บ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" 
                               name="location_name" 
                               value="<?php echo htmlspecialchars($row['location_name']); ?>" 
                               placeholder="เช่น ห้องประชุม 201 ชั้น 2" required>
                        <div class="form-text text-muted">ระบุชื่อห้องหรืออาคารให้ชัดเจนเพื่อความสะดวกในการตรวจสอบ</div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="locations.php" class="btn btn-secondary px-4"> ยกเลิก
                        </a>
                        <button type="submit" class="btn btn-warning px-4 shadow-sm"> บันทึกการเปลี่ยนแปลง
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <div class="mt-4 p-3 bg-white rounded shadow-sm border-start border-4 border-warning">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1 text-warning"></i> 
                <strong>คำแนะนำ:</strong> การแก้ไขชื่อสถานที่ที่นี่ จะทำให้อุปกรณ์ทุกชิ้นที่ถูกจัดเก็บในห้องนี้อัปเดตที่อยู่ใหม่ตามโดยอัตโนมัติ
            </small>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>