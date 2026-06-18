<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

// --- ส่วนจัดการ PHP ---
// 1. เช็คหน้าปัจจุบันสำหรับทำสีเมนู
$current_page = basename($_SERVER['PHP_SELF']); 
$is_equipment_menu = ($current_page == 'equipments.php' || $current_page == 'equipment_add.php');

// 2. รับค่าจาก URL (ทั้งสถานที่และหน่วยงาน)
$current_loc = isset($_GET['location']) ? $_GET['location'] : '';
$current_unit = isset($_GET['unit_id']) ? $_GET['unit_id'] : '';

$where_filter = "";
if ($current_loc != "") {
    $where_filter .= " AND e.campus = '" . $conn->real_escape_string($current_loc) . "'"; 
}
if ($current_unit != "") {
    $where_filter .= " AND e.unit_id = '" . $conn->real_escape_string($current_unit) . "'"; 
}

$sql_total = "SELECT COUNT(*) as count FROM equipments e WHERE 1=1 $where_filter";
$sql_ready = "SELECT COUNT(*) as count FROM equipments e WHERE e.status = 'พร้อมใช้งาน' $where_filter";
$sql_repair = "SELECT COUNT(*) as count FROM equipments e WHERE e.status = 'กำลังซ่อม' $where_filter";
$sql_broken = "SELECT COUNT(*) as count FROM equipments e WHERE e.status = 'ชำรุด' $where_filter";

// 5. ดึงข้อมูลครุภัณฑ์ที่ชำรุดหรือกำลังซ่อม 
$sql_alerts = "SELECT e.equipment_code, e.equipment_name, e.brand, e.model, e.status, e.status_updated_at, l.location_name 
               FROM equipments e
               LEFT JOIN locations l ON e.location_id = l.id
               WHERE e.status IN ('ชำรุด', 'กำลังซ่อม') $where_filter 
               ORDER BY e.id";
$result_alerts = $conn->query($sql_alerts);

$total = $conn->query($sql_total)->fetch_assoc()['count'];
$ready = $conn->query($sql_ready)->fetch_assoc()['count'];
$repair = $conn->query($sql_repair)->fetch_assoc()['count'];
$broken = $conn->query($sql_broken)->fetch_assoc()['count'];

$cat_labels = [];
$cat_data = [];
$cat_ids = []; 

$sql_cat = "SELECT c.id, c.category_name, COUNT(e.id) as qty 
            FROM equipments e 
            INNER JOIN categories c ON e.category_id = c.id 
            WHERE 1=1 $where_filter 
            GROUP BY c.id ORDER BY qty DESC";
$res_cat = $conn->query($sql_cat);
if ($res_cat) {
    while($row = $res_cat->fetch_assoc()) {
        $cat_ids[] = $row['id']; 
        $cat_labels[] = $row['category_name'];
        $cat_data[] = $row['qty'];
    }
}

$units = [];
$units_result = @$conn->query("SELECT * FROM units ORDER BY id ASC");
if ($units_result && $units_result->num_rows > 0) {
    while($row = $units_result->fetch_assoc()) {
        $units[] = $row;
    }
} else {
    $units = [
        ['id' => 1, 'unit_name' => 'หน่วยโครงสร้างพื้นฐานเทคโนโลยีสารสนเทศดิจิทัล'],
        ['id' => 2, 'unit_name' => 'หน่วยบริการสื่อและมัลติมีเดีย']
    ];
}

$base_qs = "";
if($current_loc != '') $base_qs .= "location=" . urlencode($current_loc) . "&";
if($current_unit != '') $base_qs .= "unit_id=" . urlencode($current_unit) . "&";

