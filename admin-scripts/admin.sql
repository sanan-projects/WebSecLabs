-- Admin database üçün cədvəllər
CREATE TABLE test_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    correct_answer CHAR(1) NOT NULL
);

CREATE TABLE test_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_token VARCHAR(255) NOT NULL,
    user_ip VARCHAR(45),
    score INT NOT NULL,
    answers TEXT,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Test suallarını əlavə edək
INSERT INTO test_questions (question, option_a, option_b, option_c, option_d, correct_answer) VALUES
('chmod 754 myfile.txt hansı icazələri tətbiq edir?', 
 'Sahib: oxu, icra; Qrup: oxu, yaz, icra; Digərləri: heç bir icazə yoxdur', 
 'Sahib: oxu, yaz, icra; Qrup: oxu, icra; Digərləri: oxu', 
 'Sahib: oxu, yaz; Qrup: oxu; Digərləri: yaz, icra', 
 'Sahib: oxu, yaz, icra; Qrup: oxu, yaz; Digərləri: icra', 
 'B'),

('SQL Injection hücumunun qarşısını almaq üçün ən effektiv üsul hansıdır?', 
 'Firewall istifadəsi', 
 'Antivirus proqramı', 
 'Prepared Statements istifadəsi', 
 'Şifrələmə', 
 'C'),

('HTTP və HTTPS arasındakı əsas fərq nədir?', 
 'Sürət fərqi', 
 'Port nömrəsi', 
 'Şifrələnmiş əlaqə', 
 'Protokol versiyası', 
 'C'),

('find / -perm -4000 2>/dev/null Linux komandası hansı məqsədlə istifadə olunur?', 
 'Bütün açıq portları tapmaq üçün', 
 'Sistem üzərində SUID (root hüququ olan) faylları tapmaq üçün', 
 'Sistemdə çalışan istifadəçiləri aşkar etmək üçün', 
 'Linux Kernel versiyasını yoxlamaq üçün', 
 'B'),

('CSRF (Cross-Site Request Forgery) hücumunun qarşısını almaq üçün nə istifadə olunur?', 
 'SSL Sertifikatı', 
 'Anti-virus', 
 'CSRF Token', 
 'Firewall', 
 'C'),

('nmap -sU -p 1-100 --script vuln --open -Pn -T4 -oA scan_results target.com Nmap skan sorğusu nə edir?', 
 'Firewall və IDS-ləri aşmaq üçün şifrələnmiş trafikdən istifadə edir', 
 'DNS protokolunda sorğular göndərərək subdomenləri tapmağa çalışır', 
 'Yalnız TCP portlarını (1-100) skan edərək zəiflikləri yoxlayır', 
 'UDP portları (1-100) skan edərək açıq portları aşkar edir və mümkün zəiflikləri analiz edir', 
 'D');

-- Admin istifadəçiləri üçün cədvəl
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    email VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin hesabını əlavə et
INSERT INTO admin_users (username, password, email) VALUES
('admin', 'admin123321', 'admin@example.com'),
('superadmin', 'Sadmin123321', 'superadminadmin@example.com');

-- İstifadəçilər cədvəli
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE api_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    email VARCHAR(100) UNIQUE,
    role INT DEFAULT 1
);

INSERT INTO api_users (username, password, email, role) VALUES
('admin', 'admin54321', 'admin@example.com', 2),
('john', 'password123', 'john@example.com', 1),
('alice', 'alice123', 'alice@example.com', 1),
('bob', 'bob123', 'bob@example.com', 1),
('test', 'test123', 'test@example.com', 1);

-- Reports table
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    screenshot VARCHAR(255),
    created_at DATETIME NOT NULL,
    lab_type VARCHAR(50) NOT NULL DEFAULT 'unknown',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Test submissions table-a user_id əlavə et
ALTER TABLE test_submissions 
ADD COLUMN user_id INT,
ADD FOREIGN KEY (user_id) REFERENCES users(id);

-- Mövcud test_submissions məlumatları üçün user_id-ni yenilə
UPDATE test_submissions ts 
SET user_id = (
    SELECT id 
    FROM users u 
    WHERE MD5(CONCAT(u.username, ':', u.password)) = ts.user_token
    LIMIT 1
);
