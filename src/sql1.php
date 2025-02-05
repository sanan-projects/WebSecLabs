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

$servername = "db";
$username = "sqli_user";
$password = "sqli_pass";
$dbname = "sqli_db";

$error = '';
$result = [];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Qoşulma xətası: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    
    // Kasıtlı olarak güvensiz sorgu - SQL Injection'a açık
    $sql = "SELECT * FROM users WHERE id = $user_id";
    
    try {
        $stmt = $conn->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>SQL Injection Lab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .main-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin: 2rem auto;
            max-width: 800px;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .hint {
            font-size: 0.8rem;
            color: #666;
            margin-top: 1rem;
        }
        .table {
            margin-top: 1rem;
        }
        .form-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-house-door me-2"></i>
                Home Page
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="main-container">
            <h2 class="text-center mb-4">SQL Injection Lab</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">İstifadəçi ID'sini daxil edin:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="user_id" name="user_id" 
                                   placeholder="Məsələn: 1">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>İstifadəçini Axtar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (!empty($result)): ?>
                <div class="table-responsive mt-4">
                    <h4 class="mb-3">Nəticələr:</h4>
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>İstifadəçi Adı</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <div class="hint mt-4">
                <p class="mt-2"><strong>Qeyd:</strong> Bu lab mühitində SQL Injection ilə istifadəçi şifrəsini görməyə çalışın.</p>
            </div>

            <!-- Report Form -->
            <div class="report-section mt-5">
                <h4>Təhlükəsizlik Problemi Bildir</h4>
                <form id="reportForm" action="get_reports.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="lab_type" value="SQL Lab 1">
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 