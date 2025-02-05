<?php
session_name('auth_session');
session_start();

// Admin yoxlaması
if (!isset($_SESSION['auth_user']) || $_SESSION['auth_user']['role'] !== 'admin') {
    header('Location: auth.php');
    exit();
}

$servername = "db";
$username = "sqli_user";
$password = "sqli_pass";
$dbname = "sqli_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Qoşulma xətası: " . $e->getMessage());
}

// AJAX sorğusu üçün yeni mesajları qaytaran endpoint
if(isset($_GET['action']) && $_GET['action'] === 'get_new_messages') {
    $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    
    $sql = "SELECT * FROM chat_messages WHERE id > :last_id ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':last_id' => $lastId]);
    $newMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($newMessages);
    exit;
}

// İlk yükləmə üçün bütün mesajları əldə et
$sql = "SELECT * FROM chat_messages ORDER BY created_at DESC";
$stmt = $conn->query($sql);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .chat-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .message-card {
            border-left: 4px solid #764ba2;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message-time {
            color: #6c757d;
            font-size: 0.8rem;
        }
        .message-ip {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .response {
            margin-top: 10px;
            padding-left: 20px;
            border-left: 2px solid #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="chat-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="bi bi-chat-dots me-2"></i>
                    Chat Mesajları
                </h2>
                <a href="auth.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-2"></i>Geri
                </a>
            </div>

            <div id="messages-container">
                <?php foreach ($messages as $message): ?>
                    <div class="message-card" data-message-id="<?php echo $message['id']; ?>">
                        <div class="d-flex justify-content-between">
                            <div class="message-ip">
                                <i class="bi bi-person me-2"></i>
                                <?php echo htmlspecialchars($message['user_ip']); ?>
                            </div>
                            <div class="message-time">
                                <i class="bi bi-clock me-1"></i>
                                <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
                            </div>
                        </div>
                        <div class="mt-2">
                            <strong>Sual:</strong> <?php echo $message['message']; ?>
                        </div>
                        <div class="response">
                            <strong>Cavab:</strong> <?php echo $message['response']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($messages)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Hələ heç bir chat mesajı yoxdur.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let lastMessageId = <?php echo !empty($messages) ? $messages[0]['id'] : 0; ?>;

        function checkNewMessages() {
            fetch(`admin_chat.php?action=get_new_messages&last_id=${lastMessageId}`)
                .then(response => response.json())
                .then(newMessages => {
                    if (newMessages.length > 0) {
                        const container = document.getElementById('messages-container');
                        
                        newMessages.reverse().forEach(message => {
                            if (message.id > lastMessageId) {
                                lastMessageId = message.id;
                                
                                const messageHtml = `
                                    <div class="message-card" data-message-id="${message.id}">
                                        <div class="d-flex justify-content-between">
                                            <div class="message-ip">
                                                <i class="bi bi-person me-2"></i>
                                                ${message.user_ip}
                                            </div>
                                            <div class="message-time">
                                                <i class="bi bi-clock me-1"></i>
                                                ${new Date(message.created_at).toLocaleString('az')}
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <strong>Sual:</strong> ${message.message}
                                        </div>
                                        <div class="response">
                                            <strong>Cavab:</strong> ${message.response}
                                        </div>
                                    </div>
                                `;
                                
                                // Yeni mesajı əvvələ əlavə et
                                container.insertAdjacentHTML('afterbegin', messageHtml);
                                
                                // Boş mesaj xəbərdarlığını gizlət
                                const emptyAlert = container.querySelector('.alert-info');
                                if (emptyAlert) {
                                    emptyAlert.remove();
                                }
                            }
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Hər 2 saniyədə yeni mesajları yoxla
        setInterval(checkNewMessages, 2000);
    </script>
</body>
</html> 