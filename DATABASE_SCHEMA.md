# MCC Database Flow Guide (Developer Friendly)

This document explains the current database flow in simple steps so the next developer can understand the system quickly.

---

## 1) Big Picture Flow

1. Master hierarchy is created first:
   Zone -> Division -> Station
2. Users are created and linked to a station.
3. Train master data is stored per user.
4. Reports are created per user (Normal, Intensive, Chemical, Machine, Attendance) with billing weightage.
5. Report parameters are defined and assigned.
6. Normal and Intensive report entry data is stored separately.
7. Organisation PDF view groups parameters report-wise for printing.
8. Penalty-related data is stored in two parts:
   - Detailed penalty records
   - Penalty impose section entries

---

## 2) Current Tables and Purpose

### A) Master Tables

- Mcc_zones
  - Stores zone master.
  - Key: zone_id

- Mcc_divisions
  - Stores division master.
  - Linked to Mcc_zones via zone_id.

- Mcc_stations
  - Stores station master.
  - Linked to Mcc_divisions via division_id.

### B) User and Access Context

- Mcc_users
  - Main user table (admin/org/auditor roles).
  - Linked to station via station_id.
  - Self-reference supported via created_by_user_id.

### C) Operational Tables

- Mcc_train_information
  - Train master per user.
  - Fields: train_no, train_name.

- Mcc_contract_details
  - Contract records.
  - Contains agreement and amount/time period details.

- Mcc_reports
  - Report definitions per user.
  - report_type: Normal Report, Intensive Report, Chemical Report, Machine Report, Attendance Report.
  - weight_percent is report-level billing weightage.

- Mcc_parameters
  - Parameter setup and assignment details.
  - Includes category (stored as text), report mapping, assignment metadata.

### D) Transactional Report Data

- Mcc_normal_report_data
  - Stores normal report entries.
  - Important fields: user_id, value, parameter_id, train_no, token_id.

- Mcc_intensive_report_data
  - Stores intensive report entries.
  - Important fields: user_id, value, parameter_id, train_no, token_id.

### E) Penalty Tables

- Mcc_penalties
  - Detailed penalty records.
  - Includes sr_annx_a1, clause_no, item, penalty_amount.
  - Linked by user_id and report_id.

- Mcc_penalty_impose
  - Dedicated penalty section entry table.
  - Fields: penalty_id, train_no, coach_no, date.

---

## 3) End-to-End Data Lifecycle

1. Create Zone.
2. Create Division under Zone.
3. Create Station under Division.
4. Create User and assign station.
5. Add user trains in Mcc_train_information.
6. Create report in Mcc_reports with report_type and weight_percent.
7. Configure report parameters in Mcc_parameters (category is text so report-specific values are allowed).
8. Save report run values:
   - Normal report values in Mcc_normal_report_data
   - Intensive report values in Mcc_intensive_report_data
9. If required, store penalties:
   - Detailed penalty in Mcc_penalties
   - Penalty section entry in Mcc_penalty_impose

---

## 4) Table Relationship Summary

1. Mcc_zones -> Mcc_divisions -> Mcc_stations
2. Mcc_stations -> Mcc_users
3. Mcc_users -> Mcc_train_information
4. Mcc_users -> Mcc_reports
5. Mcc_reports -> Mcc_parameters
6. Mcc_users + Mcc_parameters -> report data tables
7. Mcc_users + Mcc_reports -> Mcc_penalties

---

## 5) Developer Notes

1. Use Mcc_reports.weight_percent for billing-level report weight.
2. Use Mcc_parameters for parameter metadata and assignment context.
3. Use Mcc_normal_report_data and Mcc_intensive_report_data for actual captured values.
4. Use Mcc_penalty_impose as the quick penalty section table in UI flow.
5. Keep enum values aligned with frontend dropdowns.

---

## 6) Suggested Validation Rules

1. weight_percent should be between 0 and 100.
2. token_id should be unique per report run (enforce in service layer if not DB-level).
3. train_no should exist in Mcc_train_information for the same user before report save.
4. penalty date should not be in invalid future range (based on business rule).

---

## 7) Quick Onboarding for Next Developer

If you are starting fresh, follow this order in backend APIs:

1. Zone API
2. Division API
3. Station API
4. User API
5. Train API
6. Report API
7. Parameter API
8. Report Submit APIs (Normal/Intensive and future Chemical/Machine ingestion endpoints)
9. Penalty APIs (Detailed + Impose)

This sequence avoids foreign key and workflow confusion.

---

Last Updated: 10 April 2026
