<?php
header('Content-Type: application/json');

$servername = "db";
$username = "sqli_user";
$password = "sqli_pass";
$dbname = "sqli_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['error' => 'Database connection error']));
}

// JSON məlumatlarını al
$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';
$user_ip = $_SERVER['REMOTE_ADDR'];

// Sadə cavablar bazası
$responses = [
    'salam' => 'Salam! Sizə necə kömək edə bilərəm?',
    'necəsən' => 'Yaxşıyam, təşəkkür edirəm! Siz necəsiniz?',
    'təşəkkür' => 'Dəyməz! Başqa sualınız varsa, soruşa bilərsiniz.',
    'authentication nədir' => 'Authentication (kimlik doğrulama), istifadəçinin kimliyini təsdiqləmək prosesidir.',
    'broken authentication' => 'Broken Authentication, veb tətbiqlərdə kimlik doğrulama zəiflikləridir.',
    'sql injection' => 'SQL Injection, verilənlər bazasına icazəsiz müdaxilə etməyə imkan verən zəiflikdir.',
    'xss' => 'Cross-Site Scripting (XSS), veb səhifəyə zərərli JavaScript kodu yerləşdirməyə imkan verən zəiflikdir.'
];

// Cavabı hazırla
$response = 'Bağışlayın, bu sualı başa düşmədim. Təhlükəsizlik haqqında sual verə bilərsiniz.';

foreach ($responses as $key => $value) {
    if (stripos($message, $key) !== false) {
        $response = $value;
        break;
    }
}

// Mesajı verilənlər bazasına əlavə et - XSS üçün htmlspecialchars() istifadə etmirik
try {
    $sql = "INSERT INTO chat_messages (user_ip, message, response) VALUES (:user_ip, :message, :response)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':user_ip' => $user_ip,
        ':message' => $message,
        ':response' => $response
    ]);
} catch(PDOException $e) {
    error_log($e->getMessage());
}

// Cavabı JSON formatında göndər
echo json_encode(['response' => $response]); 