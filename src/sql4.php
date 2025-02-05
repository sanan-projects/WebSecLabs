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

session_start();

$servername = "db";
$username = "sqli_user";
$password = "sqli_pass";
$dbname = "sqli_db";

$error = '';
$images = [];
$result_message = '';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Qoşulma xətası: " . $e->getMessage());
}

// Time-based SQL Injection için resim arama
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    
    // Input validation - sadece alfanumerik karakterler ve boşluk
    if (!preg_match('/^[a-zA-Z0-9\s]+$/', $search)) {
        $result_message = "Xəta: Sadəcə hərf, rəqəm və boşluq simvollarını istifadə edə bilərsiniz.";
    } else {
        // Güvenli sorgu - prepared statement kullanımı
        $sql = "SELECT * FROM images WHERE title LIKE :search OR description LIKE :search";
        
        try {
            $stmt = $conn->prepare($sql);
            $searchParam = "%" . $search . "%";
            $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
            
            $start_time = microtime(true);
            $stmt->execute();
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $end_time = microtime(true);
            
            $execution_time = round(($end_time - $start_time) * 1000, 2);
            $result_message = count($images) . " şəkil tapıldı. (Sorğu sürəti: " . $execution_time . "ms)";
            
        } catch(PDOException $e) {
            $result_message = "Axtarış sırasında bir xəta baş verdi.";
        }
    }
} else {
    // Tüm resimleri getir
    try {
        $sql = "SELECT * FROM images ORDER BY upload_date DESC";
        $stmt = $conn->query($sql);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $result_message = "Bir xəta baş verdi.";
    }
}

// Resim dosyasını getirme işlemi
if (isset($_GET['img'])) {
    $img_id = $_GET['img'];
    
    // Kasıtlı olarak güvensiz sorgu - Time-based SQL Injection'a açık
    $sql = "SELECT * FROM images WHERE id = " . $img_id;
    
    try {
        $stmt = $conn->query($sql);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($image) {
            $real_path = __DIR__ . "/images/image" . $img_id . ".jpg";
            
            if (file_exists($real_path)) {
                header('Content-Type: image/jpeg');
                readfile($real_path);
                exit;
            }
        }
        
        // Resim bulunamazsa placeholder göster
        header('Content-Type: image/jpeg');
        readfile(__DIR__ . "/images/placeholder.jpg");
        exit;
        
    } catch(PDOException $e) {
        // Hata durumunda placeholder göster
        header('Content-Type: image/jpeg');
        readfile(__DIR__ . "/images/placeholder.jpg");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time-Based SQL Injection Lab</title>
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
        .image-card {
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
        }
        .image-card:hover {
            transform: translateY(-5px);
        }
        .image-container {
            position: relative;
            padding-top: 75%; /* 4:3 Aspect Ratio */
            overflow: hidden;
        }
        .image-container img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
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
        </div>
    </nav>

    <div class="container">
        <div class="main-container">
            <h2 class="mb-4">Time-Based SQL Injection Lab</h2>
            <form method="GET" action="" class="mb-4">
                <div class="row">
                    <div class="col-md-12">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Şəkil axtar..." 
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i> Axtar
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <?php if ($result_message): ?>
                <div class="alert alert-info"><?php echo $result_message; ?></div>
            <?php endif; ?>
            <div class="row">
                <?php foreach ($images as $image): ?>
                    <div class="col-md-4">
                        <div class="card image-card">
                            <div class="image-container">
                                <img src="sql4.php?img=<?php echo $image['id']; ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($image['title']); ?>"
                                     onerror="this.src='images/placeholder.jpg'">
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($image['title']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($image['description']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="hint">
                <p class="mt-2"><strong>Qeyd:</strong> SQL Injection açığı tapmaq üçün time-based SQL Injection istifadə edə bilərsiniz.</p>
            </div>

            <!-- Report Form -->
            <div class="report-section mt-5">
                <h4>Təhlükəsizlik Problemi Bildir</h4>
                <form id="reportForm" action="get_reports.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="lab_type" value="SQL Lab 4">
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