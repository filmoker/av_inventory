<?php session_start(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Asset Management System of Digital Academic Technology</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f4f6f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <div class="card login-card p-4">
        <div class="card-body">
           <h4 class="text-center mb-4 fw-bold text-primary">Asset Management System<br>of Digital Academic Technology</h4>
            
            <?php 
            // เช็กว่ามีข้อความ Error ส่งมาจากการล็อกอินผิดหรือไม่
            if (isset($_SESSION['login_error'])) { 
            ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <strong>เข้าสู่ระบบไม่สำเร็จ!</strong> <br>
                    <?php echo $_SESSION['login_error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php 
                unset($_SESSION['login_error']); 
            } 
            ?>

            <form action="login_process.php" method="POST" autocomplete="off">
                
                <div class="mb-3">
                    <label for="username" class="form-label">ชื่อผู้ใช้งาน (SWU Account)</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="บัวศรีไอดี" required autocomplete="off" readonly onfocus="this.removeAttribute('readonly');">
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="รหัสผ่าน" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary fw-bold py-2">เข้าสู่ระบบ</button>
                </div>
                
            </form>
            
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // โค้ดนี้จะทำงานทุกครั้งที่หน้าเว็บปรากฏขึ้น (รวมถึงการกดย้อนกลับจาก History)
        window.addEventListener('pageshow', function(event) {
            // สั่งเคลียร์ช่องกรอกข้อมูลให้ว่างเปล่า (เปลี่ยน ID ให้ตรงกับ HTML ด้านบน)
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
        });
    </script>
</body>
</html>