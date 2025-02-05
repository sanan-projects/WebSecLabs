<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$servername = "admin_db";
$username = "admin_user";
$password = "admin_pass";
$dbname = "admin_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection error']));
}

// Request body'yi al
$data = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));

// API endpoint'lerini kontrol et
if ($path_parts[0] !== 'api2' || $path_parts[1] !== 'user') {
    http_response_code(404);
    die(json_encode(['error' => 'Invalid endpoint']));
}

// API endpoint'leri
switch($method) {
    case 'POST':
        if ($path_parts[2] === 'register') {
            if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Etibarsız məlumat']);
                break;
            }

            // Input validasyonları
            if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $data['username'])) {
                http_response_code(400);
                echo json_encode(['error' => 'İstifadəçi adı 3-20 simvol arası və sadəcə hərf, rəqəm və alt çizgi daxildir']);
                break;
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Etibarsız email formatı']);
                break;
            }

            if (strlen($data['password']) < 6) {
                http_response_code(400);
                echo json_encode(['error' => 'Şifrə ən az 6 simvol olmalıdır']);
                break;
            }

            // Role değerini kontrol et, varsayılan olarak 1 (user)
            $role = isset($data['role']) ? intval($data['role']) : 1;

            try {
                // İstifadəçi yoxlaması
                $check = $conn->prepare("SELECT id FROM api_users WHERE username = :username OR email = :email");
                $check->bindParam(':username', $data['username']);
                $check->bindParam(':email', $data['email']);
                $check->execute();
                
                if ($check->rowCount() > 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Bu istifadəçi adı və ya email artıq istifadə olunur']);
                    break;
                }

                // İstifadəçini əlavə et
                $stmt = $conn->prepare("INSERT INTO api_users (username, email, password, role) VALUES (:username, :email, :password, :role)");
                $stmt->bindParam(':username', $data['username']);
                $stmt->bindParam(':email', $data['email']);
                $stmt->bindParam(':password', $data['password']);
                $stmt->bindParam(':role', $role);
                $stmt->execute();
                
                $userId = $conn->lastInsertId();
                
                echo json_encode([
                    'message' => 'İstifadəçi uğurla qeydiyyatdan keçdi',
                    'user' => [
                        'id' => $userId,
                        'username' => $data['username'],
                        'email' => $data['email'],
                        'role' => $role
                    ]
                ]);
            } catch(PDOException $e) {
                error_log($e->getMessage()); // Hatayı logla
                http_response_code(500);
                echo json_encode(['error' => 'Qeydiyyat uğursuz: ' . $e->getMessage()]);
            }
        }
        else if ($path_parts[2] === 'login') {
            if (!isset($data['username']) || !isset($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Çatışmayan məlumat']);
                break;
            }

            // Təhlükəsiz olmayan sorğu
            $sql = "SELECT * FROM api_users WHERE username = '" . $data['username'] . 
                   "' AND password = '" . $data['password'] . "'";
            
            try {
                $result = $conn->query($sql);
                $user = $result->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // Role değerini integer'a çevir
                    $role = intval($user['role']);
                    
                    echo json_encode([
                        'token' => base64_encode('exp:' . (time() + 3600)),
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'role' => $role, // Integer olarak gönder
                        'api_endpoints' => [
                            'update_email' => [
                                'url' => '/api2/user/update',
                                'method' => 'PUT',
                                'body' => ['email' => 'new@email.com']
                            ],
                            'update_username' => [
                                'url' => '/api2/user/update',
                                'method' => 'PUT',
                                'body' => ['username' => 'new_username']
                            ],
                            'update_password' => [
                                'url' => '/api2/user/' . $user['id'],
                                'method' => 'PUT',
                                'body' => ['password' => 'new_password']
                            ]
                        ]
                    ]);
                } else {
                    http_response_code(401);
                    echo json_encode(['error' => 'Yanlış istifadəçi adı və ya şifrə']);
                }
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Giriş uğursuz']);
            }
        }
        break;

    case 'PUT':
        if ($path_parts[2] === 'update') {
            // Authorization kontrolü
            if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Authorization required']);
                break;
            }

            // Email güncelleme
            if (isset($data['email'])) {
                // Email validasyonu
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Etibarsız email formatı']);
                    break;
                }

                $sql = "UPDATE api_users SET email = :email WHERE id = :id";
                try {
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':email', $data['email']);
                    $stmt->bindParam(':id', $userId);
                    $stmt->execute();
                    echo json_encode(['message' => 'Email yeniləndi']);
                } catch(PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Email yenilənməsi uğursuz']);
                }
            }
            // Username güncelleme
            else if (isset($data['username'])) {
                // Username validasyonu
                if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $data['username'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'İstifadəçi adı 3-20 simvol arası və sadəcə hərf, rəqəm və alt çizgi daxildir']);
                    break;
                }

                $sql = "UPDATE api_users SET username = :username WHERE id = :id";
                try {
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':username', $data['username']);
                    $stmt->bindParam(':id', $userId);
                    $stmt->execute();
                    echo json_encode(['message' => 'İstifadəçi adı yeniləndi']);
                } catch(PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'İstifadəçi adı yenilənməsi uğursuz']);
                }
            }
        }
        // Şifre güncelleme
        else if (is_numeric($path_parts[2])) {
            $userId = $path_parts[2];
            
            if (!isset($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Şifrə lazımdır']);
                break;
            }

            // Şifre validasyonu
            if (strlen($data['password']) < 6) {
                http_response_code(400);
                echo json_encode(['error' => 'Şifrə ən az 6 simvol olmalıdır']);
                break;
            }

            $sql = "UPDATE api_users SET password = :password WHERE id = :id";
            try {
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':password', $data['password']);
                $stmt->bindParam(':id', $userId);
                $stmt->execute();
                echo json_encode(['message' => 'Şifrə yeniləndi']);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Şifrə yenilənməsi uğursuz']);
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Etibarsız metod']);
        break;
}
?> 