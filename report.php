<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'db_connect.php';

// --- 1. รับค่าการกรองจาก URL ---
$filter_campus = isset($_GET['campus']) ? $_GET['campus'] : '';
$filter_cat = isset($_GET['category']) ? $_GET['category'] : '';
$filter_loc = isset($_GET['location']) ? $_GET['location'] : '';

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

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- 2. ดึงข้อมูลตามเงื่อนไขที่เลือก ---

// สรุปยอดตามหมวดหมู่ (ใช้ $where_sql เพื่อให้ยอดอัปเดตตามฟิลเตอร์)
$sql_cat = "SELECT c.category_name, COUNT(e.id) as total_qty 
            FROM categories c 
            LEFT JOIN equipments e ON c.id = e.category_id 
            $where_sql
            GROUP BY c.id ORDER BY total_qty DESC";
$res_cat = $conn->query($sql_cat);

// สรุปยอดตามสถานที่
$sql_loc = "SELECT l.location_name, COUNT(e.id) as total_qty 
            FROM locations l 
            LEFT JOIN equipments e ON l.id = e.location_id 
            $where_sql
            GROUP BY l.id ORDER BY total_qty DESC";
$res_loc = $conn->query($sql_loc);

// รายการครุภัณฑ์ทั้งหมดตามฟิลเตอร์
$sql_all = "SELECT e.*, c.category_name, l.location_name 
            FROM equipments e
            LEFT JOIN categories c ON e.category_id = c.id
            LEFT JOIN locations l ON e.location_id = l.id
            $where_sql
            ORDER BY e.campus ASC, e.id DESC";
$res_all = $conn->query($sql_all);

// ดึงข้อมูล Master ข้อมูลสำหรับสร้าง Dropdown
$all_cats = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
$all_locs = $conn->query("SELECT * FROM locations ORDER BY location_name ASC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานครุภัณฑ์แบบระบุเงื่อนไข</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; font-size: 14px; }
        .report-header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #000; }
        @media print {
            .no-print { display: none !important; }
            .container { width: 100% !important; max-width: 100% !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="card mb-4 no-print border-primary shadow-sm">
        <div class="card-body bg-light">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">วิทยาเขต</label>
                    <select name="campus" class="form-select form-select-sm">
                        <option value="">-- ทั้งหมด --</option>
                        <option value="ประสานมิตร" <?php if($filter_campus=='ประสานมิตร') echo 'selected'; ?>>มศว ประสานมิตร</option>
                        <option value="องครักษ์" <?php if($filter_campus=='องครักษ์') echo 'selected'; ?>>มศว องครักษ์</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">หมวดหมู่</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">-- ทั้งหมด --</option>
                        <?php while($ct = $all_cats->fetch_assoc()): ?>
                            <option value="<?php echo $ct['id']; ?>" <?php if($filter_cat==$ct['id']) echo 'selected'; ?>>
                                <?php echo $ct['category_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">สถานที่ / ห้อง</label>
                    <select name="location" class="form-select form-select-sm">
                        <option value="">-- ทั้งหมด --</option>
                        <?php while($lc = $all_locs->fetch_assoc()): ?>
                            <option value="<?php echo $lc['id']; ?>" <?php if($filter_loc==$lc['id']) echo 'selected'; ?>>
                                <?php echo $lc['location_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter"></i> กรองข้อมูล</button>
                    <a href="report.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-sync"></i> ล้าง</a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between mb-3 no-print">
    <a href="equipments.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> กลับ</a>
    <div class="d-flex gap-2">
        <a href="export_excel.php?campus=<?php echo $filter_campus; ?>&category=<?php echo $filter_cat; ?>&location=<?php echo $filter_loc; ?>" class="btn btn-outline-success btn-sm">
            <i class="fas fa-file-excel"></i> ส่งออกเป็น Excel/CSV
        </a>
        <button onclick="window.print()" class="btn btn-success btn-sm"><i class="fas fa-print"></i> พิมพ์รายงานที่เลือก</button>
    </div>
</div>

    <div class="report-header">
        <h3>รายงานสรุปยอดครุภัณฑ์โสตทัศนูปกรณ์</h3>
        <?php if($filter_campus || $filter_cat || $filter_loc): ?>
        <?php endif; ?>
        <p class="small">ข้อมูล ณ วันที่ <?php echo date('d/m/Y'); ?></p>
    </div>

    <div class="row">
        <div class="col-6">
            <h6 class="fw-bold">1. สรุปยอดตามหมวดหมู่</h6>
            <table class="table table-bordered table-sm">
                <thead class="table-light"><tr><th>หมวดหมู่</th><th width="80" class="text-center">จำนวน</th></tr></thead>
                <tbody>
                    <?php while($c = $res_cat->fetch_assoc()): ?>
                    <tr><td><?php echo $c['category_name']; ?></td><td class="text-center"><?php echo $c['total_qty']; ?></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="col-6">
            <h6 class="fw-bold">2. สรุปยอดตามสถานที่ (ห้อง)</h6>
            <table class="table table-bordered table-sm">
                <thead class="table-light"><tr><th>สถานที่ / ห้อง</th><th width="80" class="text-center">จำนวน</th></tr></thead>
                <tbody>
                    <?php while($l = $res_loc->fetch_assoc()): ?>
                    <tr><td><?php echo $l['location_name']; ?></td><td class="text-center"><?php echo $l['total_qty']; ?></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <h6 class="fw-bold mt-3">3. รายละเอียดรายการครุภัณฑ์</h6>
    <table class="table table-bordered table-sm" style="font-size: 11px;">
        <thead class="table-light text-center">
            <tr>
                <th>ลำดับ</th><th>รหัสครุภัณฑ์</th><th>ชื่อ / ยี่ห้อ / รุ่น</th><th>วิทยาเขต</th><th>สถานที่</th><th>สถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php $i=1; while($row = $res_all->fetch_assoc()): ?>
            <tr>
                <td class="text-center"><?php echo $i++; ?></td>
                <td class="text-center fw-bold"><?php echo $row['equipment_code']; ?></td>
                <td><?php echo $row['equipment_name']; ?> <br><small class="text-muted"><?php echo $row['brand'].' '.$row['model']; ?></small></td>
                <td class="text-center"><?php echo $row['campus']; ?></td>
                <td><?php echo $row['location_name']; ?></td>
                <td class="text-center"><?php echo $row['status']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>