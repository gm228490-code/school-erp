-- =====================================================================
--  School ERP - Database Schema (MySQL 5.7+ / MariaDB 10.3+)
--  Engine: InnoDB, Charset: utf8mb4
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- System
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
  `key`       VARCHAR(100) NOT NULL PRIMARY KEY,
  `value`     TEXT NULL,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sequences (
  name        VARCHAR(50) NOT NULL PRIMARY KEY,
  next_value  INT UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(60) NOT NULL,
  slug        VARCHAR(60) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  is_system   TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
  id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug    VARCHAR(80) NOT NULL UNIQUE,
  module  VARCHAR(50) NOT NULL,
  name    VARCHAR(100) NOT NULL,
  INDEX idx_perm_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
  role_id       INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id             INT UNSIGNED NOT NULL,
  name                VARCHAR(120) NOT NULL,
  username            VARCHAR(60) NOT NULL UNIQUE,
  email               VARCHAR(150) NULL UNIQUE,
  phone               VARCHAR(30) NULL,
  password            VARCHAR(255) NOT NULL,
  photo               VARCHAR(255) NULL,
  status              ENUM('active','inactive') NOT NULL DEFAULT 'active',
  lang                VARCHAR(5) NOT NULL DEFAULT 'en',
  theme               VARCHAR(10) NOT NULL DEFAULT 'light',
  last_login          DATETIME NULL,
  last_ip             VARCHAR(45) NULL,
  password_changed_at DATETIME NULL,
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_status (status),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(150) NULL,
  ip         VARCHAR(45) NOT NULL,
  success    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_la_ip (ip, created_at),
  INDEX idx_la_user (username, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NULL,
  action     VARCHAR(50) NOT NULL,
  module     VARCHAR(50) NOT NULL,
  record_id  VARCHAR(50) NULL,
  details    TEXT NULL,
  ip         VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_user (user_id),
  INDEX idx_audit_module (module),
  INDEX idx_audit_created (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Academic structure
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS academic_sessions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(30) NOT NULL UNIQUE,
  start_date  DATE NOT NULL,
  end_date    DATE NOT NULL,
  is_current  TINYINT(1) NOT NULL DEFAULT 0,
  status      ENUM('active','closed') NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS departments (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS designations (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  department_id INT UNSIGNED NULL,
  UNIQUE KEY uq_designation (name, department_id),
  CONSTRAINT fk_desig_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teachers (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id          INT UNSIGNED NULL UNIQUE,
  employee_no      VARCHAR(30) NOT NULL UNIQUE,
  name             VARCHAR(120) NOT NULL,
  father_name      VARCHAR(120) NULL,
  gender           ENUM('male','female','other') NOT NULL DEFAULT 'male',
  dob              DATE NULL,
  cnic             VARCHAR(20) NULL UNIQUE,
  phone            VARCHAR(30) NULL,
  email            VARCHAR(150) NULL,
  address          TEXT NULL,
  photo            VARCHAR(255) NULL,
  qualification    VARCHAR(150) NULL,
  specialization   VARCHAR(150) NULL,
  experience_years DECIMAL(4,1) NOT NULL DEFAULT 0,
  joining_date     DATE NULL,
  department_id    INT UNSIGNED NULL,
  designation_id   INT UNSIGNED NULL,
  basic_salary     DECIMAL(12,2) NOT NULL DEFAULT 0,
  allowances       DECIMAL(12,2) NOT NULL DEFAULT 0,
  bank_name        VARCHAR(100) NULL,
  bank_account     VARCHAR(50) NULL,
  status           ENUM('active','inactive','resigned') NOT NULL DEFAULT 'active',
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_teachers_status (status),
  INDEX idx_teachers_name (name),
  CONSTRAINT fk_teachers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_teachers_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  CONSTRAINT fk_teachers_desig FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id          INT UNSIGNED NULL UNIQUE,
  employee_no      VARCHAR(30) NOT NULL UNIQUE,
  name             VARCHAR(120) NOT NULL,
  father_name      VARCHAR(120) NULL,
  gender           ENUM('male','female','other') NOT NULL DEFAULT 'male',
  dob              DATE NULL,
  cnic             VARCHAR(20) NULL UNIQUE,
  phone            VARCHAR(30) NULL,
  email            VARCHAR(150) NULL,
  address          TEXT NULL,
  photo            VARCHAR(255) NULL,
  qualification    VARCHAR(150) NULL,
  specialization   VARCHAR(150) NULL,
  experience_years DECIMAL(4,1) NOT NULL DEFAULT 0,
  joining_date     DATE NULL,
  department_id    INT UNSIGNED NULL,
  designation_id   INT UNSIGNED NULL,
  basic_salary     DECIMAL(12,2) NOT NULL DEFAULT 0,
  allowances       DECIMAL(12,2) NOT NULL DEFAULT 0,
  bank_name        VARCHAR(100) NULL,
  bank_account     VARCHAR(50) NULL,
  status           ENUM('active','inactive','resigned') NOT NULL DEFAULT 'active',
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_staff_status (status),
  INDEX idx_staff_name (name),
  CONSTRAINT fk_staff_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_staff_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  CONSTRAINT fk_staff_desig FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employee_documents (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  emp_type      ENUM('teacher','staff') NOT NULL,
  emp_id        INT UNSIGNED NOT NULL,
  title         VARCHAR(150) NOT NULL,
  file_path     VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NULL,
  uploaded_by   INT UNSIGNED NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_empdoc (emp_type, emp_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS classes (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(50) NOT NULL UNIQUE,
  numeric_order INT NOT NULL DEFAULT 0,
  description   VARCHAR(255) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sections (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  class_id         INT UNSIGNED NOT NULL,
  name             VARCHAR(20) NOT NULL,
  capacity         INT UNSIGNED NOT NULL DEFAULT 40,
  class_teacher_id INT UNSIGNED NULL,
  room             VARCHAR(30) NULL,
  UNIQUE KEY uq_section (class_id, name),
  CONSTRAINT fk_sections_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_sections_teacher FOREIGN KEY (class_teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subjects (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  code        VARCHAR(20) NULL UNIQUE,
  type        ENUM('theory','practical','both') NOT NULL DEFAULT 'theory',
  description VARCHAR(255) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS class_subjects (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  class_id    INT UNSIGNED NOT NULL,
  section_id  INT UNSIGNED NOT NULL,
  subject_id  INT UNSIGNED NOT NULL,
  teacher_id  INT UNSIGNED NULL,
  UNIQUE KEY uq_class_subject (section_id, subject_id),
  INDEX idx_cs_teacher (teacher_id),
  CONSTRAINT fk_cs_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  CONSTRAINT fk_cs_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
  CONSTRAINT fk_cs_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_cs_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Transport (referenced by students)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS transport_vehicles (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vehicle_no     VARCHAR(30) NOT NULL UNIQUE,
  model          VARCHAR(100) NULL,
  capacity       INT UNSIGNED NOT NULL DEFAULT 30,
  driver_name    VARCHAR(120) NULL,
  driver_phone   VARCHAR(30) NULL,
  driver_license VARCHAR(50) NULL,
  driver_cnic    VARCHAR(20) NULL,
  status         ENUM('active','maintenance','inactive') NOT NULL DEFAULT 'active',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transport_routes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  start_point VARCHAR(150) NULL,
  end_point   VARCHAR(150) NULL,
  stops       TEXT NULL,
  vehicle_id  INT UNSIGNED NULL,
  monthly_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_routes_vehicle FOREIGN KEY (vehicle_id) REFERENCES transport_vehicles(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Students & parents
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS parents (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id           INT UNSIGNED NULL UNIQUE,
  father_name       VARCHAR(120) NOT NULL,
  father_cnic       VARCHAR(20) NULL UNIQUE,
  father_phone      VARCHAR(30) NULL,
  father_occupation VARCHAR(100) NULL,
  mother_name       VARCHAR(120) NULL,
  mother_cnic       VARCHAR(20) NULL,
  mother_phone      VARCHAR(30) NULL,
  mother_occupation VARCHAR(100) NULL,
  guardian_name     VARCHAR(120) NULL,
  guardian_relation VARCHAR(50) NULL,
  guardian_phone    VARCHAR(30) NULL,
  guardian_cnic     VARCHAR(20) NULL,
  email             VARCHAR(150) NULL,
  address           TEXT NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_parents_phone (father_phone),
  CONSTRAINT fk_parents_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS students (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NULL UNIQUE,
  parent_id       INT UNSIGNED NULL,
  reg_no          VARCHAR(30) NOT NULL UNIQUE,
  roll_no         VARCHAR(20) NULL,
  first_name      VARCHAR(60) NOT NULL,
  last_name       VARCHAR(60) NULL,
  gender          ENUM('male','female','other') NOT NULL,
  dob             DATE NULL,
  b_form          VARCHAR(20) NULL UNIQUE,
  religion        VARCHAR(30) NULL,
  nationality     VARCHAR(40) NULL DEFAULT 'Pakistani',
  blood_group     VARCHAR(5) NULL,
  phone           VARCHAR(30) NULL,
  email           VARCHAR(150) NULL,
  address         TEXT NULL,
  city            VARCHAR(60) NULL,
  photo           VARCHAR(255) NULL,
  previous_school VARCHAR(150) NULL,
  previous_class  VARCHAR(50) NULL,
  admission_date  DATE NOT NULL,
  session_id      INT UNSIGNED NOT NULL,
  class_id        INT UNSIGNED NOT NULL,
  section_id      INT UNSIGNED NULL,
  route_id        INT UNSIGNED NULL,
  status          ENUM('active','withdrawn','transferred','graduated','suspended') NOT NULL DEFAULT 'active',
  status_date     DATE NULL,
  status_reason   VARCHAR(255) NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_students_class (class_id, section_id, status),
  INDEX idx_students_name (first_name, last_name),
  INDEX idx_students_status (status),
  CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_students_parent FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE SET NULL,
  CONSTRAINT fk_students_session FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE RESTRICT,
  CONSTRAINT fk_students_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_students_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL,
  CONSTRAINT fk_students_route FOREIGN KEY (route_id) REFERENCES transport_routes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admissions (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id     INT UNSIGNED NOT NULL,
  session_id     INT UNSIGNED NOT NULL,
  class_id       INT UNSIGNED NOT NULL,
  section_id     INT UNSIGNED NULL,
  admission_date DATE NOT NULL,
  admission_type ENUM('new','readmission') NOT NULL DEFAULT 'new',
  remarks        VARCHAR(255) NULL,
  created_by     INT UNSIGNED NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_adm_date (admission_date),
  CONSTRAINT fk_adm_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_adm_session FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE RESTRICT,
  CONSTRAINT fk_adm_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_documents (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id    INT UNSIGNED NOT NULL,
  title         VARCHAR(150) NOT NULL,
  file_path     VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NULL,
  uploaded_by   INT UNSIGNED NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sdoc_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_history (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id  INT UNSIGNED NOT NULL,
  session_id  INT UNSIGNED NULL,
  class_id    INT UNSIGNED NULL,
  section_id  INT UNSIGNED NULL,
  action      VARCHAR(30) NOT NULL,
  details     VARCHAR(500) NULL,
  created_by  INT UNSIGNED NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sh_student (student_id),
  CONSTRAINT fk_sh_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Attendance, leave, performance
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS student_attendance (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id  INT UNSIGNED NOT NULL,
  session_id  INT UNSIGNED NULL,
  class_id    INT UNSIGNED NOT NULL,
  section_id  INT UNSIGNED NULL,
  att_date    DATE NOT NULL,
  status      ENUM('present','absent','leave','late') NOT NULL,
  remarks     VARCHAR(255) NULL,
  marked_by   INT UNSIGNED NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student_day (student_id, att_date),
  INDEX idx_sa_class (class_id, section_id, att_date),
  INDEX idx_sa_date (att_date),
  CONSTRAINT fk_sa_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_sa_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employee_attendance (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  emp_type    ENUM('teacher','staff') NOT NULL,
  emp_id      INT UNSIGNED NOT NULL,
  att_date    DATE NOT NULL,
  status      ENUM('present','absent','leave','late') NOT NULL,
  check_in    TIME NULL,
  check_out   TIME NULL,
  remarks     VARCHAR(255) NULL,
  marked_by   INT UNSIGNED NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_emp_day (emp_type, emp_id, att_date),
  INDEX idx_ea_date (att_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leaves (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  emp_type     ENUM('teacher','staff') NOT NULL,
  emp_id       INT UNSIGNED NOT NULL,
  leave_type   ENUM('casual','sick','annual','maternity','unpaid','other') NOT NULL DEFAULT 'casual',
  from_date    DATE NOT NULL,
  to_date      DATE NOT NULL,
  days         INT UNSIGNED NOT NULL DEFAULT 1,
  reason       TEXT NULL,
  status       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  approved_by  INT UNSIGNED NULL,
  approved_at  DATETIME NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_leaves_emp (emp_type, emp_id),
  INDEX idx_leaves_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teacher_performance (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id   INT UNSIGNED NOT NULL,
  review_date  DATE NOT NULL,
  period       VARCHAR(50) NULL,
  category     VARCHAR(50) NOT NULL DEFAULT 'Overall',
  rating       TINYINT UNSIGNED NOT NULL,
  remarks      TEXT NULL,
  reviewed_by  INT UNSIGNED NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_perf_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Fees
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS fee_categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  frequency   ENUM('one_time','monthly','annual','per_exam') NOT NULL DEFAULT 'monthly',
  description VARCHAR(255) NULL,
  is_system   TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_structures (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id      INT UNSIGNED NOT NULL,
  class_id        INT UNSIGNED NOT NULL,
  fee_category_id INT UNSIGNED NOT NULL,
  amount          DECIMAL(12,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_fee_structure (session_id, class_id, fee_category_id),
  CONSTRAINT fk_fs_session FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_fs_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  CONSTRAINT fk_fs_cat FOREIGN KEY (fee_category_id) REFERENCES fee_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_fees (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id      INT UNSIGNED NOT NULL,
  fee_category_id INT UNSIGNED NOT NULL,
  amount          DECIMAL(12,2) NOT NULL DEFAULT 0,
  remarks         VARCHAR(255) NULL,
  UNIQUE KEY uq_student_fee (student_id, fee_category_id),
  CONSTRAINT fk_sf_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_sf_cat FOREIGN KEY (fee_category_id) REFERENCES fee_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_discounts (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id      INT UNSIGNED NOT NULL,
  title           VARCHAR(100) NOT NULL,
  discount_type   ENUM('discount','scholarship') NOT NULL DEFAULT 'discount',
  value_type      ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  value           DECIMAL(10,2) NOT NULL,
  fee_category_id INT UNSIGNED NULL,
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  remarks         VARCHAR(255) NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sd_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_sd_cat FOREIGN KEY (fee_category_id) REFERENCES fee_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_invoices (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_no   VARCHAR(30) NOT NULL UNIQUE,
  student_id   INT UNSIGNED NOT NULL,
  session_id   INT UNSIGNED NULL,
  class_id     INT UNSIGNED NULL,
  fee_month    CHAR(7) NULL,
  title        VARCHAR(150) NOT NULL,
  issue_date   DATE NOT NULL,
  due_date     DATE NOT NULL,
  gross_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount     DECIMAL(12,2) NOT NULL DEFAULT 0,
  fine         DECIMAL(12,2) NOT NULL DEFAULT 0,
  net_amount   DECIMAL(12,2) NOT NULL DEFAULT 0,
  paid_amount  DECIMAL(12,2) NOT NULL DEFAULT 0,
  status       ENUM('unpaid','partial','paid','cancelled') NOT NULL DEFAULT 'unpaid',
  remarks      VARCHAR(255) NULL,
  created_by   INT UNSIGNED NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_invoice_month (student_id, fee_month),
  INDEX idx_inv_status (status),
  INDEX idx_inv_due (due_date),
  CONSTRAINT fk_inv_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_invoice_items (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id      INT UNSIGNED NOT NULL,
  fee_category_id INT UNSIGNED NULL,
  description     VARCHAR(150) NOT NULL,
  amount          DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount        DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_item_inv FOREIGN KEY (invoice_id) REFERENCES fee_invoices(id) ON DELETE CASCADE,
  CONSTRAINT fk_item_cat FOREIGN KEY (fee_category_id) REFERENCES fee_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_payments (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  receipt_no  VARCHAR(30) NOT NULL UNIQUE,
  invoice_id  INT UNSIGNED NOT NULL,
  student_id  INT UNSIGNED NOT NULL,
  amount      DECIMAL(12,2) NOT NULL,
  fine        DECIMAL(12,2) NOT NULL DEFAULT 0,
  method      ENUM('cash','bank','cheque','online') NOT NULL DEFAULT 'cash',
  reference   VARCHAR(100) NULL,
  paid_on     DATE NOT NULL,
  remarks     VARCHAR(255) NULL,
  received_by INT UNSIGNED NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pay_date (paid_on),
  INDEX idx_pay_student (student_id),
  CONSTRAINT fk_pay_invoice FOREIGN KEY (invoice_id) REFERENCES fee_invoices(id) ON DELETE RESTRICT,
  CONSTRAINT fk_pay_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Examinations
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS grades (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(10) NOT NULL UNIQUE,
  min_percent DECIMAL(5,2) NOT NULL,
  max_percent DECIMAL(5,2) NOT NULL,
  grade_point DECIMAL(3,2) NOT NULL DEFAULT 0,
  remarks     VARCHAR(50) NULL,
  is_pass     TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exams (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id   INT UNSIGNED NOT NULL,
  name         VARCHAR(100) NOT NULL,
  exam_type    ENUM('monthly_test','midterm','final','other') NOT NULL DEFAULT 'midterm',
  start_date   DATE NULL,
  end_date     DATE NULL,
  description  VARCHAR(255) NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_exam (session_id, name),
  CONSTRAINT fk_exam_session FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_subjects (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  exam_id     INT UNSIGNED NOT NULL,
  class_id    INT UNSIGNED NOT NULL,
  subject_id  INT UNSIGNED NOT NULL,
  exam_date   DATE NULL,
  start_time  TIME NULL,
  total_marks DECIMAL(6,2) NOT NULL DEFAULT 100,
  pass_marks  DECIMAL(6,2) NOT NULL DEFAULT 33,
  UNIQUE KEY uq_exam_subject (exam_id, class_id, subject_id),
  CONSTRAINT fk_es_exam FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  CONSTRAINT fk_es_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  CONSTRAINT fk_es_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marks (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  exam_subject_id INT UNSIGNED NOT NULL,
  student_id      INT UNSIGNED NOT NULL,
  obtained_marks  DECIMAL(6,2) NULL,
  is_absent       TINYINT(1) NOT NULL DEFAULT 0,
  remarks         VARCHAR(100) NULL,
  entered_by      INT UNSIGNED NULL,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_marks (exam_subject_id, student_id),
  CONSTRAINT fk_marks_es FOREIGN KEY (exam_subject_id) REFERENCES exam_subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_marks_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS results (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  exam_id         INT UNSIGNED NOT NULL,
  student_id      INT UNSIGNED NOT NULL,
  class_id        INT UNSIGNED NOT NULL,
  section_id      INT UNSIGNED NULL,
  total_marks     DECIMAL(8,2) NOT NULL DEFAULT 0,
  obtained_marks  DECIMAL(8,2) NOT NULL DEFAULT 0,
  percentage      DECIMAL(5,2) NOT NULL DEFAULT 0,
  grade           VARCHAR(10) NULL,
  result_status   ENUM('pass','fail') NOT NULL DEFAULT 'fail',
  position        INT UNSIGNED NULL,
  subjects_failed INT UNSIGNED NOT NULL DEFAULT 0,
  remarks         VARCHAR(255) NULL,
  generated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_result (exam_id, student_id),
  INDEX idx_results_class (exam_id, class_id, section_id),
  CONSTRAINT fk_res_exam FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  CONSTRAINT fk_res_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Timetable
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS periods (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(50) NOT NULL,
  start_time  TIME NOT NULL,
  end_time    TIME NOT NULL,
  is_break    TINYINT(1) NOT NULL DEFAULT 0,
  sort_order  INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS timetables (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id  INT UNSIGNED NOT NULL,
  section_id  INT UNSIGNED NOT NULL,
  day_of_week TINYINT UNSIGNED NOT NULL,
  period_id   INT UNSIGNED NOT NULL,
  subject_id  INT UNSIGNED NULL,
  teacher_id  INT UNSIGNED NULL,
  room        VARCHAR(30) NULL,
  UNIQUE KEY uq_tt_slot (session_id, section_id, day_of_week, period_id),
  INDEX idx_tt_teacher (teacher_id, day_of_week, period_id),
  CONSTRAINT fk_tt_session FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_tt_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
  CONSTRAINT fk_tt_period FOREIGN KEY (period_id) REFERENCES periods(id) ON DELETE CASCADE,
  CONSTRAINT fk_tt_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
  CONSTRAINT fk_tt_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Accounting
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS income_categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incomes (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id  INT UNSIGNED NOT NULL,
  title        VARCHAR(150) NOT NULL,
  amount       DECIMAL(12,2) NOT NULL,
  income_date  DATE NOT NULL,
  method       ENUM('cash','bank','cheque','online') NOT NULL DEFAULT 'cash',
  reference    VARCHAR(100) NULL,
  description  TEXT NULL,
  created_by   INT UNSIGNED NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_income_date (income_date),
  CONSTRAINT fk_income_cat FOREIGN KEY (category_id) REFERENCES income_categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS expense_categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS expenses (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id  INT UNSIGNED NOT NULL,
  title        VARCHAR(150) NOT NULL,
  amount       DECIMAL(12,2) NOT NULL,
  expense_date DATE NOT NULL,
  method       ENUM('cash','bank','cheque','online') NOT NULL DEFAULT 'cash',
  paid_to      VARCHAR(150) NULL,
  reference    VARCHAR(100) NULL,
  description  TEXT NULL,
  attachment   VARCHAR(255) NULL,
  created_by   INT UNSIGNED NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_expense_date (expense_date),
  CONSTRAINT fk_expense_cat FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS salaries (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slip_no      VARCHAR(30) NOT NULL UNIQUE,
  emp_type     ENUM('teacher','staff') NOT NULL,
  emp_id       INT UNSIGNED NOT NULL,
  salary_month CHAR(7) NOT NULL,
  basic        DECIMAL(12,2) NOT NULL DEFAULT 0,
  allowances   DECIMAL(12,2) NOT NULL DEFAULT 0,
  bonus        DECIMAL(12,2) NOT NULL DEFAULT 0,
  deductions   DECIMAL(12,2) NOT NULL DEFAULT 0,
  net_salary   DECIMAL(12,2) NOT NULL DEFAULT 0,
  status       ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  paid_on      DATE NULL,
  method       ENUM('cash','bank','cheque','online') NULL,
  remarks      VARCHAR(255) NULL,
  created_by   INT UNSIGNED NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_salary (emp_type, emp_id, salary_month),
  INDEX idx_salary_month (salary_month),
  INDEX idx_salary_paid (paid_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Library
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS book_categories (
  id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS books (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id  INT UNSIGNED NULL,
  title        VARCHAR(200) NOT NULL,
  isbn         VARCHAR(20) NULL UNIQUE,
  author       VARCHAR(150) NULL,
  publisher    VARCHAR(150) NULL,
  edition      VARCHAR(50) NULL,
  publish_year SMALLINT UNSIGNED NULL,
  rack_no      VARCHAR(30) NULL,
  quantity     INT UNSIGNED NOT NULL DEFAULT 1,
  available    INT UNSIGNED NOT NULL DEFAULT 1,
  price        DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_books_title (title),
  CONSTRAINT fk_books_cat FOREIGN KEY (category_id) REFERENCES book_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS book_issues (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  book_id      INT UNSIGNED NOT NULL,
  member_type  ENUM('student','teacher','staff') NOT NULL,
  member_id    INT UNSIGNED NOT NULL,
  issue_date   DATE NOT NULL,
  due_date     DATE NOT NULL,
  return_date  DATE NULL,
  fine         DECIMAL(10,2) NOT NULL DEFAULT 0,
  fine_paid    TINYINT(1) NOT NULL DEFAULT 0,
  status       ENUM('issued','returned','lost') NOT NULL DEFAULT 'issued',
  remarks      VARCHAR(255) NULL,
  issued_by    INT UNSIGNED NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_bi_status (status),
  INDEX idx_bi_member (member_type, member_id),
  CONSTRAINT fk_bi_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Homework & assignments
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS homework (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id   INT UNSIGNED NULL,
  class_id     INT UNSIGNED NOT NULL,
  section_id   INT UNSIGNED NULL,
  subject_id   INT UNSIGNED NOT NULL,
  teacher_id   INT UNSIGNED NULL,
  title        VARCHAR(200) NOT NULL,
  description  TEXT NULL,
  attachment   VARCHAR(255) NULL,
  assign_date  DATE NOT NULL,
  due_date     DATE NOT NULL,
  max_marks    DECIMAL(5,2) NULL,
  created_by   INT UNSIGNED NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_hw_class (class_id, section_id),
  CONSTRAINT fk_hw_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  CONSTRAINT fk_hw_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
  CONSTRAINT fk_hw_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_hw_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS homework_submissions (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  homework_id  INT UNSIGNED NOT NULL,
  student_id   INT UNSIGNED NOT NULL,
  content      TEXT NULL,
  attachment   VARCHAR(255) NULL,
  submitted_at DATETIME NULL,
  status       ENUM('submitted','late','reviewed') NOT NULL DEFAULT 'submitted',
  marks        DECIMAL(5,2) NULL,
  feedback     TEXT NULL,
  reviewed_by  INT UNSIGNED NULL,
  reviewed_at  DATETIME NULL,
  UNIQUE KEY uq_submission (homework_id, student_id),
  CONSTRAINT fk_hs_hw FOREIGN KEY (homework_id) REFERENCES homework(id) ON DELETE CASCADE,
  CONSTRAINT fk_hs_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Communication
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notices (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(200) NOT NULL,
  body         TEXT NOT NULL,
  notice_type  ENUM('general','exam','holiday','fee','event') NOT NULL DEFAULT 'general',
  audience     ENUM('all','students','parents','teachers','staff','class') NOT NULL DEFAULT 'all',
  class_id     INT UNSIGNED NULL,
  publish_date DATE NOT NULL,
  expiry_date  DATE NULL,
  created_by   INT UNSIGNED NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notice_date (publish_date),
  CONSTRAINT fk_notice_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  title       VARCHAR(200) NOT NULL,
  message     VARCHAR(1000) NULL,
  link        VARCHAR(255) NULL,
  type        VARCHAR(30) NOT NULL DEFAULT 'general',
  is_read     TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notif_user (user_id, is_read),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS message_logs (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  channel     ENUM('sms','email') NOT NULL,
  recipient   VARCHAR(150) NOT NULL,
  subject     VARCHAR(200) NULL,
  message     TEXT NULL,
  status      ENUM('sent','failed') NOT NULL,
  response    TEXT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_msg_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
