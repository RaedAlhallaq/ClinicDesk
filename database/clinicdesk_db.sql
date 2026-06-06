CREATE DATABASE IF NOT EXISTS clinicdesk_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE clinicdesk_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS prescriptions;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS specializations;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120)  NOT NULL,
  email       VARCHAR(180)  NOT NULL UNIQUE,
  password    VARCHAR(255)  NOT NULL,
  role        ENUM('admin','doctor','patient') NOT NULL DEFAULT 'patient',
  phone       VARCHAR(20)   DEFAULT NULL,
  avatar      VARCHAR(255)  DEFAULT NULL,
  is_active   TINYINT(1)    NOT NULL DEFAULT 1,
  created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_role (role),
  INDEX idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (name, email, password, role, is_active)
VALUES (
  'Admin',
  'admin@clinic.local',
  '$2y$12$zzsSyXVypeo4qgoQVBz96Or5FWCWNMzvNI9meMohTSYehjZa.fIbm',
  'admin',
  1
);

CREATE TABLE specializations (
  id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO specializations (name) VALUES
  ('General Practice'), ('Cardiology'), ('Dermatology'),
  ('Pediatrics'), ('Orthopedics'), ('Neurology'),
  ('Ophthalmology'), ('ENT'), ('Psychiatry');

CREATE TABLE doctors (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id            INT UNSIGNED NOT NULL UNIQUE,
  specialization_id  INT UNSIGNED NOT NULL,
  bio                TEXT         DEFAULT NULL,
  consultation_fee   DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  available_days     VARCHAR(50)  NOT NULL DEFAULT 'Sun,Mon,Tue,Wed,Thu',
  INDEX idx_doctors_specialization (specialization_id),
  CONSTRAINT fk_doctors_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_doctors_specialization
    FOREIGN KEY (specialization_id) REFERENCES specializations(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE appointments (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id    INT UNSIGNED NOT NULL,
  doctor_id     INT UNSIGNED NOT NULL,
  appt_date     DATE         NOT NULL,
  appt_time     TIME         NOT NULL,
  status        ENUM('pending','confirmed','completed','cancelled')
                             NOT NULL DEFAULT 'pending',
  reason        VARCHAR(255) DEFAULT NULL,
  doctor_notes  TEXT         DEFAULT NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY no_double_booking (doctor_id, appt_date, appt_time),
  INDEX idx_appointments_patient (patient_id),
  INDEX idx_appointments_date_status (appt_date, status),
  CONSTRAINT fk_appointments_patient
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_appointments_doctor
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prescriptions (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  appointment_id  INT UNSIGNED NOT NULL UNIQUE,
  diagnosis       TEXT         NOT NULL,
  medications     TEXT         NOT NULL,
  notes           TEXT         DEFAULT NULL,
  file_path       VARCHAR(255) DEFAULT NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_prescriptions_appointment
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
