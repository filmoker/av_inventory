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

// 2. รับค่าจาก URL สำหรับการกรองข้อมูล
$loc_param = isset($_GET['location']) ? $_GET['location'] : '';
$filter_param = isset($_GET['filter']) ? $_GET['filter'] : '';
$search_param = isset($_GET['search']) ? $_GET['search'] : '';
$unit_param = isset($_GET['unit_id']) ? $_GET['unit_id'] : ''; // รับค่าหน่วยงานจาก URL

// ดักรับค่าหมวดหมู่ให้รองรับทั้งที่เลือกจาก Dropdown (category) และกดมาจากกราฟหน้า Dashboard (category_id)
$cat_filter = '';
if (isset($_GET['category']) && $_GET['category'] != '') {
    $cat_filter = $_GET['category'];
} elseif (isset($_GET['category_id']) && $_GET['category_id'] != '') {
    $cat_filter = $_GET['category_id'];
}

// 3. จัดการเงื่อนไข Filter (SQL)
$where_clauses = [];
if ($filter_param != '') {
    $where_clauses[] = "e.status = '" . $conn->real_escape_string($filter_param) . "'";
}
if ($loc_param != '') {
    $where_clauses[] = "e.campus = '" . $conn->real_escape_string($loc_param) . "'";
}
if ($cat_filter != '') {
    $where_clauses[] = "e.category_id = '" . $conn->real_escape_string($cat_filter) . "'";
}
// เพิ่มเงื่อนไขกรองตามหน่วยงาน
if ($unit_param != '') {
    $where_clauses[] = "e.unit_id = '" . $conn->real_escape_string($unit_param) . "'";
}
if ($search_param != '') {
    $s_esc = $conn->real_escape_string($search_param);
    $where_clauses[] = "(e.equipment_code LIKE '%$s_esc%' 
                        OR e.equipment_name LIKE '%$s_esc%' 
                        OR e.brand LIKE '%$s_esc%' 
                        OR e.model LIKE '%$s_esc%' 
                        OR e.campus LIKE '%$s_esc%' 
                        OR e.responsible_person LIKE '%$s_esc%' 
                        OR e.status LIKE '%$s_esc%' 
                        OR e.remark LIKE '%$s_esc%'
                        OR c.category_name LIKE '%$s_esc%' 
                        OR l.location_name LIKE '%$s_esc%')";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// 4. ดึงข้อมูลครุภัณฑ์จากฐานข้อมูล 
$sql = "SELECT e.*, c.category_name, l.location_name, u.unit_name 
        FROM equipments e
        LEFT JOIN categories c ON e.category_id = c.id
        LEFT JOIN locations l ON e.location_id = l.id
        LEFT JOIN units u ON e.unit_id = u.id
        $where_sql
        ORDER BY e.id DESC";

$result = $conn->query($sql);
$all_categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");

