<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบบริหารจัดการครุภัณฑ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <h4 class="text-center mb-4 fw-bold text-primary">ระบบบริหารครุภัณฑ์โสตทัศนูปกรณ์</h4>
            
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

<script>
        // โค้ดนี้จะทำงานทุกครั้งที่หน้าเว็บปรากฏขึ้น (รวมถึงการกดย้อนกลับจาก History)
        window.addEventListener('pageshow', function(event) {
            // สั่งเคลียร์ช่องกรอกข้อมูลให้ว่างเปล่า
            document.getElementById('swu_user').value = '';
            document.getElementById('swu_pass').value = '';
        });
    </script>
</body>
</html>