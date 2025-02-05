<?php
// Session-u sil, çünki artıq USERID cookie istifadə edirik
// session_start();

// user_id session yoxlamasını sil və USERID cookie yoxlaması əlavə et
if (isset($_COOKIE['USERID'])) {
    header('Location: index.php');
    exit;
}

// Database connection
$servername = "admin_db"; // Birbaşa container adını istifadə et
$username = "admin_user";
$password = "admin_pass";
$dbname = "admin_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Qoşulma xətası: " . $e->getMessage());
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Bütün sahələri doldurun';
    } elseif ($password !== $confirm_password) {
        $error = 'Şifrələr uyğun gəlmir';
    } elseif (strlen($password) < 8) {
        $error = 'Şifrə ən az 8 simvol olmalıdır';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Düzgün email daxil edin';
    } else {
        // İstifadəçi adı və email mövcudluğunu yoxla
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Bu istifadəçi adı və ya email artıq istifadə olunur';
        } else {
            // Şifrəni hash-lə və istifadəçini əlavə et
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            
            try {
                $stmt->execute([$username, $email, $hashed_password]);
                
                // Qeydiyyatdan sonra avtomatik giriş et
                $cookie_value = base64_encode(md5($username . ':' . $hashed_password));
                setcookie('USERID', $cookie_value, time() + 86400, '/'); // 24 saat
                
                $success = 'Qeydiyyat uğurla tamamlandı!';
                header('Location: index.php'); // Birbaşa index-ə yönləndir
                exit;
            } catch(PDOException $e) {
                $error = 'Xəta baş verdi. Yenidən cəhd edin.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qeydiyyat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            margin: 20px auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <h2 class="text-center mb-4">Qeydiyyat</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">İstifadəçi adı</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Şifrə</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Şifrəni təsdiqlə</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Qeydiyyatdan keç</button>
                </div>

                <div class="text-center mt-3">
                    Hesabınız var? <a href="sign-in.php">Daxil olun</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 