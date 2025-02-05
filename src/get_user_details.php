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
$stmt = $conn->prepare("SELECT id, username, password FROM admin_users");
$stmt->execute();
$admin_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Normal istifadəçilərdə yoxla
$stmt = $conn->prepare("SELECT id, username, password FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$is_valid = false;
$current_user = null;

// Admin və normal istifadəçiləri yoxla
foreach ($admin_users as $user) {
    if (md5($user['username'] . ':' . $user['password']) === $cookie_value) {
        $is_valid = true;
        $current_user = $user;
        break;
    }
}

if (!$is_valid) {
    foreach ($users as $user) {
        if (md5($user['username'] . ':' . $user['password']) === $cookie_value) {
            $is_valid = true;
            $current_user = $user;
            break;
        }
    }
}

if (!$is_valid) {
    http_response_code(401);
    die(json_encode(['error' => 'Invalid user']));
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    die(json_encode(['error' => 'User ID tələb olunur']));
}

$userId = (int)$_GET['id'];

// Debug üçün
error_log("Getting test results for user_id: " . $userId);

// Test nəticələrini al
$sql = "
    SELECT 
        ts.*,
        CASE 
            WHEN u.username IS NOT NULL THEN u.username
            WHEN a.username IS NOT NULL THEN a.username
            ELSE FROM_BASE64(ts.user_token)
        END as username
    FROM test_submissions ts
    LEFT JOIN users u ON FROM_BASE64(ts.user_token) = u.username
    LEFT JOIN admin_users a ON FROM_BASE64(ts.user_token) = a.username
    WHERE FROM_BASE64(ts.user_token) IN (
        SELECT username FROM users WHERE id = ?
        UNION
        SELECT username FROM admin_users WHERE id = ?
    )
    ORDER BY ts.submission_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute([$userId, $userId]);
$tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug məlumatları
error_log("User ID: " . $userId);
error_log("Found tests: " . count($tests));

// Test suallarını al
$stmt = $conn->prepare("SELECT * FROM test_questions ORDER BY id");
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Test nəticələrini format et
foreach ($tests as &$test) {
    $test['submission_date'] = date('d.m.Y H:i', strtotime($test['submission_date']));
    
    if ($test['answers']) {
        $userAnswers = json_decode($test['answers'], true);
        $test['detailed_answers'] = [];
        
        foreach ($userAnswers as $index => $answer) {
            if (isset($questions[$index])) {
                $question = $questions[$index];
                $correctAnswer = '';
                
                // Düzgün cavabı tap
                if ($question['correct_answer'] === 'A') $correctAnswer = $question['option_a'];
                else if ($question['correct_answer'] === 'B') $correctAnswer = $question['option_b'];
                else if ($question['correct_answer'] === 'C') $correctAnswer = $question['option_c'];
                else if ($question['correct_answer'] === 'D') $correctAnswer = $question['option_d'];
                
                $test['detailed_answers'][] = [
                    'question' => $question['question'],
                    'user_answer' => $answer,
                    'correct_answer' => $correctAnswer,
                    'is_correct' => $answer === $question['correct_answer']
                ];
            }
        }
    }
}

// Report-ları al
$stmt = $conn->prepare("
    SELECT * FROM reports 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Report tarixlərini format et
foreach ($reports as &$report) {
    $report['created_at'] = date('d.m.Y H:i', strtotime($report['created_at']));
}

// Debug məlumatları
error_log("Tests count: " . count($tests));
error_log("Tests data: " . print_r($tests, true));

// JSON cavabını göndər
header('Content-Type: application/json');
echo json_encode([
    'tests' => $tests,
    'reports' => $reports
]); 