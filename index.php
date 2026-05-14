<?php
// ดึงไฟล์เชื่อมต่อฐานข้อมูลมาใช้งาน
require_once 'db_connect.php';

// --- ส่วนจัดการ PHP ---
// 1. เช็คหน้าปัจจุบันสำหรับทำสีเมนู
$current_page = basename($_SERVER['PHP_SELF']); 
$is_equipment_menu = ($current_page == 'equipments.php' || $current_page == 'equipment_add.php');

// 2. รับค่าสถานที่จาก URL (ถ้าไม่มีให้ว่างไว้ แปลว่าดูทั้งหมด)
$current_loc = isset($_GET['location']) ? $_GET['location'] : '';

// 3. สร้างเงื่อนไข SQL สำหรับกรองข้อมูลตามสถานที่
$where_location = "";
if ($current_loc != "") {
    $current_loc_esc = $conn->real_escape_string($current_loc);
    $where_location = " AND e.campus = '$current_loc_esc'"; 
}

// 4. คำสั่ง SQL สำหรับนับจำนวนครุภัณฑ์
$sql_total = "SELECT COUNT(*) as count FROM equipments e WHERE 1=1 $where_location";
$sql_ready = "SELECT COUNT(*) as count FROM equipments e WHERE e.status = 'พร้อมใช้งาน' $where_location";
$sql_repair = "SELECT COUNT(*) as count FROM equipments e WHERE e.status = 'กำลังซ่อม' $where_location";
$sql_broken = "SELECT COUNT(*) as count FROM equipments e WHERE e.status = 'ชำรุด' $where_location";

// 5. ดึงข้อมูลครุภัณฑ์ที่ชำรุดหรือกำลังซ่อม 
$sql_alerts = "SELECT e.equipment_code, e.equipment_name, e.brand, e.model, e.status, e.status_updated_at, l.location_name 
               FROM equipments e
               LEFT JOIN locations l ON e.location_id = l.id
               WHERE e.status IN ('ชำรุด', 'กำลังซ่อม') $where_location 
               ORDER BY e.id";
$result_alerts = $conn->query($sql_alerts);

