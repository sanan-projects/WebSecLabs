<?php
// Database connection
$servername = "admin_db";
$username = "admin_user";
$password = "admin_pass";
$dbname = "admin_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Qoşulma xətası: " . $e->getMessage());
}

// USERID cookie yoxlaması
if (!isset($_COOKIE['USERID'])) {
    header('HTTP/1.1 302 Found');
    header('Location: sign-in.php');
    exit;
}

// Cookie-ni decode et və yoxla
$cookie_value = base64_decode($_COOKIE['USERID']);
if (!$cookie_value) {
    header('HTTP/1.1 302 Found');
    header('Location: sign-in.php');
    exit;
}

// Admin istifadəçilərində yoxla
$stmt = $conn->prepare("SELECT username, password FROM admin_users");
$stmt->execute();
$admin_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Normal istifadəçilərdə yoxla
$stmt = $conn->prepare("SELECT username, password FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$is_valid = false;

// Admin və normal istifadəçiləri yoxla
foreach ($admin_users as $user) {
    if (md5($user['username'] . ':' . $user['password']) === $cookie_value) {
        $is_valid = true;
        break;
    }
}

if (!$is_valid) {
    foreach ($users as $user) {
        if (md5($user['username'] . ':' . $user['password']) === $cookie_value) {
            $is_valid = true;
            break;
        }
    }
}

if (!$is_valid) {
    header('HTTP/1.1 302 Found');
    header('Location: sign-in.php');
    exit;
}

// Session başlatmadan əvvəl adını təyin et
session_name('sql2_session');
session_start();

$servername = "db";
$username = "sqli_user";
$password = "sqli_pass";
$dbname = "sqli_db";

$error = '';
$success = '';

// Çıkış işlemi
if (isset($_GET['logout'])) {
    unset($_SESSION['sql2_user']);
    session_destroy();
    header("Location: sql2.php");
    exit();
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Qoşulma xətası: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Kasıtlı olarak güvensiz sorgu - SQL Injection'a açık
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    
    try {
        $stmt = $conn->query($sql);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['sql2_user'] = $user;
            $success = "Uğurlu giriş!";
        } else {
            $error = "Yanlış istifadəçi adı və ya şifrə!";
        }
    } catch(PDOException $e) {
        $error = "Sorğu xətası: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Injection Login Lab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            margin: 2rem auto;
        }
        .hint {
            font-size: 0.8rem;
            color: #666;
            margin-top: 1rem;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .user-avatar {
            width: 35px;
            height: 35px;
            background: #764ba2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-house-door me-2"></i>
                Home Page
            </a>
            <?php if (isset($_SESSION['sql2_user'])): ?>
                <div class="ms-auto user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['sql2_user']['username'], 0, 1)); ?>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-link text-dark text-decoration-none dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo htmlspecialchars($_SESSION['sql2_user']['username']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="?logout=1">
                                <i class="bi bi-box-arrow-right me-2"></i>Çıxış
                            </a></li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <?php if (!isset($_SESSION['sql2_user'])): ?>
            <div class="login-container">
                <h2 class="text-center mb-4">SQL Injection Login Lab</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">İstifadəçi Adı:</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifrə:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Giriş</button>
                </form>

                <!-- Report Form -->
                <div class="report-section mt-5">
                    <h4>Təhlükəsizlik Problemi Bildir</h4>
                    <form id="reportForm" action="get_reports.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="lab_type" value="SQL Lab 2">
                        <div class="mb-3">
                            <label for="message" class="form-label">Mesajınız</label>
                            <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="screenshots" class="form-label">Screenshots (multiple)</label>
                            <input type="file" class="form-control" id="screenshots" name="screenshots[]" accept="image/*" multiple>
                            <small class="text-muted">Birdən çox şəkil seçə bilərsiniz (maksimum 10MB)</small>
                        </div>
                        <button type="submit" class="btn search-btn">Göndər</button>
                    </form>

                    <div id="reportAlert" class="alert mt-3" style="display: none;"></div>
                </div>

                <script>
                document.getElementById('reportForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const alertDiv = document.getElementById('reportAlert');
                    const fileInput = document.getElementById('screenshots');
                    
                    // Faylların ümumi həcmini yoxla (maksimum 10MB)
                    let totalSize = 0;
                    for (let file of fileInput.files) {
                        totalSize += file.size;
                    }
                    
                    if (totalSize > 10 * 1024 * 1024) { // 10MB
                        alertDiv.className = 'alert alert-danger mt-3';
                        alertDiv.textContent = 'Faylların ümumi həcmi 10MB-dan çox ola bilməz';
                        alertDiv.style.display = 'block';
                        return;
                    }
                    
                    fetch('get_reports.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alertDiv.className = 'alert alert-danger mt-3';
                            alertDiv.textContent = data.error;
                        } else {
                            alertDiv.className = 'alert alert-success mt-3';
                            alertDiv.textContent = 'Report uğurla göndərildi!';
                            this.reset();
                        }
                        alertDiv.style.display = 'block';
                    })
                    .catch(error => {
                        alertDiv.className = 'alert alert-danger mt-3';
                        alertDiv.textContent = 'Xəta baş verdi. Yenidən cəhd edin.';
                        alertDiv.style.display = 'block';
                        console.error('Error:', error);
                    });
                });
                </script>

        <?php else: ?>
            <div class="login-container text-center">
                <h3 class="mb-4">Xoş Gəldiniz!</h3>
                <div class="alert alert-success">
                    <strong><?php echo htmlspecialchars($_SESSION['sql2_user']['username']); ?></strong> olaraq giriş etdiniz.
                </div>
                <div class="mt-4">
                    <a href="?logout=1" class="btn btn-danger">
                        <i class="bi bi-box-arrow-right me-2"></i>Çıxış
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 