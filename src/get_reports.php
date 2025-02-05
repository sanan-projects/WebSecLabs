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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'] ?? '';
    $lab_type = $_POST['lab_type'] ?? 'unknown';
    $screenshots = [];

    // Multiple screenshots yüklənməsi
    if (isset($_FILES['screenshots']) && is_array($_FILES['screenshots']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $upload_path = __DIR__ . '/uploads/';
        
        // Əgər uploads qovluğu yoxdursa yarat və icazələri təyin et
        if (!file_exists($upload_path)) {
            if (!mkdir($upload_path, 0777, true)) {
                die(json_encode(['error' => 'Uploads qovluğu yaradıla bilmədi']));
            }
            chmod($upload_path, 0777);
        }
        
        foreach ($_FILES['screenshots']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['screenshots']['error'][$key] === UPLOAD_ERR_OK) {
                $filename = $_FILES['screenshots']['name'][$key];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $newname = uniqid() . '_' . $key . '.' . $ext;
                    $full_path = $upload_path . $newname;
                    
                    if (move_uploaded_file($tmp_name, $full_path)) {
                        chmod($full_path, 0666); // Fayl icazələrini təyin et
                        $screenshots[] = $newname;
                    } else {
                        $upload_error = error_get_last();
                        die(json_encode([
                            'error' => 'Fayl yüklənə bilmədi: ' . ($upload_error['message'] ?? 'Unknown error')
                        ]));
                    }
                }
            }
        }
    }

    // Reportu database-ə əlavə et
    try {
        $sql = "INSERT INTO reports (user_id, message, screenshot, lab_type, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $current_user['id'], 
            $message, 
            !empty($screenshots) ? json_encode($screenshots) : null,
            $lab_type
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Report uğurla göndərildi!',
            'uploaded_files' => $screenshots
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        die(json_encode([
            'error' => 'Report əlavə edilərkən xəta baş verdi: ' . $e->getMessage()
        ]));
    }
    exit;
}
?> 