$total_link = "equipments.php" . ($base_qs ? "?" . rtrim($base_qs, "&") : "");
$ready_link = "equipments.php?filter=" . urlencode("พร้อมใช้งาน") . ($base_qs ? "&" . rtrim($base_qs, "&") : "");
$repair_link = "equipments.php?filter=" . urlencode("กำลังซ่อม") . ($base_qs ? "&" . rtrim($base_qs, "&") : "");
$broken_link = "equipments.php?filter=" . urlencode("ชำรุด") . ($base_qs ? "&" . rtrim($base_qs, "&") : "");
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
        .chart-wrapper { position: relative; height: 260px; width: 100%; }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="d-flex flex-nowrap">
        
        <div class="sidebar p-0 flex-shrink-0">
            <div class="p-4 text-center border-bottom border-secondary">
                <h5 class="m-0"><i class="fas fa-boxes"></i> AMDAT </h5>
            </div>
            <nav class="mt-3">
                <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    <i class="fas fa-home me-2"></i> หน้าแรก
                </a>
                
                <a href="#equipmentMenu" data-bs-toggle="collapse" class="<?php echo $is_equipment_menu ? 'text-white' : ''; ?>">
                    <i class="fas fa-desktop me-2"></i> รายการครุภัณฑ์
                </a>
                <div class="collapse <?php echo $is_equipment_menu ? 'show' : ''; ?>" id="equipmentMenu" style="background-color: #16202c;">
                    
                    <a href="#menuPsm" data-bs-toggle="collapse" class="text-white-50 hover-white d-block" style="padding: 10px 20px 10px 45px; font-size: 0.9em;">
                        ประสานมิตร <i class="fas fa-caret-down float-end mt-1"></i>
                    </a>
                    <div class="collapse" id="menuPsm" style="background-color: #0f1722;">
                        <a href="equipments.php?location=ประสานมิตร" class="text-white-50 hover-white d-block py-2" style="padding-left: 55px; font-size: 0.85em;">
                            <i class="fas fa-list me-1"></i> ดูทั้งหมด 
                        </a>
                        <?php foreach($units as $u): ?>
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
                        <?php foreach($units as $u): ?>
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

        <div class="p-4 flex-grow-1" style="min-width: 0; overflow-x: auto;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Asset Management System of Digital Academic Technology v.1.2.0</h4>
                
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle text-black border-0 shadow-sm rounded-pill px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i> ยินดีต้อนรับ, 
                        <?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'ผู้ใช้งาน'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li>
                            <a class="dropdown-item py-2" href="activity_logs.php">
                                <i class="fas fa-history text-secondary me-2"></i> ประวัติการใช้งาน
                            </a>
                        </li>
                    </ul>
                </div>
                
            </div>

            <div class="mb-4">
                <div class="d-flex flex-wrap gap-3 align-items-center bg-white p-3 rounded shadow-sm border border-light">
                    
                    <a href="index.php" class="btn <?php echo ($current_loc == '' && $current_unit == '') ? 'btn-primary' : 'btn-outline-primary'; ?> fw-bold px-4"> ภาพรวมทั้งหมด
                    </a>
                    
                    <div class="vr mx-1 d-none d-md-block"></div> <div class="btn-group">
                        <a href="index.php?location=ประสานมิตร" class="btn <?php echo ($current_loc == 'ประสานมิตร') ? 'btn-danger' : 'btn-outline-danger'; ?> fw-bold px-4">ประสานมิตร</a>
                        <a href="index.php?location=องครักษ์" class="btn <?php echo ($current_loc == 'องครักษ์') ? 'btn-secondary' : 'btn-outline-secondary'; ?> fw-bold px-4">องครักษ์</a>
                    </div>

                    <div class="vr mx-1 d-none d-md-block"></div> <div class="btn-group flex-wrap">
                        <?php foreach($units as $u): ?>
                            <a href="index.php?unit_id=<?php echo $u['id']; ?>" class="btn <?php echo ($current_unit == $u['id']) ? 'btn-info text-white' : 'btn-outline-info'; ?> fw-semibold">
                                <?php echo htmlspecialchars($u['unit_name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card card-stat bg-primary-dark p-3 shadow-sm" onclick="window.location='<?php echo $total_link; ?>'">
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
                    <div class="card card-stat bg-success-dark p-3 shadow-sm" onclick="window.location='<?php echo $ready_link; ?>'">
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
                    <div class="card card-stat bg-warning-dark p-3 shadow-sm" onclick="window.location='<?php echo $repair_link; ?>'">
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
                    <div class="card card-stat bg-danger-dark p-3 shadow-sm" onclick="window.location='<?php echo $broken_link; ?>'">
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

            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white py-3 border-0">
                            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-chart-pie me-2 text-primary"></i>สัดส่วนสถานะครุภัณฑ์</h6>
                        </div>
                        <div class="card-body pt-0">
                            <div class="chart-wrapper">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white py-3 border-0">
                            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-chart-bar me-2 text-success"></i>จำนวนครุภัณฑ์แยกตามหมวดหมู่</h6>
                        </div>
                        <div class="card-body pt-0">
                            <div class="chart-wrapper">
                                <canvas id="categoryChart"></canvas>
                            </div>
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
                                                <td><small class="text-secondary fw-semibold"><i class="fas fa-map-marker-alt me-1"></i><?php echo $row['location_name'] ?: '-'; ?></small></td>
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
                                <a href="equipment_add.php" class="btn btn-primary text-start p-3 fw-bold"><i class="fas fa-plus-circle me-2"></i> ขึ้นทะเบียนใหม่</a>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// เตรียมตัวแปรลิงก์ของหน้ารายการแยกตามสถานะ
const statusLinks = [
    '<?php echo $ready_link; ?>',
    '<?php echo $repair_link; ?>',
    '<?php echo $broken_link; ?>'
];

// กราฟที่ 1: Doughnut Chart (สัดส่วนสถานะ)
const ctxStatus = document.getElementById('statusChart').getContext('2d');
new Chart(ctxStatus, {
    type: 'doughnut',
    data: {
        labels: ['พร้อมใช้งาน', 'กำลังซ่อม', 'ชำรุด'],
        datasets: [{
            data: [<?php echo $ready; ?>, <?php echo $repair; ?>, <?php echo $broken; ?>],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        },
        onHover: (event, chartElement) => {
            event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
        },
        onClick: (event, elements) => {
            if (elements.length > 0) {
                const index = elements[0].index;
                window.location.href = statusLinks[index];
            }
        }
    }
});

// ดึงอาเรย์รหัส ID หมวดหมู่จากฝั่ง PHP มาไว้ที่ฝั่ง JavaScript
const catIds = <?php echo json_encode($cat_ids); ?>;

// กราฟที่ 2: Bar Chart (แยกตามหมวดหมู่)
const ctxCat = document.getElementById('categoryChart').getContext('2d');
new Chart(ctxCat, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($cat_labels); ?>,
        datasets: [{
            label: 'จำนวน (รายการ)',
            data: <?php echo json_encode($cat_data); ?>,
            backgroundColor: 'rgba(23, 162, 184, 0.8)',
            borderColor: '#17a2b8',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        },
        onHover: (event, chartElement) => {
            event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
        },
        onClick: (event, elements) => {
            if (elements.length > 0) {
                const index = elements[0].index;
                const clickedCatId = catIds[index];
                
                let url = 'equipments.php?category_id=' + clickedCatId;
                <?php if($current_loc != '') echo "url += '&location=" . urlencode($current_loc) . "';"; ?>
                <?php if($current_unit != '') echo "url += '&unit_id=" . urlencode($current_unit) . "';"; ?>
                
                window.location.href = url;
            }
        }
    }
});
</script>

</body>
</html>