# MCC Management System - Database Schema

SQL schema for Railway Mechanized Cleaning Coach Management System

---

## Database Setup

### Create Database
```sql
CREATE DATABASE IF NOT EXISTS mcc_railway_db;
USE mcc_railway_db;
```

---

## Table Definitions

### 1. Users Table
```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,  -- Store hashed password
    role ENUM('Admin', 'Auditor', 'Supervisor') NOT NULL DEFAULT 'Auditor',
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);
```

**Sample Data**:
```sql
INSERT INTO users (username, email, password, role) VALUES
('john_doe', 'john@example.com', 'hashed_password_1', 'Admin'),
('jane_smith', 'jane@example.com', 'hashed_password_2', 'Auditor'),
('mike_johnson', 'mike@example.com', 'hashed_password_3', 'Auditor'),
('sarah_davis', 'sarah@example.com', 'hashed_password_4', 'Supervisor');
```

---

### 2. Zones Table
```sql
CREATE TABLE zones (
    zone_id INT PRIMARY KEY AUTO_INCREMENT,
    zone_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_zone_name (zone_name)
);
```

**Sample Data**:
```sql
INSERT INTO zones (zone_name, description) VALUES
('Zone A', 'Northern Railway Zone'),
('Zone B', 'Central Railway Zone'),
('Zone C', 'Eastern Railway Zone'),
('Zone D', 'Western Railway Zone');
```

---

### 3. Stations Table
```sql
CREATE TABLE stations (
    station_id INT PRIMARY KEY AUTO_INCREMENT,
    station_name VARCHAR(100) NOT NULL UNIQUE,
    zone_id INT NOT NULL,
    location VARCHAR(255),
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES zones(zone_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_zone_id (zone_id),
    INDEX idx_station_name (station_name)
);
```

**Sample Data**:
```sql
INSERT INTO stations (station_name, zone_id, location) VALUES
('Central Station', 1, 'New Delhi'),
('Northern Station', 1, 'Chandigarh'),
('Eastern Station', 2, 'Kolkata'),
('South Station', 1, 'Bangalore'),
('West Station', 4, 'Mumbai');
```

---

### 4. Contractors Table
```sql
CREATE TABLE contractors (
    contractor_id INT PRIMARY KEY AUTO_INCREMENT,
    contractor_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_contractor_name (contractor_name),
    INDEX idx_status (status)
);
```

**Sample Data**:
```sql
INSERT INTO contractors (contractor_name, email, phone) VALUES
('ABC Cleaning Services', 'info@abc-cleaning.com', '9999999999'),
('XYZ Maintenance Ltd', 'contact@xyz-maintenance.com', '8888888888'),
('Premium Clean Co.', 'hello@premium-clean.com', '7777777777');
```

---

### 5. Contracts Table
```sql
CREATE TABLE contracts (
    contract_id INT PRIMARY KEY AUTO_INCREMENT,
    agreement_no VARCHAR(50) NOT NULL UNIQUE,
    agreement_date DATE NOT NULL,
    contractor_id INT NOT NULL,
    zone_id INT NOT NULL,
    station_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    contract_value DECIMAL(12, 2),
    terms_conditions TEXT,
    status ENUM('Active', 'Expired', 'Suspended') DEFAULT 'Active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (contractor_id) REFERENCES contractors(contractor_id),
    FOREIGN KEY (zone_id) REFERENCES zones(zone_id),
    FOREIGN KEY (station_id) REFERENCES stations(station_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_agreement_no (agreement_no),
    INDEX idx_contractor (contractor_id),
    INDEX idx_zone (zone_id),
    INDEX idx_station (station_id),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date)
);
```

**Sample Data**:
```sql
INSERT INTO contracts (agreement_no, agreement_date, contractor_id, zone_id, station_id, start_date, end_date)
VALUES
('AGR-2026-001', '2026-01-15', 1, 1, 1, '2026-01-15', '2026-12-31'),
('AGR-2026-002', '2026-02-01', 2, 2, 3, '2026-02-01', '2026-11-30'),
('AGR-2026-003', '2026-01-20', 3, 3, 2, '2026-01-20', '2026-10-31');
```

---

