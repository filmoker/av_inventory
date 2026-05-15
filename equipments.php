<?php
// 1. ดึงไฟล์เชื่อมต่อฐานข้อมูล
require_once 'db_connect.php';

// 2. รับค่าจาก URL สำหรับการกรองข้อมูล
$loc_param = isset($_GET['location']) ? $_GET['location'] : '';
$filter_param = isset($_GET['filter']) ? $_GET['filter'] : '';
$cat_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search_param = isset($_GET['search']) ? $_GET['search'] : '';

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
$sql = "SELECT e.*, c.category_name, l.location_name 
        FROM equipments e
        LEFT JOIN categories c ON e.category_id = c.id
        LEFT JOIN locations l ON e.location_id = l.id
        $where_sql
        ORDER BY e.id DESC";

$result = $conn->query($sql);
$all_categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
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
                    <h5 class="m-0"><i class="fas fa-boxes"></i> ระบบครุภัณฑ์</h5>
                </a>
            </div>
            <nav class="mt-3">
                <a href="index.php"><i class="fas fa-home me-2"></i> หน้าแรก</a>
                <a href="#equipmentMenu" data-bs-toggle="collapse" class="text-white" aria-expanded="true">
                    <i class="fas fa-desktop me-2"></i> รายการครุภัณฑ์
                </a>
                <div class="collapse show" id="equipmentMenu" style="background-color: #16202c;">
                    <a href="equipments.php?location=ประสานมิตร" class="<?php echo ($loc_param == 'ประสานมิตร') ? 'text-white fw-bold' : 'text-white-50'; ?> hover-white" style="padding-left: 45px;"> ประสานมิตร</a>
                    <a href="equipments.php?location=องครักษ์" class="<?php echo ($loc_param == 'องครักษ์') ? 'text-white fw-bold' : 'text-white-50'; ?> hover-white" style="padding-left: 45px;"> องครักษ์</a>
                </div>
                <a href="locations.php"><i class="fas fa-map-marker-alt me-2"></i> จัดการสถานที่</a>
                <a href="categories.php"><i class="fas fa-tags me-2"></i> จัดการหมวดหมู่</a>
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
                <h4> ทะเบียนรายการครุภัณฑ์ <?php echo $loc_param ? "- มศว $loc_param" : ""; ?> </h4>
                <div class="d-flex gap-2">
                    <form action="equipments.php" method="GET" class="d-flex gap-2">
                        <?php if($loc_param): ?><input type="hidden" name="location" value="<?php echo $loc_param; ?>"><?php endif; ?>
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" name="search" class="form-control" placeholder="ค้นหา..." value="<?php echo htmlspecialchars($search_param); ?>">
                            <button class="btn btn-dark" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                        <select name="category" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                            <option value=""> หมวดหมู่ </option>
                            <?php $all_categories->data_seek(0); while($c = $all_categories->fetch_assoc()): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($cat_filter == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo $c['category_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                    <a href="equipment_add.php" class="btn btn-sm btn-primary"> เพิ่มครุภัณฑ์</a>
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
                                    <th>อายุการใช้งาน</th>
                                    <th>วิทยาเขต</th>
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
                                        <div class="mb-1"><small><?php echo htmlspecialchars($row['remark']) ?: '-'; ?></small></div>
                                        
                                        <?php if (!empty($row['created_at']) && strtotime($row['created_at']) > 0): ?>
                                        <div class="text-primary mt-2" style="font-size: 0.70rem;">
                                            <i class="fas fa-plus-circle me-1"></i>เพิ่ม: Admin
                                            (<?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?> น.)
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($row['updated_at']) && strtotime($row['updated_at']) > 0): ?>
                                        <div class="text-secondary mt-1" style="font-size: 0.70rem;">
                                            <i class="fas fa-user-edit me-1"></i>แก้: Admin
                                            (<?php echo date('d/m/Y H:i', strtotime($row['updated_at'])); ?> น.)
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-center text-nowrap">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="equipment_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                                            <a href="equipment_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ลบข้อมูล?')"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; endif; ?>
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
    $('#equipmentTable').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json" },
        "searching": false,   
        "paging": true,       
        "ordering": true,     
        "order": [[ 1, "asc" ]], 
        "columnDefs": [
            { "orderable": false, "targets": [0, 12] } 
        ]
    });
});

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
</body>
</html>