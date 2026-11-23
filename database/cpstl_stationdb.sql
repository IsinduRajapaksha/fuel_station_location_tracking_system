DROP DATABASE IF EXISTS fualStations;

CREATE DATABASE fualStations;

USE fualStations;	

-- Fuel Stations Table
DROP TABLE IF EXISTS fuel_stations;

CREATE TABLE fuel_stations (
    acct_no INT(6) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    district VARCHAR(100) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    address TEXT,
    contact_phone VARCHAR(20),
    status ENUM('Open', 'Closed', 'Maintenance') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Depo Table
CREATE TABLE depo(
    depo_id INT PRIMARY KEY AUTO_INCREMENT,
    depo_name VARCHAR(50) NOT NULL,
     latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL

)DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO depo (depo_name,latitude,longitude) 
VALUES 
('Anuradhapura Depo','8.35051','80.41177'),
('Badulla ','6.97907','81.06129'),
('Batticaloa','7.725495','81.700111'),
('Galle','6.034564','80.235876'),
('Haputhale','6.767531','80.955270'),
('Kks','9.8142335','80.037239'),
('Kotagala','6.928049','80.607967'),
('Kurunegala','7.476719','80.371508'),
('Mathara','5.952364','80.5450415'),
('Kolonnawa','6.929777','79.8872997'),
('Muthurajawela','7.0204070','79.875106'),
('Peradeniya','7.25948','80.595774');

-- Depo by station Table
CREATE TABLE depo_by_station(
     acct_no INT(6) NOT NULL,
     depo_id INT NOT NULL,
     
	FOREIGN KEY (acct_no) REFERENCES fuel_stations(acct_no) ON DELETE CASCADE,
    FOREIGN KEY (depo_id) REFERENCES depo(depo_id) ON DELETE CASCADE
)DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;




-- 2. Fuel Quantity Table
CREATE TABLE fuel_quantity (
    id INT PRIMARY KEY AUTO_INCREMENT,
    station_acct_no INT(6) NOT NULL,
    fuel_type VARCHAR(50) NOT NULL,     -- e.g., Petrol, Diesel
    year INT NOT NULL,
    load_liters INT DEFAULT 0,          -- Fuel loaded
    available_liters INT DEFAULT 0,     -- Fuel remaining
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (station_acct_no) REFERENCES fuel_stations(acct_no) ON DELETE CASCADE,

    UNIQUE KEY unique_station_fuel_period (station_acct_no, fuel_type, year)
)DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Create users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',
    full_name VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'
);


-- Create activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create sessions table for better session management
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

select * from depo;
select * from depo_by_station;


-- SET FOREIGN_KEY_CHECKS = 0;
-- DROP TABLE depo;
-- DROP TABLE depo_by_station;
-- SET FOREIGN_KEY_CHECKS = 1;
DROP DATABASE IF EXISTS fualStations;

CREATE DATABASE fualStations;

USE fualStations;	

-- Fuel Stations Table
DROP TABLE IF EXISTS fuel_stations;

CREATE TABLE fuel_stations (
    acct_no INT(6) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    district VARCHAR(100) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    address TEXT,
    contact_phone VARCHAR(20),
    status ENUM('Open', 'Closed', 'Maintenance') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Depo Table
CREATE TABLE depo(
    depo_id INT PRIMARY KEY AUTO_INCREMENT,
    depo_name VARCHAR(50) NOT NULL,
     latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL

)DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO depo (depo_name,latitude,longitude) 
VALUES 
('Anuradhapura Depo','8.35051','80.41177'),
('Badulla Depo','6.97907','81.06129'),
('Batticaloa Depo','7.725495','81.700111'),
('Galle Depo','6.034564','80.235876'),
('Haputhale Depo','6.767531','80.955270'),
('Kankasanthure Depo','9.8142335','80.037239'),
('Kotagala Depo','6.928049','80.607967'),
('Kurunegala Depo','7.476719','80.371508'),
('Mathara Depo','5.952364','80.5450415'),
('Kolonnawa Terminal','6.929777','79.8872997'),
('Muthurajawela Terminal','7.0204070','79.875106'),
('Peradeniya Depo','7.25948','80.595774'),
('05 Valve', 0.00000000, 0.00000000),
('Customer Collections', 0.00000000, 0.00000000);

-- Depo by station Table
CREATE TABLE depo_by_station(
     acct_no INT(6) NOT NULL,
     depo_id INT NOT NULL,
     
	FOREIGN KEY (acct_no) REFERENCES fuel_stations(acct_no) ON DELETE CASCADE,
    FOREIGN KEY (depo_id) REFERENCES depo(depo_id) ON DELETE CASCADE
)DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;




-- 2. Fuel Quantity Table
CREATE TABLE fuel_quantity (
    id INT PRIMARY KEY AUTO_INCREMENT,
    station_acct_no INT(6) NOT NULL,
    fuel_type VARCHAR(50) NOT NULL,     -- e.g., Petrol, Diesel
    year INT NOT NULL,
    load_liters INT DEFAULT 0,          -- Fuel loaded
    available_liters INT DEFAULT 0,     -- Fuel remaining
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (station_acct_no) REFERENCES fuel_stations(acct_no) ON DELETE CASCADE,

    UNIQUE KEY unique_station_fuel_period (station_acct_no, fuel_type, year)
)DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Create users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',
    full_name VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'
);


-- Create activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create sessions table for better session management
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

select * from depo;
select * from depo_by_station;


-- SET FOREIGN_KEY_CHECKS = 0;
-- DROP TABLE depo;
-- DROP TABLE depo_by_station;
-- SET FOREIGN_KEY_CHECKS = 1;