### 6. Inspections Table
```sql
CREATE TABLE inspections (
    inspection_id INT PRIMARY KEY AUTO_INCREMENT,
    inspection_date DATE NOT NULL,
    zone_id INT NOT NULL,
    station_id INT NOT NULL,
    depot_name VARCHAR(100),
    supervisor_name VARCHAR(100) NOT NULL,
    auditor_id INT NOT NULL,
    train_no VARCHAR(50) NOT NULL,
    time_work_started TIME NOT NULL,
    time_work_completed TIME NOT NULL,
    total_coaches_in_rake INT NOT NULL,
    coaches_attended INT NOT NULL,
    coaches_not_attended INT GENERATED ALWAYS AS (total_coaches_in_rake - coaches_attended) STORED,
    completion_percentage DECIMAL(5, 2) GENERATED ALWAYS AS ((coaches_attended / total_coaches_in_rake) * 100) STORED,
    comments TEXT,
    status ENUM('Completed', 'In Progress', 'Suspended') DEFAULT 'Completed',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES zones(zone_id),
    FOREIGN KEY (station_id) REFERENCES stations(station_id),
    FOREIGN KEY (auditor_id) REFERENCES users(user_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_inspection_date (inspection_date),
    INDEX idx_zone (zone_id),
    INDEX idx_station (station_id),
    INDEX idx_auditor (auditor_id),
    INDEX idx_train_no (train_no),
    INDEX idx_status (status)
);
```

**Sample Data**:
```sql
INSERT INTO inspections (
    inspection_date, zone_id, station_id, depot_name, supervisor_name, 
    auditor_id, train_no, time_work_started, time_work_completed, 
    total_coaches_in_rake, coaches_attended
) VALUES
('2026-03-28', 1, 1, 'Central Depot', 'Rajesh Kumar', 1, 'TR-001', '08:00', '16:30', 12, 10),
('2026-03-27', 2, 3, 'Northern Depot', 'Amit Sharma', 2, 'TR-002', '07:30', '14:15', 10, 10),
('2026-03-26', 3, 2, 'Eastern Depot', 'Priya Singh', 3, 'TR-003', '09:00', '14:20', 10, 7),
('2026-03-25', 1, 4, 'South Depot', 'Vikram Patel', 1, 'TR-004', '08:30', '15:45', 12, 11),
('2026-03-24', 2, 5, 'West Depot', 'Neha Gupta', 4, 'TR-005', '07:00', '11:50', 8, 6);
```

---

## Relationships Diagram

```
users
  |
  ├─→ zones (created_by)
  |
  ├─→ stations (created_by)
  |
  ├─→ contracts (created_by, auditor_id)
  |
  └─→ inspections (auditor_id, created_by)

zones
  |
  ├─→ stations (zone_id)
  |
  ├─→ contracts (zone_id)
  |
  └─→ inspections (zone_id)

stations
  |
  ├─→ contracts (station_id)
  |
  └─→ inspections (station_id)

contractors
  |
  └─→ contracts (contractor_id)
```

---

## Indexes for Performance

```sql
-- Create additional indexes for better query performance
CREATE INDEX idx_contracts_date_range ON contracts(start_date, end_date);
CREATE INDEX idx_inspections_date_range ON inspections(inspection_date, auditor_id);
CREATE INDEX idx_inspections_zone_station ON inspections(zone_id, station_id, inspection_date);
CREATE INDEX idx_contracts_zone_station ON contracts(zone_id, station_id, status);
```

---

## Views for Common Queries

### Active Contracts View
```sql
CREATE VIEW active_contracts AS
SELECT 
    c.contract_id,
    c.agreement_no,
    con.contractor_name,
    z.zone_name,
    s.station_name,
    c.start_date,
    c.end_date,
    DATEDIFF(c.end_date, CURDATE()) as days_remaining
FROM contracts c
JOIN contractors con ON c.contractor_id = con.contractor_id
JOIN zones z ON c.zone_id = z.zone_id
JOIN stations s ON c.station_id = s.station_id
WHERE c.status = 'Active' AND c.end_date >= CURDATE();
```

### Inspection Summary View
```sql
CREATE VIEW inspection_summary AS
SELECT 
    inspection_date,
    z.zone_name,
    s.station_name,
    u.username as auditor_name,
    COUNT(*) as total_inspections,
    SUM(coaches_attended) as total_coaches_attended,
    AVG(completion_percentage) as avg_completion
FROM inspections i
JOIN zones z ON i.zone_id = z.zone_id
JOIN stations s ON i.station_id = s.station_id
JOIN users u ON i.auditor_id = u.user_id
GROUP BY inspection_date, z.zone_id, s.station_id;
```

---

## SQL Queries for Common Operations

### Get Dashboard Statistics
```sql
SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM contracts WHERE status = 'Active') as active_contracts,
    (SELECT COUNT(*) FROM inspections) as total_inspections,
    (SELECT COUNT(*) FROM stations) as total_stations,
    (SELECT AVG(completion_percentage) FROM inspections WHERE inspection_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as avg_completion_rate;
```

### Get Recent Inspections
```sql
SELECT 
    i.inspection_id,
    i.inspection_date,
    z.zone_name,
    s.station_name,
    i.train_no,
    i.coaches_attended,
    u.username as auditor,
    i.status
FROM inspections i
JOIN zones z ON i.zone_id = z.zone_id
JOIN stations s ON i.station_id = s.station_id
JOIN users u ON i.auditor_id = u.user_id
ORDER BY i.inspection_date DESC
LIMIT 10;
```