// ดึงข้อมูลออกมาเก็บในตัวแปร
$total = $conn->query($sql_total)->fetch_assoc()['count'];
$ready = $conn->query($sql_ready)->fetch_assoc()['count'];
$repair = $conn->query($sql_repair)->fetch_assoc()['count'];
$broken = $conn->query($sql_broken)->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ระบบบริหารครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        
        /* 🌟 ปรับ Sidebar ให้กว้าง 220px ตายตัว */
        .sidebar { background-color: #1e2b3c; min-height: 100vh; color: #fff; width: 220px; }
        .sidebar a { color: #c2c7d0; text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid #2b3c53; }
        .sidebar a:hover, .sidebar a.active { background-color: #2b3c53; color: #fff; }
        .hover-white:hover { color: #ffffff !important; }
        
        .card-stat { border: none; border-radius: 10px; color: white; cursor: pointer; transition: 0.3s; }
        .card-stat:hover { opacity: 0.85; transform: translateY(-3px); }
        .bg-primary-dark { background-color: #17a2b8; }
        .bg-success-dark { background-color: #28a745; }
        .bg-warning-dark { background-color: #ffc107; color: #333; }
        .bg-danger-dark { background-color: #dc3545; }
        .icon-lg { font-size: 3rem; opacity: 0.4; }
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
                <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    <i class="fas fa-home me-2"></i> หน้าแรก
                </a>
                <a href="#equipmentMenu" data-bs-toggle="collapse" class="<?php echo $is_equipment_menu ? 'text-white' : ''; ?>">
                    <i class="fas fa-desktop me-2"></i> รายการครุภัณฑ์
                </a>
                <div class="collapse <?php echo $is_equipment_menu ? 'show' : ''; ?>" id="equipmentMenu" style="background-color: #16202c;">
                    <a href="equipments.php?location=ประสานมิตร" style="padding-left: 45px; font-size: 0.9em;">มศว ประสานมิตร</a>
                    <a href="equipments.php?location=องครักษ์" style="padding-left: 45px; font-size: 0.9em;">มศว องครักษ์</a>
                </div>
                <a href="locations.php"><i class="fas fa-map-marker-alt me-2"></i> จัดการสถานที่</a>
                <a href="categories.php"><i class="fas fa-tags me-2"></i> จัดการหมวดหมู่</a>
                <a href="report.php"><i class="fas fa-print me-2"></i> พิมพ์รายงานสรุปยอด</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a>
            </nav>
        </div>

        <div class="p-4 flex-grow-1" style="min-width: 0; overflow-x: auto;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>ระบบบริหารตรวจสอบครุภัณฑ์โสตทัศนูปกรณ์ v.1.0.0</h4>
                <span><i class="fas fa-user-circle"></i> ยินดีต้อนรับ, แอดมิน</span>
            </div>

            <div class="mb-4">
                <div class="btn-group shadow-sm">
                    <a href="index.php" class="btn <?php echo ($current_loc == '') ? 'btn-primary' : 'btn-outline-primary bg-white'; ?>">ภาพรวมทั้งหมด</a>
                    <a href="index.php?location=ประสานมิตร" class="btn <?php echo ($current_loc == 'ประสานมิตร') ? 'btn-primary' : 'btn-outline-primary bg-white'; ?>">ประสานมิตร</a>
                    <a href="index.php?location=องครักษ์" class="btn <?php echo ($current_loc == 'องครักษ์') ? 'btn-primary' : 'btn-outline-primary bg-white'; ?>">องครักษ์</a>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card card-stat bg-primary-dark p-3 shadow-sm" 
                         onclick="window.location='equipments.php<?php echo ($current_loc != '') ? '?location='.$current_loc : ''; ?>'">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><?php echo $total; ?></h3>
                                <small>ครุภัณฑ์ทั้งหมด (รายการ)</small>
                            </div>
                            <i class="fas fa-database icon-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-stat bg-success-dark p-3 shadow-sm" 
                        onclick="window.location='equipments.php?filter=พร้อมใช้งาน<?php echo ($current_loc != '') ? '&location='.$current_loc : ''; ?>'">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><?php echo $ready; ?></h3>
                                <small>พร้อมใช้งาน (รายการ)</small>
                            </div>
                            <i class="fas fa-check-circle icon-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-stat bg-warning-dark p-3 shadow-sm" 
                        onclick="window.location='equipments.php?filter=กำลังซ่อม<?php echo ($current_loc != '') ? '&location='.$current_loc : ''; ?>'">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><?php echo $repair; ?></h3>
                                <small>กำลังส่งซ่อม (รายการ)</small>
                            </div>
                            <i class="fas fa-wrench icon-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-stat bg-danger-dark p-3 shadow-sm" 
                        onclick="window.location='equipments.php?filter=ชำรุด<?php echo ($current_loc != '') ? '&location='.$current_loc : ''; ?>'">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><?php echo $broken; ?></h3>
                                <small>ชำรุด/จำหน่ายออก (รายการ)</small>
                            </div>
                            <i class="fas fa-times-circle icon-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white"><h5 class="text-danger m-0"> รายการที่ต้องติดตาม (ชำรุด/ส่งซ่อม)</h5></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>รหัสครุภัณฑ์</th>
                                            <th>ชื่อ/รุ่นอุปกรณ์</th>
                                            <th>สถานที่จัดเก็บ</th> 
                                            <th class="text-center">สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result_alerts->num_rows > 0): ?>
                                            <?php while($row = $result_alerts->fetch_assoc()): ?>
                                            <tr>
                                                <td><small class="fw-bold"><?php echo $row['equipment_code']; ?></small></td>
                                                <td>
                                                    <div><?php echo $row['equipment_name']; ?></div>
                                                    <small class="text-muted"><?php echo ($row['brand'] ?: '-') . " / " . ($row['model'] ?: '-'); ?></small>
                                                </td>
                                                <td><small class="text-secondary fw-semibold"><?php echo $row['location_name'] ?: '-'; ?></small></td>
                                                <td class="text-center">
                                                    <span class="badge <?php echo ($row['status'] == 'ชำรุด') ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                                        <?php echo $row['status']; ?>
                                                    </span>
                                                    <?php if (!empty($row['status_updated_at'])): ?>
                                                    <div class="mt-1 small text-muted" style="font-size: 0.75rem; font-weight: 500;">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php 
                                                            $s_date = new DateTime($row['status_updated_at']);
                                                            $months_th = ["","ม.ค.","ก.พ.","มี.ค.","เม.ย.","พ.ค.","มิ.ย.","ก.ค.","ส.ค.","ก.ย.","ต.ค.","พ.ย.","ธ.ค."];
                                                            
                                                            $thai_day = $s_date->format('j');
                                                            $thai_month = $months_th[(int)$s_date->format('n')];
                                                            $eng_year = $s_date->format('Y'); 
                                                            
                                                            echo $thai_day . " " . $thai_month . " " . $eng_year;
                                                        ?>
                                                    </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center text-success py-4">ไม่มีรายการที่ต้องติดตามในขณะนี้</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white"><h5>รายการเข้าถึงด่วน</h5></div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="equipment_add.php" class="btn btn-primary text-start p-3"><i class="fas fa-plus-circle me-2"></i> ขึ้นทะเบียนใหม่</a>
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