// เตรียมข้อมูลหน่วยงานสำหรับแสดงใน Sidebar และ Dropdown
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
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการครุภัณฑ์ - ระบบบริหารครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        
        .sidebar { background-color: #1e2b3c; min-height: 100vh; color: #fff; width: 220px; }
        .sidebar a { color: #c2c7d0; text-decoration: none; padding: 12px 20px; display: block; border-bottom: 1px solid #2b3c53; }
        .sidebar a:hover, .sidebar a.active { background-color: #2b3c53; color: #fff; }
        .table-custom th { background-color: #2b3c53; color: white; border: none; padding: 12px 8px; font-size: 0.9em; }
        .badge-age { background-color: #f8f9fa; color: #333; border: 1px solid #dee2e6; }
        .text-small { font-size: 0.85em; }
        .hover-white:hover { color: #ffffff !important; }
        
        #bulk-action-bar {
            display: none; background: #fff; border: 2px solid #dc3545; border-radius: 10px;
            padding: 10px 20px; position: sticky; top: 20px; z-index: 1000; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

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
                    <div class="collapse <?php echo ($loc_param == 'ประสานมิตร') ? 'show' : ''; ?>" id="menuPsm" style="background-color: #0f1722;">
                        <a href="equipments.php?location=ประสานมิตร" class="<?php echo ($loc_param == 'ประสานมิตร' && $unit_param == '') ? 'text-white fw-bold' : 'text-white-50'; ?> hover-white d-block py-2" style="padding-left: 55px; font-size: 0.85em;">
                            <i class="fas fa-list me-1"></i> ดูทั้งหมด
                        </a>
                        <?php foreach($units as $u): ?>
                        <a href="equipments.php?location=ประสานมิตร&unit_id=<?php echo $u['id']; ?>" class="<?php echo ($loc_param == 'ประสานมิตร' && $unit_param == $u['id']) ? 'text-warning fw-bold' : 'text-white-50'; ?> hover-white d-block py-2" style="padding-left: 55px; font-size: 0.75em; line-height: 1.4;">
                            - <?php echo htmlspecialchars($u['unit_name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <a href="#menuOkr" data-bs-toggle="collapse" class="text-white-50 hover-white d-block mt-1" style="padding: 10px 20px 10px 45px; font-size: 0.9em;">
                        องครักษ์ <i class="fas fa-caret-down float-end mt-1"></i>
                    </a>
                    <div class="collapse <?php echo ($loc_param == 'องครักษ์') ? 'show' : ''; ?>" id="menuOkr" style="background-color: #0f1722;">
                        <a href="equipments.php?location=องครักษ์" class="<?php echo ($loc_param == 'องครักษ์' && $unit_param == '') ? 'text-white fw-bold' : 'text-white-50'; ?> hover-white d-block py-2" style="padding-left: 55px; font-size: 0.85em;">
                            <i class="fas fa-list me-1"></i> ดูทั้งหมด
                        </a>
                        <?php foreach($units as $u): ?>
                        <a href="equipments.php?location=องครักษ์&unit_id=<?php echo $u['id']; ?>" class="<?php echo ($loc_param == 'องครักษ์' && $unit_param == $u['id']) ? 'text-warning fw-bold' : 'text-white-50'; ?> hover-white d-block py-2" style="padding-left: 55px; font-size: 0.75em; line-height: 1.4;">
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

        <div class="col-md-10 p-4 bg-light flex-grow-1" style="min-width: 0; overflow-x: auto;">
            
            <div id="bulk-action-bar" class="mb-3 align-items-center justify-content-between flex-wrap gap-2">
                <div class="fw-bold text-danger">
                    <i class="fas fa-check-square me-2"></i> เลือกแล้ว <span id="selected-count">0</span> รายการ
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-warning btn-sm fw-bold text-dark" onclick="bulkEdit()">
                        <i class="fas fa-edit me-1"></i> แก้ไขที่เลือก
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="bulkDelete()">
                        <i class="fas fa-trash-alt me-1"></i> ลบที่เลือก
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="deselectAll()">ยกเลิก</button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
                <h4 class="m-0"> รายการครุภัณฑ์ 
                    <?php 
                    if($loc_param) { 
                        echo "- $loc_param "; 
                        if ($unit_param) {
                            foreach($units as $u) {
                                if($u['id'] == $unit_param) echo " <span class='text-primary fs-5'>(" . htmlspecialchars($u['unit_name']) . ")</span>";
                            }
                        }
                    } 
                    ?> 
                </h4>
                
                <div class="d-flex gap-2 align-items-center">
                    <form action="equipments.php" method="GET" class="d-flex gap-2 m-0">
                        <?php if($loc_param): ?><input type="hidden" name="location" value="<?php echo $loc_param; ?>"><?php endif; ?>
                        
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <input type="text" id="searchInput" class="form-control" placeholder="ค้นหา...">
                            <button class="btn btn-dark" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                        
                        <select name="unit_id" class="form-select form-select-sm" style="width: 170px;" onchange="this.form.submit()">
                            <option value=""> หน่วยงานทั้งหมด </option>
                            <?php foreach($units as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo ($unit_param == $u['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['unit_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="category" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                            <option value=""> หมวดหมู่ทั้งหมด </option>
                            <?php 
                            if($all_categories && $all_categories->num_rows > 0):
                                $all_categories->data_seek(0); 
                                while($c = $all_categories->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($cat_filter == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['category_name']); ?>
                                </option>
                            <?php 
                                endwhile; 
                            endif; 
                            ?>
                        </select>
                    </form>
                    
                    <a href="equipment_add.php" class="btn btn-sm btn-primary text-nowrap">
                        เพิ่มครุภัณฑ์
                    </a>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="equipmentTable" class="table table-hover align-middle mb-0 text-small">
                            <thead class="table-custom text-nowrap text-center">
                                <tr>
                                    <th><input type="checkbox" id="check-all" class="form-check-input"></th> 
                                    <th>ลำดับ</th>
                                    <th>รหัสครุภัณฑ์</th>
                                    <th>ชื่อครุภัณฑ์</th>
                                    <th>ยี่ห้อ/รุ่น</th>
                                    <th>วันที่รับ</th>
                                    <th>อายุ</th>
                                    <th>วิทยาเขต</th>
                                    <th>หน่วยงาน</th>
                                    <th>สถานที่จัดเก็บ</th>
                                    <th>ผู้ครอบครอง</th>
                                    <th>สถานะ</th>
                                    <th>หมายเหตุ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($result && $result->num_rows > 0): 
                                    $i = 1;
                                    while($row = $result->fetch_assoc()): 
                                        $entry_date = new DateTime($row['entry_date']);
                                        $diff = (new DateTime())->diff($entry_date);
                                        $status_time = $row['status_updated_at'];
                                        $age_text = "";
                                        if ($diff->y > 0) {
                                            $age_text .= $diff->y . " ปี ";
                                        }
                                        if ($diff->m > 0) {
                                            $age_text .= $diff->m . " เดือน";
                                        }

                                        if ($diff->y == 0 && $diff->m == 0) {
                                            $age_text = $diff->d . " วัน"; 
                                        }
                                ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" value="<?php echo $row['id']; ?>" class="form-check-input item-checkbox">
                                    </td>
                                    <td class="text-center text-muted"><?php echo $i++; ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['equipment_code']); ?></td>
                                    <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                                    <td><?php echo (htmlspecialchars($row['brand']) ?: '-') . " / " . (htmlspecialchars($row['model']) ?: '-'); ?></td>
                                    
                                    <td class="text-center"><?php echo date('d/m/Y', strtotime($row['entry_date'])); ?></td>
                                    
                                    <td class="text-center"><span class="badge badge-age text-nowrap"><?php echo trim($age_text); ?></span></td>
                                    
                                    <td class="text-center">
                                        <span class="badge <?php echo ($row['campus'] == 'ประสานมิตร') ? 'bg-danger' : 'bg-primary'; ?>">
                                            <?php echo htmlspecialchars($row['campus']) ?: '-'; ?>
                                        </span>
                                    </td>
                                    
                                    <td class="text-center text-info fw-bold">
                                        <small class="<?php 
                                        if ($row['unit_name'] == 'หน่วยโครงสร้างพื้นฐานเทคโนโลยีสารสนเทศดิจิทัล') {
                                            echo 'text-primary'; 
                                        } else {
                                            echo 'text-info';  
                                        }
                                    ?>">
                                            <?php echo htmlspecialchars($row['unit_name']) ?: '-'; ?>
                                        </small>
                                    </td>
                                    
                                    <td class="text-center text-secondary"><?php echo htmlspecialchars($row['location_name']) ?: '-'; ?></td>
                                    
                                    <td class="text-center fw-bold"><?php echo htmlspecialchars($row['responsible_person']) ?: '-'; ?></td>
                                    <td class="text-center">
                                        <span class="badge px-2 <?php 
                                            if ($row['status'] == 'พร้อมใช้งาน') echo 'bg-success'; 
                                            elseif ($row['status'] == 'กำลังซ่อม') echo 'bg-warning text-dark'; 
                                            elseif ($row['status'] == 'ชำรุด') echo 'bg-danger'; 
                                            else echo 'bg-secondary';
                                        ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                        
                                        <?php if ($row['status'] != 'พร้อมใช้งาน' && !empty($status_time) && strtotime($status_time) > 0): ?>
                                        <div class="mt-1 small text-muted" style="font-size: 0.75rem; font-weight: 500;">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php 
                                                $s_date = new DateTime($status_time);
                                                $months_th = ["","ม.ค.","ก.พ.","มี.ค.","เม.ย.","พ.ค.","มิ.ย.","ก.ค.","ส.ค.","ก.ย.","ต.ค.","พ.ย.","ธ.ค."];
                                                echo $s_date->format('j') . " " . $months_th[(int)$s_date->format('n')] . " " . $s_date->format('Y');
                                            ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-start text-muted">
                                        <small><?php echo htmlspecialchars($row['remark']) ?: '-'; ?></small>
                                    </td>
                                    
                                    <td class="text-center text-nowrap">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="equipment_view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info" title="ดูรายละเอียด">
                                                <i class="fas fa-list-ul"></i>
                                            </a>
                                            <a href="equipment_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                                            <a href="equipment_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ลบข้อมูล?')"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile; 
                                endif; 
                                ?>
                            </tbody>
                        </table>
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
    // 1. นำ DataTables ไปเก็บไว้ในตัวแปร table และเปิดการตั้งค่าค้นหา
    var table = $('#equipmentTable').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json" },
        "searching": true,
        "dom": 'lrtip',
        "paging": true,       
        "ordering": true,     
        "order": [[ 1, "asc" ]], 
        "columnDefs": [
            { "orderable": false, "targets": [0, 13] }
        ]
    });

    // 2. ฟังก์ชัน Live Search: เมื่อพิมพ์ข้อความ ระบบจะกรองข้อมูลในตารางทันที
    $('#searchInput').on('keyup', function() {
        table.search(this.value).draw();
    });

    // 3. ป้องกันปัญหา: กด Enter แล้วหน้าเว็บรีเฟรช (โหลดใหม่)
    $('#searchInput').on('keypress', function(e) {
        if (e.which == 13) {
            e.preventDefault(); 
        }
    });
});

// ====================================================
// ส่วนโค้ด Bulk Delete / Bulk Edit 
// ====================================================
const checkAll = document.getElementById('check-all');
const bulkBar = document.getElementById('bulk-action-bar');
const countSpan = document.getElementById('selected-count');

function updateUI() {
    const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
    countSpan.innerText = checkedCount;
    bulkBar.style.display = checkedCount > 0 ? 'flex' : 'none';
}

checkAll.addEventListener('change', function() {
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = this.checked);
    updateUI();
});

document.body.addEventListener('change', function(e) {
    if (e.target.classList.contains('item-checkbox')) { updateUI(); }
});

function deselectAll() {
    checkAll.checked = false;
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
    updateUI();
}

function bulkDelete() {
    const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
    const ids = Array.from(checkedBoxes).map(cb => cb.value);
    if (ids.length > 0) {
        if (confirm(`ยืนยันการลบ ${ids.length} รายการที่เลือก?`)) {
            window.location.href = 'equipment_bulk_delete.php?ids=' + ids.join(',');
        }
    }
}

function bulkEdit() {
    const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
    const ids = Array.from(checkedBoxes).map(cb => cb.value);
    if (ids.length > 0) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'equipment_bulk_edit.php';
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_ids';
        input.value = ids.join(',');
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>