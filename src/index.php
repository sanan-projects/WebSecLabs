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
$is_superadmin = false;
$current_user = '';

// Admin və normal istifadəçiləri yoxla
foreach ($admin_users as $user) {
    if (md5($user['username'] . ':' . $user['password']) === $cookie_value) {
        $is_valid = true;
        $current_user = $user['username'];
        if ($user['username'] === 'superadmin') {
            $is_superadmin = true;
        }
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
    <title>Labs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            text-align: center;
        }
        h1 {
            color: #4a4a4a;
            margin-bottom: 2rem;
            font-weight: bold;
        }
        .lab-links {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            margin-top: 2rem;
        }
        .lab-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 1rem;
            border-radius: 15px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            font-weight: 500;
        }
        .lab-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.3);
            color: white;
        }
        .description {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        /* Center container for test button */
        .text-center .lab-btn {
            float: none;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Web Security Laboratoriyaları</h1>
            <div class="d-flex gap-2">
                <?php if ($is_superadmin): ?>
                    <a href="admin_test.php" class="btn btn-primary">
                        <i class="bi bi-gear-fill me-2"></i>
                        Admin Panel
                    </a>
                <?php endif; ?>
                <form action="sign-in.php?logout=1" method="POST" class="m-0">
                    <button type="submit" class="btn btn-outline-danger">Çıxış</button>
                </form>
            </div>
        </div>

        <!-- Test button - centered -->
        <div class="text-center mb-5">
            <a href="test.php" class="lab-btn" style="display: inline-block; padding: 1rem 4rem;">Test Lab</a>
        </div>

        <div class="lab-container">
            <div class="lab-links">
                <a href="xss1.php" class="lab-btn">XSS Lab 1</a>
                <a href="xss2.php" class="lab-btn">XSS Lab 2</a>
                <a href="xss3.php" class="lab-btn">XSS Lab 3</a>
                <a href="xss4.php" class="lab-btn">XSS Lab 4</a>
                <a href="xss5.php" class="lab-btn">XSS Lab 5</a>
                <a href="xss6.php" class="lab-btn">XSS Lab 6</a>
                <a href="sql1.php" class="lab-btn">SQL Injection Lab 1</a>
                <a href="sql2.php" class="lab-btn">SQL Injection Lab 2</a>
                <a href="sql3.php" class="lab-btn">SQL Injection Lab 3</a>
                <a href="sql4.php" class="lab-btn">SQL Injection Lab 4</a>
                <a href="login.php" class="lab-btn">Broken Authentication Lab 1</a>
                <a href="auth.php" class="lab-btn">Broken Authentication Lab 2</a>
                <a href="api_lab.php" class="lab-btn">API Security Lab 1</a>
                <a href="api_lab2.php" class="lab-btn">API Security Lab 2</a>
            </div>
        </div>
    </div>
</body>
</html> 