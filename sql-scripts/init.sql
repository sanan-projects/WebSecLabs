DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    email VARCHAR(100) UNIQUE,
    role INT DEFAULT 1
);

INSERT INTO users (username, password, email, role) VALUES
('admin', '123456', 'admin@example.com', 2),
('john', 'password123', 'john@example.com', 1),
('moderator', 'mod123', 'mod@example.com', 2),
('alice', 'alice123', 'alice@example.com', 1),
('bob', 'bob123', 'bob@example.com', 1),
('test_user', 'test456', 'test@example.com', 1),
('manager', 'manager789', 'manager@example.com', 2),
('editor', 'editor321', 'editor@example.com', 2);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    description TEXT
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(200),
    price DECIMAL(10,2),
    description TEXT,
    stock INT,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

INSERT INTO categories (name, description) VALUES
('Elektronika', 'Elektronika məhsulları kateqoriyası'),
('Kitablar', 'Kitablar kateqoriyası'),
('Geyim', 'Geyim məhsulları kateqoriyası');

INSERT INTO products (category_id, name, price, description, stock) VALUES
(1, 'Noutbuk', 15000.00, 'Yüksək performanslı noutbuk', 10),
(1, 'Ağıllı Telefon', 8000.00, 'Son model ağıllı telefon', 15),
(2, 'Python Proqramlaşdırma', 120.00, 'Python proqramlaşdırma kitabı', 50),
(2, 'SQL Öyrənmə Təlimatı', 90.00, 'SQL verilənlər bazası kitabı', 30),
(3, 'T-Shirt', 150.00, 'Pambıq t-shirt', 100),
(3, 'Cins Şalvar', 300.00, 'Mavi cins şalvar', 45);

CREATE TABLE images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100),
    description TEXT,
    path VARCHAR(255),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Nümunə şəkillər
INSERT INTO images (title, description, path) VALUES
('Yağışlı yol', 'Şam meşəsi arasından keçən yağışlı asfalt yol', '/images/1'),
('Dumanlı meşə', 'Səhər dumanına bürünmüş şam ağacları', '/images/2'),
('Qürub buludları', 'Qızılı-bənövşəyi rənglərlə bəzənmiş axşam səması', '/images/3'),
('Göl kənarı ev', 'Meşə və göl kənarında yerləşən işıqlı dağ evi', '/images/4'),
('Dağ gölü', 'Əzəmətli dağlar arasında yerləşən güzgü kimi göl', '/images/5'),
('Gecə şəhəri', 'Mavi və qırmızı işıqlarla bəzənmiş göydələnlər', '/images/6'),
('Qış günbatımı', 'Qarla örtülmüş təpələr üzərində firuzəyi-narıncı səma', '/images/7'),
('Qaya kanyonu', 'Antilop kanyonunun dalğalı qırmızı qaya formaları', '/images/8'),
('Qırmızı georgin', 'Tünd yaşıl fonda açılmış qırmızı georgin çiçəyi', '/images/9'),
('LED maska', 'Qaranlıqda mavi işıqlı LED maskada insan silueti', '/images/10');

CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_ip VARCHAR(45),
    message TEXT,
    response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
); 