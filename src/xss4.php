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
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XSS Lab 4</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <?php
    if(isset($_GET['mode'])) {
        if($_GET['mode'] === 'dark') {
            echo '<style>
                body { background: #1a1a1a !important; }
                .main-container { 
                    background: rgba(40, 40, 40, 0.95) !important;
                    color: #fff !important;
                }
                .search-title { color: #fff !important; }
                .result-card { 
                    background: #333 !important;
                    color: #fff !important;
                }
                .search-input {
                    background: #333 !important;
                    color: #fff !important;
                    border-color: #444 !important;
                }
                .search-input::placeholder {
                    color: #888 !important;
                }
                .results {
                    background: rgba(40, 40, 40, 0.95) !important;
                    color: #fff !important;
                }
                .h4 {
                    color: #fff !important;
                }
                .result-label {
                    color: #fff !important;
                }
            </style>';
        } else if($_GET['mode'] === 'light') {
            echo '<style>
                body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; }
                .main-container { background: rgba(255, 255, 255, 0.95) !important; }
                .search-title { color: #4a4a4a !important; }
            </style>';
        } else {
            // XSS açığı için diğer mode değerlerini olduğu gibi kullan
            echo '<style>
                body {
                    color: ' . $_GET['mode'] . ';
                }
            </style>';
        }
    }
    ?>
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
        .search-input {
            border-radius: 50px;
            padding: 0.8rem 1.5rem;
            border: 2px solid #ddd;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            border-color: #764ba2;
            box-shadow: 0 0 15px rgba(118, 75, 162, 0.2);
        }
        .search-btn {
            border-radius: 50px;
            padding: 0.8rem 2rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.3);
        }
        .results {
            margin-top: 2rem;
            padding: 1rem;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.9);
        }
        .result-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
            <div class="d-flex align-items-center gap-2">
                <a href="?mode=dark<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" 
                   class="btn btn-dark btn-sm py-1 px-3">
                    <i class="bi bi-moon"></i>
                </a>
                <a href="?mode=light<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" 
                   class="btn btn-light btn-sm py-1 px-3">
                    <i class="bi bi-sun"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="main-container">
            <h2 class="text-center mb-4">XSS Lab 4</h2>
            
            <form method="GET" action="">
                <div class="input-group mb-3">
                    <input type="text" class="form-control search-input" name="search" 
                           placeholder="Nə axtarırsınız?" 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                    <button class="btn search-btn" type="submit">Axtar</button>
                    <?php
                    if(isset($_GET['mode'])) {
                        echo '<input type="hidden" name="mode" value="' . htmlspecialchars($_GET['mode'], ENT_QUOTES, 'UTF-8') . '">';
                    }
                    ?>
                </div>
            </form>

            <?php if(isset($_GET['search'])): ?>
                <div class="results">
                    <h4 class="mb-3">Axtarış nəticələri</h4>
                    <div class="result-card card">
                        <div class="card-body">
                            <div class="search-result">
                                <span class="result-label">Tapılmadı</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Report Form -->
            <div class="report-section mt-5">
                <h4>Təhlükəsizlik Problemi Bildir</h4>
                <form id="reportForm" action="get_reports.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="lab_type" value="XSS Lab 4">
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

            <div class="hint mt-4">
                <p class="mt-2"><strong>Qeyd:</strong> Bu lab mühitində XSS-i təhlükəsiz şəkildə test edə bilərsiniz.</p>
            </div>
        </div>
    </div>
</body>
</html> 