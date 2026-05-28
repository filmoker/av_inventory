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

// --- 1. รับค่าการกรองจาก URL ---
$filter_campus = isset($_GET['campus']) ? $_GET['campus'] : '';
$filter_cat = isset($_GET['category']) ? $_GET['category'] : '';
$filter_loc = isset($_GET['location']) ? $_GET['location'] : '';
$filter_unit = isset($_GET['unit_id']) ? $_GET['unit_id'] : ''; 

// สร้างเงื่อนไข WHERE สำหรับ SQL
$where_clauses = [];
if ($filter_campus != '') {
    $where_clauses[] = "e.campus = '" . $conn->real_escape_string($filter_campus) . "'";
}
if ($filter_cat != '') {
    $where_clauses[] = "e.category_id = '" . $conn->real_escape_string($filter_cat) . "'";
}
if ($filter_loc != '') {
    $where_clauses[] = "e.location_id = '" . $conn->real_escape_string($filter_loc) . "'";
}
if ($filter_unit != '') {
    $where_clauses[] = "e.unit_id = '" . $conn->real_escape_string($filter_unit) . "'";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- 2. ดึงข้อมูลสรุปยอด (Stats) ---
// นับจำนวนแยกตามสถานะ
$sql_status = "SELECT 
    COUNT(CASE WHEN status = 'พร้อมใช้งาน' THEN 1 END) as ready,
    COUNT(CASE WHEN status = 'กำลังซ่อม' THEN 1 END) as repair,
    COUNT(CASE WHEN status = 'ชำรุด' THEN 1 END) as broken,
    COUNT(id) as total
    FROM equipments e $where_sql";
$res_status = $conn->query($sql_status)->fetch_assoc();

// สรุปยอดตามหมวดหมู่
$sql_cat = "SELECT c.category_name, COUNT(e.id) as total_qty 
            FROM categories c 
            LEFT JOIN equipments e ON c.id = e.category_id 
            $where_sql
            GROUP BY c.id ORDER BY total_qty DESC";
$res_cat = $conn->query($sql_cat);

// สรุปยอดตามหน่วยงาน 
$sql_unit_summary = "SELECT u.unit_name, COUNT(e.id) as total_qty 
                    FROM units u 
                    LEFT JOIN equipments e ON u.id = e.unit_id 
                    $where_sql
                    GROUP BY u.id ORDER BY total_qty DESC";
$res_unit_summary = $conn->query($sql_unit_summary);

// รายการครุภัณฑ์ทั้งหมดตามฟิลเตอร์
$sql_all = "SELECT e.*, c.category_name, l.location_name, u.unit_name 
            FROM equipments e
            LEFT JOIN categories c ON e.category_id = c.id
            LEFT JOIN locations l ON e.location_id = l.id
            LEFT JOIN units u ON e.unit_id = u.id
            $where_sql
            ORDER BY e.campus ASC, e.unit_id ASC, e.id DESC";
$res_all = $conn->query($sql_all);

// ดึงข้อมูล Master ข้อมูลสำหรับสร้าง Dropdown
$all_cats = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
$all_locs = $conn->query("SELECT * FROM locations ORDER BY location_name ASC");
$all_units = $conn->query("SELECT * FROM units ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานสรุปยอดครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; font-size: 13px; background-color: #f8f9fa; }
        .report-paper { background: white; padding: 40px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-bottom: 30px; border-radius: 8px; }
        .report-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .stat-box { border: 1px solid #dee2e6; border-radius: 10px; padding: 15px; text-align: center; }
        
        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .report-paper { box-shadow: none; padding: 0; margin: 0; }
            .container { width: 100% !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="card mb-4 no-print border-0 shadow-sm">
        <div class="card-header bg-primary text-white fw-bold">ตัวกรองรายงาน</div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">วิทยาเขต</label>
                    <select name="campus" class="form-select form-select-sm">
                        <option value="">-- ทั้งหมด --</option>
                        <option value="ประสานมิตร" <?php if($filter_campus=='ประสานมิตร') echo 'selected'; ?>> ประสานมิตร</option>
                        <option value="องครักษ์" <?php if($filter_campus=='องครักษ์') echo 'selected'; ?>> องครักษ์</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">หน่วยงาน</label>
                    <select name="unit_id" class="form-select form-select-sm">
                        <option value="">-- ทั้งหมด --</option>
                        <?php while($ut = $all_units->fetch_assoc()): ?>
                            <option value="<?php echo $ut['id']; ?>" <?php if($filter_unit==$ut['id']) echo 'selected'; ?>>
                                <?php echo $ut['unit_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">หมวดหมู่</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">-- ทั้งหมด --</option>
                        <?php $all_cats->data_seek(0); while($ct = $all_cats->fetch_assoc()): ?>
                            <option value="<?php echo $ct['id']; ?>" <?php if($filter_cat==$ct['id']) echo 'selected'; ?>>
                                <?php echo $ct['category_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">สถานที่</label>
                    <select name="location" class="form-select form-select-sm">
                        <option value="">-- ทั้งหมด --</option>
                        <?php $all_locs->data_seek(0); while($lc = $all_locs->fetch_assoc()): ?>
                            <option value="<?php echo $lc['id']; ?>" <?php if($filter_loc==$lc['id']) echo 'selected'; ?>>
                                <?php echo $lc['location_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1 align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100">กรอง</button>
                    <a href="report.php" class="btn btn-secondary btn-sm"><i class="fas fa-sync"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between mb-3 no-print">
        <a href="index.php" class="btn btn-dark btn-sm px-3"><i class="fas fa-arrow-left me-1"></i> กลับหน้าแรก</a>
        <div class="d-flex gap-2">
            <a href="export_excel.php?campus=<?php echo $filter_campus; ?>&unit_id=<?php echo $filter_unit; ?>&category=<?php echo $filter_cat; ?>&location=<?php echo $filter_loc; ?>" 
               class="btn btn-outline-success btn-sm px-3">
                <i class="fas fa-file-excel me-1"></i> ส่งออก Excel
            </a>
            
            <button onclick="window.print()" class="btn btn-success btn-sm px-4">
                <i class="fas fa-print me-1"></i> พิมพ์รายงาน (A4)
            </button>
        </div>
    </div>

    <div class="report-paper">
        <div class="report-header">
            <h4 class="fw-bold m-0">รายงานสรุปยอดและสถานะครุภัณฑ์โสตทัศนูปกรณ์</h4>
            <p class="text-muted m-0">สำนักหอสมุดกลาง มหาวิทยาลัยศรีนครินทรวิโรฒ (ข้อมูล ณ วันที่ <?php echo date('d/m/Y'); ?>)</p>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-3">
                <div class="stat-box bg-light">
                    <div class="small text-muted">ทั้งหมด</div>
                    <div class="h4 fw-bold mb-0"><?php echo $res_status['total']; ?></div>
                </div>
            </div>
            <div class="col-3">
                <div class="stat-box">
                    <div class="small text-success fw-bold">พร้อมใช้งาน</div>
                    <div class="h4 fw-bold mb-0 text-success"><?php echo $res_status['ready']; ?></div>
                </div>
            </div>
            <div class="col-3">
                <div class="stat-box">
                    <div class="small text-warning fw-bold">กำลังซ่อม</div>
                    <div class="h4 fw-bold mb-0 text-warning"><?php echo $res_status['repair']; ?></div>
                </div>
            </div>
            <div class="col-3">
                <div class="stat-box">
                    <div class="small text-danger fw-bold">ชำรุด</div>
                    <div class="h4 fw-bold mb-0 text-danger"><?php echo $res_status['broken']; ?></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-6">
                <h6 class="fw-bold"><i class="fas fa-chart-pie me-1"></i> 1. ยอดรวมแยกตามหมวดหมู่</h6>
                <table class="table table-bordered table-sm mt-2">
                    <thead class="table-light text-center"><tr><th>หมวดหมู่ครุภัณฑ์</th><th width="80">จำนวน</th></tr></thead>
                    <tbody>
                        <?php while($c = $res_cat->fetch_assoc()): ?>
                        <tr><td><?php echo $c['category_name']; ?></td><td class="text-center fw-bold"><?php echo $c['total_qty']; ?></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="col-6">
                <h6 class="fw-bold"><i class="fas fa-layer-group me-1"></i> 2. ยอดรวมแยกตามหน่วยงาน</h6>
                <table class="table table-bordered table-sm mt-2">
                    <thead class="table-light text-center"><tr><th>หน่วยงาน</th><th width="80">จำนวน</th></tr></thead>
                    <tbody>
                        <?php while($un = $res_unit_summary->fetch_assoc()): ?>
                        <tr><td><?php echo $un['unit_name'] ?: 'ไม่ระบุ'; ?></td><td class="text-center fw-bold"><?php echo $un['total_qty']; ?></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <h6 class="fw-bold mt-4"><i class="fas fa-list me-1"></i> 3. รายละเอียดรายการครุภัณฑ์ (ตามเงื่อนไขที่เลือก)</h6>
        <table class="table table-bordered mt-2" style="font-size: 11px;">
            <thead class="table-light text-center align-middle">
                <tr>
                    <th width="40">ลำดับ</th>
                    <th width="100">รหัสครุภัณฑ์</th>
                    <th>รายการ / ยี่ห้อ / รุ่น</th>
                    <th width="120">หน่วยงาน</th>
                    <th width="100">วิทยาเขต</th>
                    <th width="80">สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; while($row = $res_all->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td class="text-center fw-bold"><?php echo $row['equipment_code']; ?></td>
                    <td>
                        <div class="fw-bold"><?php echo $row['equipment_name']; ?></div>
                        <small class="text-muted"><?php echo $row['brand'].' '.$row['model']; ?></small>
                    </td>
                    <td class="text-center small"><?php echo $row['unit_name'] ?: '-'; ?></td>
                    <td class="text-center"><?php echo $row['campus']; ?></td>
                    <td class="text-center fw-bold <?php 
                        if($row['status']=='พร้อมใช้งาน') echo 'text-success';
                        elseif($row['status']=='กำลังซ่อม') echo 'text-warning';
                        else echo 'text-danger';
                    ?>"><?php echo $row['status']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
                
        <div class="mt-5 d-flex justify-content-between">
            <div class="text-center" style="width: 250px;">
                <p>ลงชื่อ..........................................................<br>(..........................................................)<br>ผู้จัดทำรายงาน</p>
            </div>
            <div class="text-center" style="width: 250px;">
                <p>ลงชื่อ..........................................................<br>(..........................................................)<br>ผู้อนุมัติรายงาน</p>
            </div>
        </div>
    </div>
</div>

</body>
</html>