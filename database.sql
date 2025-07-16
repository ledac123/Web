CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50),
  password VARCHAR(100),
  balance INT DEFAULT 0
);

CREATE TABLE accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(50),
  info TEXT,
  price INT,
  status ENUM('available', 'sold') DEFAULT 'available'
);
