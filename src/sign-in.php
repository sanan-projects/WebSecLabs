<?php
session_start();

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

// Çıxış
if (isset($_GET['logout'])) {
    setcookie('USERID', '', time() - 3600, '/');
    session_destroy();
    header('Location: sign-in.php');
    exit;
}

// Əgər USERID cookie varsa, index səhifəsinə yönləndir
if (isset($_COOKIE['USERID'])) {
    // Cookie-ni decode et və yoxla
    $cookie_value = base64_decode($_COOKIE['USERID']);
    
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
    
    if ($is_valid) {
        header('Location: index.php');
        exit;
    } else {
        // Cookie etibarsızdırsa, sil
        setcookie('USERID', '', time() - 3600, '/');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Bütün sahələri doldurun';
    } else {
        // Əvvəlcə admin cədvəlində yoxla
        $stmt = $conn->prepare("SELECT id, username, password FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['password'] === $password) { // Admin şifrəsi hash edilməyib
            $cookie_value = base64_encode(md5($user['username'] . ':' . $user['password']));
            setcookie('USERID', $cookie_value, time() + 86400, '/'); // 24 saat
            header('Location: index.php');
            exit;
        } else {
            // Admin deyilsə, normal istifadəçilərdə yoxla
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $cookie_value = base64_encode(md5($user['username'] . ':' . $user['password']));
                setcookie('USERID', $cookie_value, time() + 86400, '/'); // 24 saat
                header('Location: index.php');
                exit;
            } else {
                $error = 'Yanlış istifadəçi adı və ya şifrə';
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
    <title>Daxil ol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
            margin: 20px auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">Daxil ol</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">İstifadəçi adı</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Şifrə</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Daxil ol</button>
                </div>

                <div class="text-center mt-3">
                    Hesabınız yoxdur? <a href="register.php">Qeydiyyatdan keçin</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 