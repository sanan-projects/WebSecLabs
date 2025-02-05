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
    header('HTTP/1.1 302 Found');
    header('Location: sign-in.php');
    exit;
}

$message = '';
$test_completed = false;

// Test tamamlanıb-tamamlanmadığını yoxla
$stmt = $conn->prepare("SELECT id FROM test_submissions WHERE user_token = ?");
$stmt->execute([base64_encode($current_user)]);
if ($stmt->fetch()) {
    $test_completed = true;
}

// Test cavablarının yoxlanması
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['answers']) && !$test_completed) {
    $score = 0;
    $user_answers = [];  // İstifadəçinin cavablarını saxlamaq üçün array
    
    // Hər sualın cavabını yoxla
    foreach ($_POST['answers'] as $index => $answer) {
        $stmt = $conn->prepare("SELECT correct_answer FROM test_questions WHERE id = ?");
        $stmt->execute([$index + 1]);
        $correct = $stmt->fetchColumn();
        
        if ($answer === $correct) {
            $score++;
        }
        
        $user_answers[] = $answer;  // İstifadəçinin cavabını array-ə əlavə et
    }
    
    // Test nəticəsini verilənlər bazasına əlavə et
    $stmt = $conn->prepare("INSERT INTO test_submissions (user_token, user_ip, score, answers) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        base64_encode($current_user),
        $_SERVER['REMOTE_ADDR'],
        $score,
        json_encode($user_answers)  // Cavabları JSON formatında saxla
    ]);

    $message = "Test tamamlandı! Sizin nəticəniz: $score/6";
    $test_completed = true;
}

// Sualları əldə et
$stmt = $conn->query("SELECT * FROM test_questions ORDER BY id");
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Təhlükəsizlik Testi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .test-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 800px;
        }
        .question {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 5px;
        }
        .options {
            margin-top: 15px;
        }
        .option {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-container">
            <h2 class="text-center mb-4">Təhlükəsizlik Testi</h2>
            
            <div class="mb-4 text-center">
                <h4>Xoş gəldiniz, <?php echo htmlspecialchars($current_user); ?>!</h4>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success text-center">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($test_completed): ?>
                <div class="alert alert-info text-center">
                    Siz artıq bu testi tamamlamısınız.
                </div>
            <?php else: ?>
                <form id="testForm" method="POST" action="get_tests.php">
                    <?php foreach ($questions as $q): ?>
                        <div class="question">
                            <h5><?php echo htmlspecialchars($q['question']); ?></h5>
                            <div class="options">
                                <div class="option">
                                    <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="A" required>
                                    <label><?php echo htmlspecialchars($q['option_a']); ?></label>
                                </div>
                                <div class="option">
                                    <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="B">
                                    <label><?php echo htmlspecialchars($q['option_b']); ?></label>
                                </div>
                                <div class="option">
                                    <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="C">
                                    <label><?php echo htmlspecialchars($q['option_c']); ?></label>
                                </div>
                                <div class="option">
                                    <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="D">
                                    <label><?php echo htmlspecialchars($q['option_d']); ?></label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">Testi Tamamla</button>
                    </div>
                </form>

                <script>
                document.getElementById('testForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    fetch('get_tests.php', {
                        method: 'POST',
                        body: new FormData(this)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                        } else {
                            alert(data.message);
                            location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Xəta baş verdi. Yenidən cəhd edin.');
                    });
                });
                </script>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 