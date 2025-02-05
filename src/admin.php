<?php
session_start();

if (!isset($_SESSION['logged_in'])) {
    $_SESSION['error'] = "Bu səhifəni görüntüləmək üçün giriş etməlisiniz!";
    header("Location: login.php");
}

// Çıxış funksiyası
if (isset($_GET['logout'])) {
    session_destroy();
    setcookie('PHPSESSİD', '', time() - 3600, '/');
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-top: 60px;
        }
        .admin-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin: 2rem auto;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
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
            <div class="ms-auto">
                <form action="?logout=1" method="POST" class="m-0">
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-box-arrow-right me-2"></i>Çıxış
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="admin-container">
            <h2 class="mb-4">Admin Panel</h2>
            <div class="alert alert-success">
                <h4>Təbriklər! 🎉</h4>
                <p>Broken Authentication boşluğunu tapdınız!</p>
                <p>Bu səhifəni görə bilirsinizsə, authentication bypass uğurlu olub.</p>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Təhlükəsizlik Boşluğunun İzahı</h5>
                    <p class="card-text">
                        Bu labda olan təhlükəsizlik boşluğu, yönləndirmədən sonra səhifə məzmununun hələ də göndərilməsidir. 
                        Server 302 Found cavabı ilə login.php-yə yönləndirmə etsə də, admin.php səhifəsinin məzmununu 
                        da göndərir. Bu, ciddi bir təhlükəsizlik boşluğudur və real tətbiqlərdə heç vaxt olmamalıdır.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 