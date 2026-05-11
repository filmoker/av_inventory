<?php

require_once 'db_connect.php';

// จัดการการเพิ่มข้อมูลใหม่ (เมื่อมีการ Submit ฟอร์มเพิ่มหมวดหมู่)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $category_name = $_POST['category_name'];
    $sql_insert = "INSERT INTO categories (category_name) VALUES ('$category_name')";
    if ($conn->query($sql_insert) === TRUE) {
        echo "<script>alert('เพิ่มหมวดหมู่ใหม่สำเร็จ!'); window.location.href='categories.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาด: " . $conn->error . "');</script>";
    }
}

// ดึงข้อมูลหมวดหมู่ทั้งหมดมาแสดง
$sql = "SELECT * FROM categories ORDER BY id ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหมวดหมู่ - ระบบบริหารครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        
        /*  ล็อกความกว้าง Sidebar ไว้ที่ 220px */
        .sidebar { background-color: #1e2b3c; min-height: 100vh; color: #fff; width: 220px; }
        .sidebar a { color: #c2c7d0; text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid #2b3c53; }
        .sidebar a:hover, .sidebar a.active { background-color: #2b3c53; color: #fff; }

        /* ปรับระยะห่าง DataTables ให้สวยงาม ไม่ชิดขอบ */
        div.dataTables_wrapper div.row:first-child { padding: 15px 20px 10px 20px; margin: 0; }
        div.dataTables_wrapper div.row:last-child { padding: 15px 20px 15px 20px; margin: 0; }
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
                <a href="equipments.php"><i class="fas fa-desktop me-2"></i> รายการครุภัณฑ์</a>
                <a href="locations.php"><i class="fas fa-map-marker-alt me-2"></i> จัดการสถานที่</a>
                <a href="categories.php" class="active"><i class="fas fa-tags me-2"></i> จัดการหมวดหมู่</a>
                <a href="report.php"><i class="fas fa-print me-2"></i> พิมพ์รายงานสรุปยอด</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a>
            </nav>
        </div>

        <div class="p-4 bg-light flex-grow-1" style="min-width: 0; overflow-x: auto;">
            <div class="mb-4">
                <h4> จัดการหมวดหมู่ครุภัณฑ์</h4>
            </div>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white pb-0">
                            <h5 class="card-title">เพิ่มหมวดหมู่ใหม่</h5>
                        </div>
                        <div class="card-body">
                            <form action="categories.php" method="POST">
                                <div class="mb-3">
                                    <label class="form-label">ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="category_name" required placeholder="เช่น อุปกรณ์ไอที">
                                </div>
                                <button type="submit" name="add_category" class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i> เพิ่มข้อมูล</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="categoryTable" class="table table-striped table-hover align-middle mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="15%">ID</th>
                                            <th>ชื่อหมวดหมู่</th>   
                                            <th width="25%" class="text-center">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php while($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['id']; ?></td>
                                                <td><?php echo $row['category_name']; ?></td>
                                                <td class="text-center text-nowrap">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="category_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning px-3" title="แก้ไข">
                                                        <i class="fas fa-edit"></i> แก้ไข
                                                    </a>
                                                    
                                                    <a href="category_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger px-3" onclick="return confirm('ยืนยันการลบหมวดหมู่นี้?')" title="ลบ">
                                                        <i class="fas fa-trash"></i> ลบ
                                                    </a>
                                                </div>
                                            </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php endif; ?> </tbody>
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
        $('#categoryTable').DataTable({ 
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