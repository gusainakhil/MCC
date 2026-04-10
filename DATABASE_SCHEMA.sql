-- MCC Management System - SQL Script (Updated)
-- Generated: 2026-04-06

CREATE DATABASE IF NOT EXISTS mccbeatlebuddy_db;
USE mccbeatlebuddy_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS Mcc_penalty_impose;
DROP TABLE IF EXISTS Mcc_penalties;
DROP TABLE IF EXISTS Mcc_intensive_report_data;
DROP TABLE IF EXISTS Mcc_normal_report_data;
DROP TABLE IF EXISTS Mcc_parameters;
DROP TABLE IF EXISTS Mcc_reports;
DROP TABLE IF EXISTS Mcc_contract_details;
DROP TABLE IF EXISTS Mcc_train_information;
DROP TABLE IF EXISTS Mcc_users;
DROP TABLE IF EXISTS Mcc_users;
DROP TABLE IF EXISTS Mcc_stations;
DROP TABLE IF EXISTS Mcc_divisions;
DROP TABLE IF EXISTS Mcc_zones;

SET FOREIGN_KEY_CHECKS = 1;

-- 1) Zones
CREATE TABLE Mcc_zones (
    zone_id INT PRIMARY KEY AUTO_INCREMENT,
    zone_name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_zone_name (zone_name)
);

-- 2) Divisions
CREATE TABLE Mcc_divisions (
    division_id INT PRIMARY KEY AUTO_INCREMENT,
    division_name VARCHAR(120) NOT NULL,
    zone_id INT NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES Mcc_zones(zone_id),
    UNIQUE KEY uk_division_zone (division_name, zone_id),
    INDEX idx_division_name (division_name),
    INDEX idx_division_zone (zone_id)
);

-- 3) Stations
CREATE TABLE Mcc_stations (
    station_id INT PRIMARY KEY AUTO_INCREMENT,
    station_name VARCHAR(120) NOT NULL,
    division_id INT NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (division_id) REFERENCES Mcc_divisions(division_id),
    UNIQUE KEY uk_station_division (station_name, division_id),
    INDEX idx_station_name (station_name),
    INDEX idx_station_division (division_id)
);

-- 4) Users
CREATE TABLE Mcc_users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    user_name VARCHAR(150) NOT NULL UNIQUE,
    user_code VARCHAR(50) UNIQUE,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150),
    phone VARCHAR(20),
    designation VARCHAR(100),
    address TEXT,
    role ENUM('SUPER_ADMIN', 'ADMIN', 'ORG_ADMIN', 'ORG_USER', 'AUDITOR') NOT NULL DEFAULT 'ORG_ADMIN',
    station_id INT NOT NULL,
    start_date DATE,
    end_date DATE,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_by_user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES Mcc_stations(station_id),
    FOREIGN KEY (created_by_user_id) REFERENCES Mcc_users(user_id),
    INDEX idx_user_name (user_name),
    INDEX idx_user_station (station_id),
    INDEX idx_user_role (role),
    INDEX idx_user_email (email)
);


-- 7) Contract Details
CREATE TABLE Mcc_contract_details (
    contract_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    agreement_no VARCHAR(60) NOT NULL UNIQUE,
    agreement_date DATE NOT NULL,
    contractor_name VARCHAR(150) NOT NULL,
    train_no_count INT,
    train_name VARCHAR(150),
    station_id INT NOT NULL,
    amount DECIMAL(14, 2),
    no_of_years INT,
    contract_start_date DATE NOT NULL,
    contract_end_date DATE NOT NULL,
    status ENUM('Active', 'Expired', 'Suspended') DEFAULT 'Active',
    created_by_user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Mcc_users(user_id),
    FOREIGN KEY (station_id) REFERENCES Mcc_stations(station_id),
    FOREIGN KEY (created_by_user_id) REFERENCES Mcc_users(user_id),
    INDEX idx_contract_user (user_id),
    INDEX idx_contract_station (station_id),
    INDEX idx_contract_dates (contract_start_date, contract_end_date)
);

-- 8) Train Information
CREATE TABLE Mcc_train_information (
    train_info_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    train_no VARCHAR(50) NOT NULL,
    train_name VARCHAR(150) NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Mcc_users(user_id),
    UNIQUE KEY uk_user_train_no (user_id, train_no),
    INDEX idx_train_user (user_id),
    INDEX idx_train_no (train_no)
);

-- 9) Reports
CREATE TABLE Mcc_reports (
    report_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    report_name VARCHAR(120) NOT NULL UNIQUE,
    report_type ENUM('Normal Report', 'Intensive Report', 'Chemical Report', 'Machine Report', 'Attendance Report') NOT NULL,
    weight_percent DECIMAL(5, 2),
    description TEXT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Mcc_users(user_id),
    INDEX idx_report_user (user_id),
    INDEX idx_report_name (report_name),
    INDEX idx_report_type (report_type)
);

-- 10) Parameters (Report Assignment + Parameter Details)
CREATE TABLE Mcc_parameters (
    parameter_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    report_id INT NOT NULL,
    parameter_name VARCHAR(150) NOT NULL,
    category VARCHAR(150) NOT NULL,
    assigned_by_user_id INT,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Mcc_users(user_id),
    FOREIGN KEY (report_id) REFERENCES Mcc_reports(report_id),
    FOREIGN KEY (assigned_by_user_id) REFERENCES Mcc_users(user_id),
    UNIQUE KEY uk_user_report_parameter (user_id, report_id, parameter_name),
    INDEX idx_param_user (user_id),
    INDEX idx_param_report (report_id),
    INDEX idx_param_category (category)
);

-- 11) Normal Report Data
CREATE TABLE Mcc_normal_report_data (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    `value` DECIMAL(10, 2),
    parameter_id BIGINT NOT NULL,
    train_no VARCHAR(50) NOT NULL,
    token_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Mcc_users(user_id),
    FOREIGN KEY (parameter_id) REFERENCES Mcc_parameters(parameter_id),
    INDEX idx_normal_user (user_id),
    INDEX idx_normal_parameter (parameter_id),
    INDEX idx_normal_train_no (train_no),
    INDEX idx_normal_token (token_id)
);

-- 12) Intensive Report Data
CREATE TABLE Mcc_intensive_report_data (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    `value` DECIMAL(10, 2),
    parameter_id BIGINT NOT NULL,
    train_no VARCHAR(50) NOT NULL,
    token_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Mcc_users(user_id),
    FOREIGN KEY (parameter_id) REFERENCES Mcc_parameters(parameter_id),
    INDEX idx_intensive_user (user_id),
    INDEX idx_intensive_parameter (parameter_id),
    INDEX idx_intensive_train_no (train_no),
    INDEX idx_intensive_token (token_id)
);

-- 13) Penalties
CREATE TABLE Mcc_penalties (
    penalty_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    report_id INT NOT NULL,
    sr_annx_a1 VARCHAR(100),
    clause_no VARCHAR(100),
    item TEXT,
    penalty_amount DECIMAL(12, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Mcc_users(user_id),
    FOREIGN KEY (report_id) REFERENCES Mcc_reports(report_id),
    INDEX idx_penalty_user (user_id),
    INDEX idx_penalty_report (report_id),
    INDEX idx_penalty_clause (clause_no)
);

-- 14) Penalty Imposition Section
CREATE TABLE Mcc_penalty_impose (
    penalty_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    train_no VARCHAR(50) NOT NULL,
    coach_no VARCHAR(50) NOT NULL,
    `date` DATE NOT NULL,
    INDEX idx_penalty_impose_train (train_no),
    INDEX idx_penalty_impose_date (`date`)
);
