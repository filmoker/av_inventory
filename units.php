<?php
require_once 'db_connect.php';

// จัดการการเพิ่มข้อมูลใหม่ (เมื่อมีการ Submit ฟอร์มเพิ่มหน่วยงาน)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_unit'])) {
    $unit_name = $conn->real_escape_string(trim($_POST['unit_name']));
    
    // เช็คว่ามีชื่อนี้ในระบบหรือยังเพื่อป้องกันการเพิ่มซ้ำ
    $check_sql = "SELECT id FROM units WHERE unit_name = '$unit_name'";
    if ($conn->query($check_sql)->num_rows > 0) {
        echo "<script>alert('มีหน่วยงานนี้ในระบบแล้ว!');</script>";
    } else {
        $sql_insert = "INSERT INTO units (unit_name) VALUES ('$unit_name')";
        if ($conn->query($sql_insert) === TRUE) {
            echo "<script>alert('เพิ่มหน่วยงานใหม่สำเร็จ!'); window.location.href='units.php';</script>";
        } else {
            echo "<script>alert('เกิดข้อผิดพลาด: " . $conn->error . "');</script>";
        }
    }
}

// ดึงข้อมูลหน่วยงานทั้งหมดมาแสดงในตาราง
$sql = "SELECT * FROM units ORDER BY id ASC";
$result = $conn->query($sql);

// เตรียมข้อมูลหน่วยงานสำหรับแสดงใน Sidebar 
$units_sidebar = [];
$units_result = @$conn->query("SELECT * FROM units ORDER BY id ASC");
if ($units_result && $units_result->num_rows > 0) {
    while($row = $units_result->fetch_assoc()) {
        $units_sidebar[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหน่วยงาน - ระบบบริหารครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        
        .sidebar { background-color: #1e2b3c; min-height: 100vh; color: #fff; width: 220px; }
        .sidebar a { color: #c2c7d0; text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid #2b3c53; }
        .sidebar a:hover, .sidebar a.active { background-color: #2b3c53; color: #fff; }
        .hover-white:hover { color: #ffffff !important; }

        div.dataTables_wrapper div.row:first-child { padding: 15px 20px 10px 20px; margin: 0; }
        div.dataTables_wrapper div.row:last-child { padding: 15px 20px 15px 20px; margin: 0; }
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

        <div class="p-4 bg-light flex-grow-1" style="min-width: 0; overflow-x: auto;">
            <div class="mb-4">
                <h4>จัดการหน่วยงาน</h4>
            </div>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white pb-0">
                            <h5 class="card-title text-dark"> เพิ่มหน่วยงานใหม่</h5>
                        </div>
                        <div class="card-body">
                            <form action="units.php" method="POST">
                                <div class="mb-3">
                                    <label class="form-label">ชื่อหน่วยงาน <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="unit_name" required placeholder="เช่น หน่วยงานบริหารงานทั่วไป">
                                </div>
                                <button type="submit" name="add_unit" class="btn btn-primary w-100"> บันทึกข้อมูล</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="unitTable" class="table table-striped table-hover align-middle mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="15%">ลำดับ</th>
                                            <th>ชื่อหน่วยงาน</th>
                                            <th width="25%" class="text-center">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['id']; ?></td>
                                                <td class="text-dark"><?php echo htmlspecialchars($row['unit_name']); ?></td>
                                                <td class="text-center text-nowrap">
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <a href="units_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning px-3" title="แก้ไข">
                                                            <i class="fas fa-edit"></i> แก้ไข
                                                        </a>
                                                        
                                                        <a href="units_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger px-3" onclick="return confirm('คำเตือน: ยืนยันการลบหรือไม่?')" title="ลบ">
                                                            <i class="fas fa-trash"></i> ลบ
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php endif; ?> 
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#unitTable').DataTable({ 
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json"
            },
            "order": [], 
            "columnDefs": [
                { "orderable": false, "targets": [2] } 
            ]
        });
    });
</script>

</body>
</html>