### Generate Report by Date Range
```sql
SELECT 
    i.inspection_date,
    z.zone_name,
    s.station_name,
    con.contractor_name,
    i.train_no,
    i.total_coaches_in_rake,
    i.coaches_attended,
    CONCAT(
        TIMESTAMPDIFF(HOUR, 
            TIMESTAMP(CONCAT(i.inspection_date, ' ', i.time_work_started)),
            TIMESTAMP(CONCAT(i.inspection_date, ' ', i.time_work_completed))
        ), 'h'
    ) as work_duration,
    ROUND(i.completion_percentage, 2) as completion_percentage
FROM inspections i
JOIN zones z ON i.zone_id = z.zone_id
JOIN stations s ON i.station_id = s.station_id
JOIN contracts c ON c.zone_id = z.zone_id AND c.station_id = s.station_id
JOIN contractors con ON c.contractor_id = con.contractor_id
WHERE i.inspection_date BETWEEN ? AND ?
ORDER BY i.inspection_date DESC;
```

---

## User Roles & Permissions

| Feature | Admin | Auditor | Supervisor |
|---------|-------|---------|-----------|
| Create User | ✅ | ❌ | ❌ |
| Manage Zones | ✅ | ❌ | ❌ |
| Manage Stations | ✅ | ❌ | ❌ |
| Create Contract | ✅ | ❌ | ❌ |
| Create Inspection | ✅ | ✅ | ❌ |
| View Reports | ✅ | ✅ | ✅ |
| Export Data | ✅ | ✅ | ✅ |
| View Dashboard | ✅ | ✅ | ✅ |

---

## Data Integrity Constraints

1. **Foreign Key Constraints**: Prevent orphaned records
2. **Unique Constraints**: Ensure no duplicate entries
3. **Date Constraints**: End date > Start date
4. **Enum Constraints**: Only valid statuses allowed
5. **Generated Columns**: Auto-calculation of percentages

---

## Backup & Maintenance

### Weekly Backup Script
```bash
#!/bin/bash
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

### Check Table Health
```sql
CHECK TABLE zones, stations, users, contracts, inspections;
ANALYZE TABLE zones, stations, users, contracts, inspections;
OPTIMIZE TABLE zones, stations, users, contracts, inspections;
```

---

## Security Notes

1. **Password**: Always hash before storing
   ```php
   password_hash($password, PASSWORD_BCRYPT)
   ```

2. **SQL Injection Prevention**: Use prepared statements
   ```php
   $stmt = $connection->prepare("SELECT * FROM users WHERE email = ?");
   $stmt->bind_param("s", $email);
   ```

3. **Access Control**: Check user role before operations
4. **Audit Trail**: Log all changes to sensitive tables
5. **Data Validation**: Validate on both frontend & backend

---

## Creating Sample Data Script

```sql
-- Insert all sample data
USE mcc_railway_db;

START TRANSACTION;

-- Users
INSERT INTO users (username, email, password, role) VALUES 
('john_doe', 'john@example.com', '$2y$10$...', 'Admin'),
('jane_smith', 'jane@example.com', '$2y$10$...', 'Auditor');

-- Zones
INSERT INTO zones (zone_name) VALUES 
('Zone A'), ('Zone B'), ('Zone C'), ('Zone D');

-- Stations
INSERT INTO stations (station_name, zone_id) VALUES 
('Central Station', 1), ('Northern Station', 1), ('Eastern Station', 2);

-- Contractors
INSERT INTO contractors (contractor_name, email, phone) VALUES 
('ABC Cleaning Services', 'abc@example.com', '9999999999'),
('XYZ Maintenance Ltd', 'xyz@example.com', '8888888888');

-- Contracts
INSERT INTO contracts (agreement_no, agreement_date, contractor_id, zone_id, station_id, start_date, end_date) VALUES 
('AGR-2026-001', '2026-01-15', 1, 1, 1, '2026-01-15', '2026-12-31');

-- Inspections
INSERT INTO inspections (inspection_date, zone_id, station_id, depot_name, supervisor_name, auditor_id, train_no, time_work_started, time_work_completed, total_coaches_in_rake, coaches_attended) VALUES 
('2026-03-28', 1, 1, 'Central Depot', 'Rajesh Kumar', 1, 'TR-001', '08:00', '16:30', 12, 10);

COMMIT;
```

---

## Reference

- **Total Tables**: 6 (users, zones, stations, contractors, contracts, inspections)
- **Total Fields**: ~80
- **Primary Keys**: 6
- **Foreign Keys**: 14
- **Indexes**: 20+
- **Views**: 2+

---

**Last Updated**: April 1, 2026

---
