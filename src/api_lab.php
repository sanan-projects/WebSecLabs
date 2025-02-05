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
    <title>API Security Lab</title>
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
        .form-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        #userInfo {
            display: none;
        }
        .auth-buttons {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-buttons .btn {
            margin: 0 10px;
            padding: 10px 30px;
        }
        .modal-content {
            border-radius: 15px;
        }
        .modal-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .modal-body {
            padding: 2rem;
        }
        .gallery-container {
            margin-top: 2rem;
        }
        .image-card {
            height: 100%;
            transition: transform 0.2s;
        }
        .image-card:hover {
            transform: translateY(-5px);
        }
        .image-container {
            height: 200px;
            overflow: hidden;
        }
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-section {
            background: linear-gradient(45deg, #f6f9fc 0%, #f1f4f8 100%);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .profile-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin-right: 1rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .validation-feedback {
            font-size: 0.875rem;
            margin-top: 0.25rem;
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
            <div id="userInfo" class="ms-auto">

                <div class="d-flex align-items-center">
                    <div class="profile-avatar me-2">
                        <i class="bi bi-person"></i>
                    </div>
                    <div>
                        <span class="me-2">Xoş gəldin, <strong id="username"></strong></span>
                        <button onclick="logout()" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-box-arrow-right me-1"></i>Çıxış
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="main-container">
            <h2 class="text-center mb-4">
                <i class="bi bi-shield-check me-2"></i>
                API Security Lab
            </h2>
            
            <!-- Auth Buttons -->
            <div class="auth-buttons" id="authButtons">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Giriş Et
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#registerModal">
                    <i class="bi bi-person-plus me-2"></i>Qeydiyyatdan Keç
                </button>
            </div>

            <!-- Profile Section -->
            <div id="profileSection" style="display: none;">
                <div class="profile-section">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="bi bi-person"></i>
                        </div>
                        <div>
                            <h3 id="profileUsername"></h3>
                            <p id="profileEmail" class="text-muted"></p>
                        </div>
                    </div>

                    <!-- Update Forms -->
                    <div class="row">
                        <div class="col-md-6">
                            <form id="updateUsernameForm">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" name="new_username" required>
                                    <label>Yeni İstifadəçi Adı</label>
                                </div>
                                <button type="submit" class="btn btn-primary">İstifadəçi Adını Yenilə</button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form id="updateEmailForm">
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" name="new_email" required>
                                    <label>Yeni Email</label>
                                </div>
                                <button type="submit" class="btn btn-primary">Email Yenilə</button>
                            </form>
                        </div>
                    </div>

                    <form id="updatePasswordForm" class="mt-3">
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" name="password" required>
                            <label>Yeni Şifrə</label>
                        </div>
                        <button type="submit" class="btn btn-warning">Şifrə Yenilə</button>
                    </form>
                </div>

                <!-- Admin Panel -->
                <div id="adminPanel" style="display: none;" class="mt-4">
                    <div class="card bg-dark text-white">
                        <div class="card-body">
                            <h4 class="card-title">
                                <i class="bi bi-shield-lock-fill me-2"></i>
                                Admin Paneli
                            </h4>
                            <p class="card-text">Təbriklər! API təhlükəsizlik açığını tapdınız.</p>
                            <div class="alert alert-warning">
                                <strong>İpucu:</strong> Login API-də ID=1 olan admin hesabına daxil olmaq üçün request parametrlərini manipulyasiya etdiniz.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gallery Section -->
            <div class="gallery-container">
                <h4 class="mb-3">
                    <i class="bi bi-images me-2"></i>Şəkillər
                </h4>
                <div class="row">
                    <?php
                    $images = [
                        ['id' => 1, 'title' => 'Yağışlı yol', 'desc' => 'Şam meşəsi arasından keçən yağışlı asfalt yol'],
                        ['id' => 2, 'title' => 'Dumanlı meşə', 'desc' => 'Səhər dumanına bürünmüş şam ağacları'],
                        ['id' => 3, 'title' => 'Qürub buludları', 'desc' => 'Qızılı-bənövşəyi rənglərlə bəzənmiş axşam səması'],
                        ['id' => 4, 'title' => 'Göl kənarı ev', 'desc' => 'Meşə və göl kənarında yerləşən işıqlı dağ evi'],
                        ['id' => 5, 'title' => 'Dağ gölü', 'desc' => 'Əzəmətli dağlar arasında yerləşən güzgü kimi göl'],
                        ['id' => 6, 'title' => 'Gecə şəhəri', 'desc' => 'Mavi və qırmızı işıqlarla bəzənmiş göydələnlər']
                    ];
                    foreach ($images as $image): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card image-card">
                                <div class="image-container">
                                    <img src="images/image<?php echo $image['id']; ?>.jpg" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($image['title']); ?>">
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($image['title']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($image['desc']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- API Security Hints -->
            <div class="hint">
                <p class="mt-2"><strong>Qeyd:</strong> Bu lab'da API təhlükəsizlik açığını tapmalısınız.</p>
            </div>

            <div class="hint mt-4">
                <p class="mt-2"><strong>Qeyd:</strong> Bu lab mühitində API təhlükəsizliyini test edə bilərsiniz.</p>
            </div>

            <!-- Report Form -->
            <div class="report-section mt-5">
                <h4>Təhlükəsizlik Problemi Bildir</h4>
                <form id="reportForm" action="get_reports.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="lab_type" value="API Lab 1">
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
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Giriş Et
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm" class="needs-validation" novalidate>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="loginUsername" name="username" required>
                            <label>İstifadəçi Adı</label>
                            <div class="invalid-feedback">İstifadəçi adı lazımdır</div>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="loginPassword" name="password" required>
                            <label>Şifrə</label>
                            <div class="invalid-feedback">Şifrə lazımdır</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Giriş Et
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus me-2"></i>Qeydiyyatdan Keç
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="registerForm" class="needs-validation" novalidate>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="registerUsername" name="username" 
                                   pattern="[a-zA-Z0-9_]{3,20}" required>
                            <label>İstifadəçi Adı</label>
                            <div class="invalid-feedback">
                                İstifadəçi adı 3-20 simvol arası olmalı və sadəcə hərf, rəqəm və alt çizgi daxildir
                            </div>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="registerEmail" name="email" required>
                            <label>Email</label>
                            <div class="invalid-feedback">Etibarlı bir email adresi daxil edin</div>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="registerPassword" name="password" 
                                   pattern=".{6,}" required>
                            <label>Şifrə</label>
                            <div class="invalid-feedback">Şifrə ən az 6 simvol olmalıdır</div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-person-plus me-2"></i>Qeydiyyatdan Keç
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Session kontrolü
        function checkSession() {
            const sessionCookie = document.cookie
                .split('; ')
                .find(row => row.startsWith('session='));
            
            if (sessionCookie) {
                const userData = JSON.parse(atob(sessionCookie.split('=')[1]));
                document.getElementById('userInfo').style.display = 'block';
                document.getElementById('username').textContent = userData.username;
                document.getElementById('profileUsername').textContent = userData.username;
                document.getElementById('profileEmail').textContent = userData.email;
                document.getElementById('authButtons').style.display = 'none';
                document.getElementById('profileSection').style.display = 'block';
                
                // Admin panelini göstər/gizlət - sadəcə ID 1 olan istifadəçi üçün
                if (parseInt(userData.id) === 1) {  // String-i number-ə çeviririk
                    document.getElementById('adminPanel').style.display = 'block';
                } else {
                    document.getElementById('adminPanel').style.display = 'none';
                }
                
                return true;
            }
            
            document.getElementById('userInfo').style.display = 'none';
            document.getElementById('authButtons').style.display = 'block';
            document.getElementById('profileSection').style.display = 'none';
            document.getElementById('adminPanel').style.display = 'none';
            return false;
        }

        // Sayfa yüklendiğinde session kontrolü
        document.addEventListener('DOMContentLoaded', checkSession);

        // API isteği gönderme fonksiyonu
        async function makeRequest(endpoint, method, data) {
            const headers = {
                'Content-Type': 'application/json'
            };
            
            const sessionCookie = document.cookie
                .split('; ')
                .find(row => row.startsWith('session='));
            
            if (sessionCookie) {
                const userData = JSON.parse(atob(sessionCookie.split('=')[1]));
                headers['Authorization'] = `Bearer ${btoa(userData.username + ':' + Date.now())}`;
            }

            let url = `/api/user/${endpoint}`;
            
            // Şifre güncelleme için özel URL
            if (endpoint === 'update-password' && sessionCookie) {
                const userData = JSON.parse(atob(sessionCookie.split('=')[1]));
                url = `/api/user/${userData.id}`;
                // Sadece şifreyi gönder
                data = {
                    password: data.password
                };
            }
            // Email ve username güncelleme için
            else if (endpoint === 'update-email') {
                url = `/api/user/update`;
                data = { email: data.new_email };
            }
            else if (endpoint === 'update-username') {
                url = `/api/user/update`;
                data = { username: data.new_username };
            }

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: headers,
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                
                if (result.error) {
                    alert(result.error);
                    return;
                }

                if (endpoint === 'login' && result.token) {
                    document.cookie = `session=${btoa(JSON.stringify({
                        id: result.id,
                        username: result.username,
                        email: result.email
                    }))}; path=/`;
                    
                    bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
                    checkSession();
                } else {
                    alert(result.message || 'İşlem başarılı');
                    if (endpoint === 'register') {
                        bootstrap.Modal.getInstance(document.getElementById('registerModal')).hide();
                    }
                }

            } catch (error) {
                alert('Bir xəta baş verdi: ' + error.message);
            }
        }

        // Çıkış yapma fonksiyonu
        function logout() {
            document.cookie = 'session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
            checkSession();
        }

        // Form event listeners
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            await makeRequest('register', 'POST', Object.fromEntries(formData));
        });

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            await makeRequest('login', 'POST', Object.fromEntries(formData));
        });

        document.getElementById('updateUsernameForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!checkSession()) return;
            const formData = new FormData(e.target);
            await makeRequest('update-username', 'PUT', Object.fromEntries(formData));
        });

        document.getElementById('updateEmailForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!checkSession()) return;
            const formData = new FormData(e.target);
            await makeRequest('update-email', 'PUT', Object.fromEntries(formData));
        });

        document.getElementById('updatePasswordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!checkSession()) return;
            const formData = new FormData(e.target);
            await makeRequest('update-password', 'PUT', Object.fromEntries(formData));
        });

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
</body>
</html> 