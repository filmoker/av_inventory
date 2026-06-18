<?php
require_once 'db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: equipments.php");
    exit();
}

$id = $conn->real_escape_string($_GET['id']);

// 1. ดึงข้อมูลครุภัณฑ์แบบละเอียด
$sql = "SELECT e.*, c.category_name, l.location_name, u.unit_name 
        FROM equipments e
        LEFT JOIN categories c ON e.category_id = c.id
        LEFT JOIN locations l ON e.location_id = l.id
        LEFT JOIN units u ON e.unit_id = u.id
        WHERE e.id = $id";
$result = $conn->query($sql);
$eq = $result->fetch_assoc();

if (!$eq) { header("Location: equipments.php"); exit(); }

// 2. ดึงประวัติการซ่อม
$sql_repair = "SELECT * FROM repair_history WHERE equipment_id = $id ORDER BY reported_date DESC";
$result_repair = $conn->query($sql_repair);

// เตรียมข้อมูลสำหรับ Sidebar
$units_sidebar = [];
$units_res = @$conn->query("SELECT * FROM units ORDER BY id ASC");
if ($units_res) {
    while($row = $units_res->fetch_assoc()) { $units_sidebar[] = $row; }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายละเอียดครุภัณฑ์ - <?php echo $eq['equipment_code']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f7f6; }
        .sidebar { background-color: #1e2b3c; min-height: 100vh; color: #fff; width: 220px; }
        .sidebar a { color: #c2c7d0; text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid #2b3c53; }
        .sidebar a:hover, .sidebar a.active { background-color: #2b3c53; color: #fff; }
        .info-label { color: #6c757d; font-weight: 500; width: 150px; display: inline-block; }
        .equipment-img { width: 100%; max-height: 400px; object-fit: contain; border-radius: 10px; background: #eee; }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="d-flex flex-nowrap">
        <div class="sidebar p-0 flex-shrink-0">
            <div class="p-4 text-center border-bottom border-secondary">
                    <h5 class="m-0"><i class="fas fa-boxes"></i> AMDAT </h5>
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

        <div class="p-4 flex-grow-1" style="min-width: 0;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4> รายละเอียดครุภัณฑ์</h4>
                <a href="equipments.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> กลับหน้ารายการ</a>
            </div>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <?php if($eq['image']): ?>
                                <img src="uploads/<?php echo $eq['image']; ?>" class="equipment-img shadow-sm mb-3">
                            <?php else: ?>
                                <div class="p-5 bg-light rounded mb-3 text-muted">
                                    <i class="fas fa-image fa-4x mb-2"></i><br>ไม่มีรูปภาพ
                                </div>
                            <?php endif; ?>
                            <h5 class="fw-bold text-dark"><?php echo $eq['equipment_name']; ?></h5>
                            <span class="badge bg-primary px-3"><?php echo $eq['equipment_code']; ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3"><h5 class="m-0 text-primary">ข้อมูลพื้นฐาน</h5></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6"><span class="info-label">ยี่ห้อ:</span> <?php echo $eq['brand'] ?: '-'; ?></div>
                                <div class="col-md-6"><span class="info-label">รุ่น:</span> <?php echo $eq['model'] ?: '-'; ?></div>
                                <div class="col-md-6"><span class="info-label">Serial Number:</span> <?php echo $eq['serial_number'] ?: '-'; ?></div>
                                <div class="col-md-6"><span class="info-label">หมวดหมู่:</span> <?php echo $eq['category_name'] ?: '-'; ?></div>
                                <div class="col-md-6"><span class="info-label">วิทยาเขต:</span> <?php echo $eq['campus']; ?></div>
                                <div class="col-md-6"><span class="info-label">หน่วยงาน:</span> <?php echo $eq['unit_name'] ?: '-'; ?></div>
                                <div class="col-md-6"><span class="info-label">สถานที่จัดเก็บ:</span> <?php echo $eq['location_name'] ?: '-'; ?></div>
                                <div class="col-md-6"><span class="info-label">ผู้รับผิดชอบ:</span> <?php echo $eq['responsible_person'] ?: '-'; ?></div>
                                <div class="col-md-6"><span class="info-label">วันที่รับเข้า:</span> <?php echo date('d/m/Y', strtotime($eq['entry_date'])); ?></div>
                                <div class="col-md-6"><span class="info-label">สถานะปัจจุบัน:</span> 
                                    <span class="badge <?php echo ($eq['status'] == 'พร้อมใช้งาน') ? 'bg-success' : 'bg-danger'; ?>"><?php echo $eq['status']; ?></span>
                                </div>
                                
                                <div class="col-md-6">
                                    <span class="info-label"><i class="fas fa-plus-circle text-primary me-1"></i> วันที่เพิ่มข้อมูล:</span> 
                                    <?php echo !empty($eq['created_at']) ? date('d/m/Y H:i', strtotime($eq['created_at'])) . ' น.' : '-'; ?>
                                </div>
                                <div class="col-md-6">
                                    <span class="info-label"><i class="fas fa-user-edit text-secondary me-1"></i> แก้ไขล่าสุด:</span> 
                                    <?php echo !empty($eq['updated_at']) ? date('d/m/Y H:i', strtotime($eq['updated_at'])) . ' น.' : '-'; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between">
                            <h5 class="m-0 text-warning"> ประวัติการซ่อม</h5>
                            <a href="repair_add.php?eq_id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-warning text-dark fw-bold"> เพิ่มบันทึกการซ่อม
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>วันที่แจ้ง</th>
                                        <th>รายละเอียดการซ่อม</th>
                                        <th>ช่างผู้ซ่อม</th>
                                        <th>วันที่เสร็จ</th>
                                        <th>สถานะ</th>
                                        <th>จัดการ</th> 
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($result_repair->num_rows > 0): ?>
                                        <?php while($rep = $result_repair->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($rep['reported_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($rep['repair_detail']); ?></td>
                                            <td><?php echo htmlspecialchars($rep['technician_name']) ?: '-'; ?></td>
                                            <td><?php echo $rep['completed_date'] ? date('d/m/Y', strtotime($rep['completed_date'])) : '-'; ?></td>
                                            <td>
                                                <span class="badge <?php echo ($rep['repair_status'] == 'ซ่อมเสร็จแล้ว') ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $rep['repair_status']; ?>
                                                </span>
                                            </td>
                                            
                                            <td>
                                                <a href="repair_edit.php?id=<?php echo $rep['id']; ?>&eq_id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-outline-warning" title="อัปเดตสถานะ/แก้ไข">
                                                    <i class="fas fa-edit"></i> แก้ไข
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted">ยังไม่เคยมีประวัติการซ่อม</td></tr>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>