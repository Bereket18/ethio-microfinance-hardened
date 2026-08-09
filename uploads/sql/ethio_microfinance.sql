-- sql/setup.sql
-- Complete Database Setup for Ethio Microfinance System

CREATE DATABASE IF NOT EXISTS ethio_microfinance;
USE ethio_microfinance;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    email VARCHAR(100),
    role VARCHAR(20) DEFAULT 'customer',
    account_balance DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Customer Information Table
CREATE TABLE customer_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    national_id VARCHAR(20),
    occupation VARCHAR(50),
    monthly_income DECIMAL(10,2),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Loans Table
CREATE TABLE loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    amount DECIMAL(10,2),
    interest_rate DECIMAL(5,2) DEFAULT 12.5,
    status VARCHAR(20) DEFAULT 'pending',
    application_date DATE,
    approval_date DATE NULL,
    due_date DATE NULL,
    remaining_balance DECIMAL(10,2),
    reason TEXT,
    approved_by INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Transactions Table
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type VARCHAR(20),
    amount DECIMAL(10,2),
    description TEXT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reference_number VARCHAR(50),
    status VARCHAR(20) DEFAULT 'completed',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Login Attempts Table
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    ip_address VARCHAR(45),
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE
);

-- Audit Log Table
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100),
    details TEXT,
    ip_address VARCHAR(45),
    log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert Sample Users (Passwords are plaintext for educational purposes)
INSERT INTO users (username, password, email, role, account_balance) VALUES 
('admin', 'admin123', 'admin@ethiomf.com', 'admin', 10000.00),
('alemu', 'password', 'alemu@email.com', 'customer', 5000.00),
('betty', 'betty123', 'betty@email.com', 'customer', 3000.00),
('chala', 'chala456', 'chala@email.com', 'customer', 7000.00),
('dawit', 'dawit789', 'dawit@email.com', 'customer', 4500.00),
('eden', 'eden321', 'eden@email.com', 'customer', 2500.00),
('fitsum', 'fitsum654', 'fitsum@email.com', 'customer', 6000.00),
('genet', 'genet987', 'genet@email.com', 'customer', 8000.00);

-- Insert Customer Information
INSERT INTO customer_info (user_id, full_name, phone, address, national_id, occupation, monthly_income) VALUES
(1, 'Admin User', '0911000000', 'Addis Ababa, Ethiopia', 'AA100001', 'Administrator', 15000.00),
(2, 'Alemu Getachew', '0912000001', 'Addis Ababa, Ethiopia', 'AA100002', 'Teacher', 8000.00),
(3, 'Betty Hailu', '0912000002', 'Addis Ababa, Ethiopia', 'AA100003', 'Nurse', 9000.00),
(4, 'Chala Fikre', '0912000003', 'Addis Ababa, Ethiopia', 'AA100004', 'Engineer', 12000.00),
(5, 'Dawit Mekonnen', '0912000004', 'Addis Ababa, Ethiopia', 'AA100005', 'Accountant', 10000.00),
(6, 'Eden Tesfaye', '0912000005', 'Addis Ababa, Ethiopia', 'AA100006', 'Doctor', 18000.00),
(7, 'Fitsum Alemayehu', '0912000006', 'Addis Ababa, Ethiopia', 'AA100007', 'Lawyer', 14000.00),
(8, 'Genet Assefa', '0912000007', 'Addis Ababa, Ethiopia', 'AA100008', 'Architect', 11000.00);

-- Insert Sample Loans
INSERT INTO loans (user_id, amount, interest_rate, status, application_date, approval_date, due_date, remaining_balance, reason) VALUES
(2, 10000.00, 12.5, 'approved', CURDATE(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 10000.00, 'Business expansion'),
(3, 5000.00, 12.5, 'pending', CURDATE(), NULL, NULL, 5000.00, 'Medical expenses'),
(4, 15000.00, 12.5, 'approved', CURDATE(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH), 15000.00, 'Home renovation'),
(5, 8000.00, 12.5, 'pending', CURDATE(), NULL, NULL, 8000.00, 'Education fees'),
(6, 20000.00, 12.5, 'approved', CURDATE(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 18 MONTH), 20000.00, 'Business startup');

-- Insert Sample Transactions
INSERT INTO transactions (user_id, type, amount, description, transaction_date, reference_number) VALUES
(2, 'deposit', 5000.00, 'Initial deposit', NOW(), 'TXN001'),
(2, 'withdrawal', 1000.00, 'ATM withdrawal', NOW(), 'TXN002'),
(3, 'deposit', 3000.00, 'Salary deposit', NOW(), 'TXN003'),
(4, 'deposit', 7000.00, 'Business revenue', NOW(), 'TXN004'),
(5, 'deposit', 4500.00, 'Freelance payment', NOW(), 'TXN005');

-- Insert Login Attempts
INSERT INTO login_attempts (username, ip_address, success) VALUES
('admin', '127.0.0.1', TRUE),
('alemu', '127.0.0.1', TRUE),
('guest', '192.168.1.100', FALSE),
('admin', '192.168.1.101', FALSE);

-- Insert Audit Log Entries
INSERT INTO audit_log (user_id, action, details, ip_address) VALUES
(1, 'login', 'Admin logged in successfully', '127.0.0.1'),
(1, 'user_create', 'Created user: dawit', '127.0.0.1'),
(2, 'loan_application', 'Applied for loan of 10000.00', '127.0.0.1');