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
    die(json_encode(['error' => "Qoşulma xətası: " . $e->getMessage()]));
}

// USERID cookie yoxlaması
if (!isset($_COOKIE['USERID'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

// Cookie-ni decode et və yoxla
$cookie_value = base64_decode($_COOKIE['USERID']);
if (!$cookie_value) {
    http_response_code(401);
    die(json_encode(['error' => 'Invalid cookie']));
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
$current_user = null;

// Admin və normal istifadəçiləri yoxla
foreach ($admin_users as $user) {
    if (md5($user['username'] . ':' . $user['password']) === $cookie_value) {
        $is_valid = true;
        $current_user = $user['username'];
        break;
    }
}

if (!$is_valid) {
    foreach ($users as $user) {
        if (md5($user['username'] . ':' . $user['password']) === $cookie_value) {
            $is_valid = true;
            $current_user = $user['username'];
            break;
        }
    }
}

if (!$is_valid) {
    http_response_code(401);
    die(json_encode(['error' => 'Invalid user']));
}

// POST request yoxlaması
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

// Test cavablarının olub-olmadığını yoxla
if (!isset($_POST['answers'])) {
    http_response_code(400);
    die(json_encode(['error' => 'No answers submitted']));
}

// İstifadəçinin əvvəlcədən test edib-etmədiyini yoxla
$stmt = $conn->prepare("SELECT id FROM test_submissions WHERE user_token = ?");
$stmt->execute([base64_encode($current_user)]);
if ($stmt->fetch()) {
    http_response_code(400);
    die(json_encode(['error' => 'Test already submitted']));
}

// Test cavablarını yoxla və bal hesabla
$score = 0;
$user_answers = [];

foreach ($_POST['answers'] as $index => $answer) {
    $stmt = $conn->prepare("SELECT correct_answer FROM test_questions WHERE id = ?");
    $stmt->execute([$index]);
    $correct = $stmt->fetchColumn();
    
    if ($answer === $correct) {
        $score++;
    }
    
    $user_answers[] = $answer;
}

// İstifadəçinin ID-sini tap
$user_id = null;
foreach ($users as $user) {
    if ($user['username'] === $current_user) {
        $user_id = $user['id'];
        break;
    }
}

if (!$user_id) {
    foreach ($admin_users as $user) {
        if ($user['username'] === $current_user) {
            $user_id = $user['id'];
            break;
        }
    }
}

// Test nəticəsini verilənlər bazasına əlavə et
try {
    $stmt = $conn->prepare("INSERT INTO test_submissions (user_token, user_ip, score, answers) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        base64_encode($current_user),
        $_SERVER['REMOTE_ADDR'],
        $score,
        json_encode($user_answers)
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => "Test tamamlandı! Sizin nəticəniz: $score/6",
        'score' => $score
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Test nəticəsi əlavə edilərkən xəta baş verdi']));
} 