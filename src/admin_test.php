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

// Superadmin-i yoxla
$stmt = $conn->prepare("SELECT username, password FROM admin_users WHERE username = 'superadmin'");
$stmt->execute();
$superadmin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$superadmin || md5($superadmin['username'] . ':' . $superadmin['password']) !== $cookie_value) {
    header('HTTP/1.1 403 Forbidden');
    die('Bu səhifəyə yalnız superadmin daxil ola bilər!');
}

// Əgər login olunubsa, test nəticələrini əldə et
$sql = "SELECT * FROM test_submissions ORDER BY submission_date DESC";
$stmt = $conn->query($sql);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistika hesabla
$total_submissions = count($submissions);
$avg_score = 0;
$perfect_scores = 0;

if ($total_submissions > 0) {
    $total_score = array_sum(array_column($submissions, 'score'));
    $avg_score = round($total_score / $total_submissions, 2);
    $perfect_scores = count(array_filter($submissions, function($s) { return $s['score'] == 6; }));
}

// Çıxış
if (isset($_GET['logout'])) {
    setcookie('USERID', '', time() - 3600, '/');
    header('Location: sign-in.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Test Nəticələri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
        }
        .admin-container, .login-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin: 20px auto;
        }
        .login-container {
            max-width: 400px;
        }
        .stats-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #764ba2;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .score-badge {
            font-size: 1.1em;
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Admin Panel -->
        <div class="admin-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="bi bi-clipboard-data me-2"></i>
                    Test Nəticələri
                </h2>
                <a href="?logout=1" class="btn btn-outline-danger">
                    <i class="bi bi-box-arrow-right me-2"></i>Çıxış
                </a>
            </div>

            <!-- Statistika -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stats-card">
                        <h5>Ümumi İştirakçı</h5>
                        <h3><?php echo $total_submissions; ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h5>Ortalama Bal</h5>
                        <h3><?php echo $avg_score; ?>/6</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h5>Maksimum Bal Alanlar</h5>
                        <h3><?php echo $perfect_scores; ?></h3>
                    </div>
                </div>
            </div>

            <!-- Nəticələr Cədvəli -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>IP Ünvan</th>
                            <th>Bal</th>
                            <th>Faiz</th>
                            <th>Tarix</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <?php 
                                $percentage = ($submission['score'] / 6) * 100;
                                $badge_class = 'bg-danger';
                                if ($percentage >= 80) $badge_class = 'bg-success';
                                elseif ($percentage >= 60) $badge_class = 'bg-warning';
                            ?>
                            <tr>
                                <td>
                                    <i class="bi bi-person me-2"></i>
                                    <?php echo htmlspecialchars($submission['user_ip']); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badge_class; ?> score-badge">
                                        <?php echo $submission['score']; ?>/6
                                    </span>
                                </td>
                                <td><?php echo round($percentage, 1); ?>%</td>
                                <td>
                                    <i class="bi bi-clock me-1"></i>
                                    <?php echo date('d.m.Y H:i', strtotime($submission['submission_date'])); ?>
                                </td>
                                <td>
                                    <?php if ($percentage >= 60): ?>
                                        <span class="badge bg-success">Keçdi</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Kəsildi</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (empty($submissions)): ?>
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle me-2"></i>
                        Hələ heç bir test nəticəsi yoxdur.
                    </div>
                <?php endif; ?>
            </div>

            <div class="container mt-4">
                <h3>İstifadəçilər</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>İstifadəçi Adı</th>
                                <th>Email</th>
                                <th>Test Nəticəsi</th>
                                <th>Report Sayı</th>
                                <th>Əməliyyatlar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->query("SELECT u.*, 
                                (SELECT COUNT(*) FROM reports WHERE user_id = u.id) as report_count,
                                (SELECT score FROM test_submissions WHERE user_id = u.id ORDER BY submission_date DESC LIMIT 1) as last_score
                                FROM users u");
                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= $row['last_score'] ? $row['last_score'] . '/6' : 'Yoxdur' ?></td>
                                <td><?= $row['report_count'] ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="showUserDetails(<?= $row['id'] ?>)">
                                        Ətraflı
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- User Details Modal -->
            <div class="modal fade" id="userDetailsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">İstifadəçi Detalları</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="userDetailsContent">
                            <!-- AJAX ilə doldurulacaq -->
                        </div>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
            <script>
            async function showUserDetails(userId) {
                const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
                const content = document.getElementById('userDetailsContent');
                
                // Test suallarını və cavablarını əlavə et
                const questions = [
                    {
                        question: "chmod 754 myfile.txt hansı icazələri tətbiq edir?",
                        correct_answer: "B",
                        answer_text: "Sahib: oxu, yaz, icra; Qrup: oxu, icra; Digərləri: oxu"
                    },
                    {
                        question: "SQL Injection hücumunun qarşısını almaq üçün ən effektiv üsul hansıdır?",
                        correct_answer: "C",
                        answer_text: "Prepared Statements istifadəsi"
                    },
                    {
                        question: "HTTP və HTTPS arasındakı əsas fərq nədir?",
                        correct_answer: "C",
                        answer_text: "Şifrələnmiş əlaqə"
                    },
                    {
                        question: "find / -perm -4000 2>/dev/null Linux komandası hansı məqsədlə istifadə olunur?",
                        correct_answer: "B",
                        answer_text: "Sistem üzərində SUID (root hüququ olan) faylları tapmaq üçün"
                    },

                    {
                        question: "CSRF (Cross-Site Request Forgery) hücumunun qarşısını almaq üçün nə istifadə olunur?",
                        correct_answer: "C",
                        answer_text: "CSRF Token"
                    },
                    {
                        question: "nmap -sU -p 1-100 --script vuln --open -Pn -T4 -oA scan_results target.com Nmap skan sorğusu nə edir?",
                        correct_answer: "D",
                        answer_text: "UDP portları (1-100) skan edərək açıq portları aşkar edir və mümkün zəiflikləri analiz edir"
                    }
                ];
                

                try {
                    const response = await fetch(`get_user_details.php?id=${userId}`);
                    const data = await response.json();
                    
                    let html = `
                        <h6>Test Nəticələri</h6>
                        <div class="test-results mb-4">`;
                    
                    if (data.tests.length === 0) {
                        html += '<div class="alert alert-info">Test nəticəsi yoxdur</div>';
                    } else {
                        data.tests.forEach(test => {
                            html += `
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <span class="badge bg-primary me-2">${test.username}</span>
                                                <small class="text-muted">${test.submission_date}</small>
                                            </div>
                                            <span class="badge bg-${test.score >= 4 ? 'success' : 'danger'}">${test.score}/6</span>
                                        </div>
                                        <div class="test-details">`;
                                        
                                        try {
                                            const answers = JSON.parse(test.answers);
                                            answers.forEach((answer, index) => {
                                                const isCorrect = answer === questions[index].correct_answer;
                                                html += `
                                                    <div class="answer-item mb-2 p-2 ${isCorrect ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10'}">
                                                        <div class="question fw-bold">${index + 1}. ${questions[index].question}</div>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="answer">
                                                                <i class="bi bi-${isCorrect ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'} me-2"></i>
                                                                Cavab: ${answer}
                                                            </div>
                                                            ${!isCorrect ? `
                                                                <div class="correct-answer">
                                                                    <small class="text-muted">Düzgün cavab: ${answer} → ${questions[index].answer_text}</small>
                                                                </div>
                                                            ` : ''}
                                                        </div>
                                                    </div>`;
                                            });
                                        } catch(e) {
                                            console.error('Error parsing answers:', e);
                                            html += '<div class="alert alert-warning">Cavablar oxuna bilmədi: ' + e.message + '</div>';
                                        }
                                        
                                        html += `</div></div>`;
                                    });
                                }
                                
                                html += `</div>
                                    <h6 class="mt-4">Reportlar</h6>
                                    <div class="reports-container">`;
                                
                                // Mövcud report hissəsi
                                if (data.reports.length === 0) {
                                    html += '<div class="alert alert-info">Report yoxdur</div>';
                                } else {
                                    data.reports.forEach(report => {
                                        html += `
                                            <div class="card mb-2">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <span class="badge bg-info">${report.lab_type}</span>
                                                        <small class="text-muted">${report.created_at}</small>
                                                    </div>
                                                    <p class="mb-1">${report.message}</p>`;
                                                    
                                                    if (report.screenshot) {
                                                        try {
                                                            const screenshots = JSON.parse(report.screenshot);
                                                            screenshots.forEach(screenshot => {
                                                                html += `<img src="/uploads/${screenshot}" class="img-fluid mt-2 mb-2" style="max-width: 300px;">`;
                                                            });
                                                        } catch(e) {
                                                            html += `<img src="/uploads/${report.screenshot}" class="img-fluid mt-2" style="max-width: 300px;">`;
                                                        }
                                                    }
                                                    
                                                    html += `
                                                            </div>
                                                        </div>
                                                    `;
                                                });
                                            }
                                            
                                            html += `</div>`;
                                            
                                            content.innerHTML = html;
                                            modal.show();
                                    } catch (error) {
                                        alert('Məlumatları əldə edərkən xəta baş verdi');
                                        console.error('Error:', error);
                                    }
                                }
                                </script>
        </div>
    </div>
</body>
</html> 