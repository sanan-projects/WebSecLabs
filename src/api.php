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
if ($path_parts[0] !== 'api' || $path_parts[1] !== 'user') {
    http_response_code(404);
    die(json_encode(['error' => 'Xətalı endpoint']));
}

// API endpoint'leri
switch($method) {
    case 'POST':
        if ($path_parts[2] === 'register') {
            if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Çatışmayan məlumat']);
                break;
            }

            // Kasıtlı olarak güvensiz sorgu - SQL Injection'a açık
            $sql = "INSERT INTO api_users (username, email, password) 
                    VALUES ('" . $data['username'] . "', '" . $data['email'] . "', '" . $data['password'] . "')";
            
            try {
                $conn->query($sql);
                $userId = $conn->lastInsertId();
                echo json_encode([
                    'message' => 'İstifadəçi uğurla qeydiyyatdan keçdi',
                    'user' => [
                        'id' => $userId,
                        'username' => $data['username'],
                        'email' => $data['email']
                    ]
                ]);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Uğursuz qeydiyyat']);
            }
        }
        else if ($path_parts[2] === 'login') {
            if (!isset($data['username']) || !isset($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Çatışmayan məlumat']);
                break;
            }

            // Kasıtlı olarak güvensiz sorgu
            $sql = "SELECT * FROM api_users WHERE username = '" . $data['username'] . 
                   "' AND password = '" . $data['password'] . "'";
            
            try {
                $result = $conn->query($sql);
                $user = $result->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // API documentation
                    echo json_encode([
                        'token' => base64_encode('exp:' . (time() + 3600)),
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'api_endpoints' => [
                            'update_email' => [
                                'url' => '/api/user/update',
                                'method' => 'PUT',
                                'body' => ['email' => 'new@email.com']
                            ],
                            'update_username' => [
                                'url' => '/api/user/update',
                                'method' => 'PUT',
                                'body' => ['username' => 'new_username']
                            ],
                            'update_password' => [
                                'url' => '/api/user/' . $user['id'],
                                'method' => 'PUT',
                                'body' => [
                                    'current_password' => 'current',
                                    'new_password' => 'new'
                                ]
                            ]
                        ]
                    ]);
                } else {
                    http_response_code(401);
                    echo json_encode(['error' => 'Xətalı istifadəçi adı və ya şifrə']);
                }
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Uğursuz giriş']);
            }
        }
        break;

    case 'PUT':
        // Authorization kontrolü
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['error' => 'authentication required']);
            break;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $tokenData = explode(':', base64_decode($token));
        $username = $tokenData[0] ?? '';

        if ($path_parts[2] === 'update') {
            if (isset($data['email'])) {
                $sql = "UPDATE api_users SET email = '" . $data['email'] . 
                       "' WHERE username = '" . $username . "'";
            } 
            else if (isset($data['username'])) {
                $sql = "UPDATE api_users SET username = '" . $data['new_username'] . 
                       "' WHERE username = '" . $username . "'";
            }
            else {
                http_response_code(400);
                echo json_encode(['error' => 'Xətalı güncəlləmə müraciəti']);
                break;
            }
            
            try {
                $conn->query($sql);
                echo json_encode(['message' => 'Güncəlləmə uğurla tamamlandı']);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Güncəlləmə uğursuz']);
            }
        }
        else if (is_numeric($path_parts[2])) { // /api/user/{id}
            $userId = $path_parts[2];
            
            if (!isset($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Şifre gerekli']);
                break;
            }

            // SQL sorgusunu güncelle - username parametresi varsa onu da güncelle
            $sql = "UPDATE api_users SET password = '" . $data['password'] . "'";
            
            // Eğer username parametresi varsa, onu da güncelle
            if (isset($data['username'])) {
                $sql .= ", username = '" . $data['username'] . "'";
            }
            
            $sql .= " WHERE id = " . $userId;
            
            try {
                $stmt = $conn->query($sql);
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['message' => 'Güncəlləmə uğurla tamamlandı']);
                } else {
                    http_response_code(401);
                    echo json_encode(['error' => 'Güncəlləmə uğursuz']);
                }
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Güncəlləmə uğursuz']);
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Invalid method']);
        break;
}
?> 