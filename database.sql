-- English Language Institute Management System - Full Database Schema

CREATE DATABASE IF NOT EXISTS english_institute;
USE english_institute;

-- Users table (for authentication - Admin only)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin') NOT NULL DEFAULT 'admin',
    email VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Teachers table
CREATE TABLE IF NOT EXISTS teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    qualification VARCHAR(100),
    specialization VARCHAR(100),
    experience_years INT DEFAULT 0,
    salary DECIMAL(10,2) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Time slots table
CREATE TABLE IF NOT EXISTS time_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    UNIQUE KEY unique_slot (start_time, end_time)
) ENGINE=InnoDB;

-- Days of week
CREATE TABLE IF NOT EXISTS days (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(20) NOT NULL,
    short_name VARCHAR(3) NOT NULL,
    day_order INT NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Subjects table
CREATE TABLE IF NOT EXISTS subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20),
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Teacher-Subject assignments (many-to-many)
CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_subject (teacher_id, subject_id)
) ENGINE=InnoDB;

-- Classes table (student groups - no time slot, subjects own the schedule)
CREATE TABLE IF NOT EXISTS classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    max_students INT DEFAULT 30,
    status ENUM('active', 'archived', 'completed') DEFAULT 'active',
    INDEX (status),
    INDEX (start_date)
) ENGINE=InnoDB;

-- Class-Subject schedule entries (the timetable)
CREATE TABLE IF NOT EXISTS class_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    time_slot_id INT NOT NULL,
    day_of_week ENUM('Mon','Tue','Wed','Thu','Fri','Sat','Sun') NOT NULL,
    weekly_frequency INT DEFAULT 1,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id),
    UNIQUE KEY unique_class_day_slot (class_id, day_of_week, time_slot_id)
) ENGINE=InnoDB;

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_uid VARCHAR(10) DEFAULT NULL,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    guardian_name VARCHAR(100),
    guardian_phone VARCHAR(20),
    current_class_id INT,
    enrollment_date DATE NOT NULL,
    status ENUM('active', 'inactive', 'graduated', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_uid (student_uid),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (current_class_id) REFERENCES classes(id),
    INDEX (status)
) ENGINE=InnoDB;

-- Student-Class-Course enrollment
CREATE TABLE IF NOT EXISTS student_enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    UNIQUE KEY unique_enrollment (student_id, class_id)
) ENGINE=InnoDB;

-- Fee structures
CREATE TABLE IF NOT EXISTS fee_structures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    fee_type ENUM('monthly', 'quarterly', 'yearly', 'one-time') DEFAULT 'monthly',
    description TEXT,
    due_date_day INT DEFAULT 1,
    penalty_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB;

-- Fee payments
CREATE TABLE IF NOT EXISTS fee_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    fee_structure_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'online') DEFAULT 'cash',
    transaction_id VARCHAR(100),
    remarks TEXT,
    received_by INT,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id),
    FOREIGN KEY (received_by) REFERENCES users(id),
    INDEX (student_id),
    INDEX (payment_date)
) ENGINE=InnoDB;

-- Student fee ledger
CREATE TABLE IF NOT EXISTS student_fees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    fee_structure_id INT NOT NULL,
    amount_due DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0,
    due_date DATE,
    status ENUM('pending', 'partial', 'paid', 'overdue') DEFAULT 'pending',
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id),
    INDEX (student_id),
    INDEX (due_date)
) ENGINE=InnoDB;

-- Student reassignments table
CREATE TABLE IF NOT EXISTS student_reassignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    from_class_id INT NOT NULL,
    to_class_id INT NOT NULL,
    reassign_date DATE NOT NULL,
    reason VARCHAR(255),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (from_class_id) REFERENCES classes(id),
    FOREIGN KEY (to_class_id) REFERENCES classes(id)
) ENGINE=InnoDB;

-- Expense categories
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB;

-- Expenses
CREATE TABLE IF NOT EXISTS expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expense_category_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'online') DEFAULT 'cash',
    reference_number VARCHAR(100),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_category_id) REFERENCES expense_categories(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id),
    INDEX (expense_date),
    INDEX (expense_category_id)
) ENGINE=InnoDB;

-- Salary payments
CREATE TABLE IF NOT EXISTS salary_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    month_for VARCHAR(7) NOT NULL,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'online') DEFAULT 'cash',
    remarks TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id),
    INDEX (teacher_id),
    INDEX (month_for),
    INDEX (payment_date)
) ENGINE=InnoDB;

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default settings
INSERT INTO settings (setting_key, setting_value) VALUES 
    ('institute_name', 'English Institute'),
    ('institute_logo', ''),
    ('institute_email', ''),
    ('institute_phone', ''),
    ('institute_address', '');

-- ============================================
-- SEED DATA
-- ============================================

-- Insert Admin user
INSERT INTO users (username, password, role, email) VALUES 
    ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'admin@example.com'); -- password: admin123

-- Insert time slots
INSERT INTO time_slots (start_time, end_time) VALUES 
    ('08:00:00', '09:00:00'),
    ('09:00:00', '10:00:00'),
    ('10:00:00', '11:00:00'),
    ('11:00:00', '12:00:00'),
    ('13:00:00', '14:00:00'),
    ('14:00:00', '15:00:00'),
    ('15:00:00', '16:00:00'),
    ('16:00:00', '17:00:00'),
    ('17:00:00', '18:00:00'),
    ('18:00:00', '19:00:00'),
    ('19:00:00', '20:00:00'),
    ('20:00:00', '21:00:00');

-- Insert days
INSERT INTO days (name, short_name, day_order) VALUES 
    ('Monday', 'Mon', 1),
    ('Tuesday', 'Tue', 2),
    ('Wednesday', 'Wed', 3),
    ('Thursday', 'Thu', 4),
    ('Friday', 'Fri', 5),
    ('Saturday', 'Sat', 6),
    ('Sunday', 'Sun', 7);

-- Insert 8 Teachers
INSERT INTO teachers (name, email, phone, qualification, specialization, experience_years, salary, status) VALUES
    ('Abdirahman Ahmed Hassan', 'abdirahman@school.com', '+252615001001', 'B.Ed Primary Education', 'Mathematics', 8, 800.00, 'active'),
    ('Fatima Mohamed Ali', 'fatima@school.com', '+252615001002', 'B.Ed English Language', 'English', 12, 950.00, 'active'),
    ('Omar Abdullahi Nur', 'omar@school.com', '+252615001003', 'M.A Education', 'Science', 15, 1100.00, 'active'),
    ('Amina Hassan Yusuf', 'amina@school.com', '+252615001004', 'B.Ed Islamic Studies', 'Islamic Studies', 6, 750.00, 'active'),
    ('Ibrahim Mohamed Warsame', 'ibrahim@school.com', '+252615001005', 'B.Sc Computer Science', 'ICT', 5, 850.00, 'active'),
    ('Halima Abdi Osman', 'halima@school.com', '+252615001006', 'B.A Somali Language', 'Somali', 10, 900.00, 'active'),
    ('Mohamed Ali Geedi', 'mohamed@school.com', '+252615001007', 'B.Ed Social Studies', 'Geography', 7, 800.00, 'active'),
    ('Zahra Ahmed Farah', 'zahra@school.com', '+252615001008', 'B.Ed Arts', 'Art & Music', 4, 700.00, 'active');

-- Insert Classes (5 grades x 2 sections)
INSERT INTO classes (name, start_date, max_students, status) VALUES
    ('Grade 1 - Section A', '2025-09-01', 30, 'active'),
    ('Grade 1 - Section B', '2025-09-01', 30, 'active'),
    ('Grade 2 - Section A', '2025-09-01', 28, 'active'),
    ('Grade 2 - Section B', '2025-09-01', 28, 'active'),
    ('Grade 3 - Section A', '2025-09-01', 25, 'active'),
    ('Grade 3 - Section B', '2025-09-01', 25, 'active'),
    ('Grade 4 - Section A', '2025-09-01', 25, 'active'),
    ('Grade 4 - Section B', '2025-09-01', 25, 'active'),
    ('Grade 5 - Section A', '2025-09-01', 20, 'active'),
    ('Grade 5 - Section B', '2025-09-01', 20, 'active');

-- Insert 20 Students (2 per class)
INSERT INTO students (student_uid, name, gender, phone, guardian_name, guardian_phone, current_class_id, enrollment_date, status) VALUES
    ('25001', 'Abdullahi Mohamud Abdi', 'male', '+252616001001', 'Mohamud Abdi Mohamed', '+252616002001', 1, '2025-09-01', 'active'),
    ('25002', 'Khadija Omar Hassan', 'female', '+252616001002', 'Omar Hassan Farah', '+252616002002', 1, '2025-09-01', 'active'),
    ('25003', 'Ahmed Ali Yusuf', 'male', '+252616001003', 'Ali Yusuf Hersi', '+252616002003', 2, '2025-09-01', 'active'),
    ('25004', 'Sahra Ibrahim Mohamed', 'female', '+252616001004', 'Ibrahim Mohamed Saaid', '+252616002004', 2, '2025-09-01', 'active'),
    ('25005', 'Hassan Abdi Warsame', 'male', '+252616001005', 'Abdi Warsame Guled', '+252616002005', 3, '2025-09-01', 'active'),
    ('25006', 'Maryan Ahmed Nur', 'female', '+252616001006', 'Ahmed Nur Salad', '+252616002006', 3, '2025-09-01', 'active'),
    ('25007', 'Osman Khalid Roble', 'male', '+252616001007', 'Khalid Roble Ahmed', '+252616002007', 4, '2025-09-01', 'active'),
    ('25008', 'Fadumo Ismail Hassan', 'female', '+252616001008', 'Ismail Hassan Abdi', '+252616002008', 4, '2025-09-01', 'active'),
    ('25009', 'Yusuf Mohamed Dahir', 'male', '+252616001009', 'Mohamed Dahir Ali', '+252616002009', 5, '2025-09-01', 'active'),
    ('25010', 'Hawa Abdirahman Farah', 'female', '+252616001010', 'Abdirahman Farah Mohamud', '+252616002010', 5, '2025-09-01', 'active'),
    ('25011', 'Bilal Omar Mohamud', 'male', '+252616001011', 'Omar Mohamud Abdi', '+252616002011', 6, '2025-09-01', 'active'),
    ('25012', 'Sumaya Ahmed Omar', 'female', '+252616001012', 'Ahmed Omar Hassan', '+252616002012', 6, '2025-09-01', 'active'),
    ('25013', 'Mukhtar Abdi Salah', 'male', '+252616001013', 'Abdi Salah Mohamed', '+252616002013', 7, '2025-09-02', 'active'),
    ('25014', 'Ifrah Ali Hersi', 'female', '+252616001014', 'Ali Hersi Guled', '+252616002014', 7, '2025-09-02', 'active'),
    ('25015', 'Said Mohamed Nur', 'male', '+252616001015', 'Mohamed Nur Abdi', '+252616002015', 8, '2025-09-02', 'active'),
    ('25016', 'Nimco Abdirashid Warsame', 'female', '+252616001016', 'Abdirashid Warsame Ali', '+252616002016', 8, '2025-09-02', 'active'),
    ('25017', 'Farah Hassan Ismail', 'male', '+252616001017', 'Hassan Ismail Dahir', '+252616002017', 9, '2025-09-02', 'active'),
    ('25018', 'Zainab Omar Abdi', 'female', '+252616001018', 'Omar Abdi Mohamed', '+252616002018', 9, '2025-09-02', 'active'),
    ('25019', 'Abdirizak Ali Mohamud', 'male', '+252616001019', 'Ali Mohamud Hassan', '+252616002019', 10, '2025-09-02', 'active'),
    ('25020', 'Asho Mohamed Ahmed', 'female', '+252616001020', 'Mohamed Ahmed Omar', '+252616002020', 10, '2025-09-02', 'active');

-- Insert Subjects
INSERT INTO subjects (name, code, description, status) VALUES
    ('Mathematics', 'MATH', 'Mathematics and arithmetic', 'active'),
    ('English', 'ENG', 'English Language', 'active'),
    ('Science', 'SCI', 'General Science', 'active'),
    ('Islamic Studies', 'ISL', 'Islamic Religious Studies', 'active'),
    ('Somali', 'SOM', 'Somali Language and Literature', 'active'),
    ('Geography', 'GEO', 'Geography and Social Studies', 'active'),
    ('ICT', 'ICT', 'Information and Communication Technology', 'active'),
    ('Art', 'ART', 'Art and Drawing', 'active'),
    ('Physical Education', 'PE', 'Physical Education and Sports', 'active'),
    ('Music', 'MUS', 'Music and Arts', 'active');

-- Teacher-Subject assignments
INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES
    (1,1),(1,3),(2,2),(2,8),(3,3),(3,1),(4,4),(4,5),
    (5,7),(5,10),(6,5),(6,2),(7,6),(7,3),(8,8),(8,9),(8,10);

-- Class Subject schedule entries (sample schedule)
-- Grade 1-A
INSERT INTO class_subjects (class_id, subject_id, teacher_id, time_slot_id, day_of_week, weekly_frequency, status) VALUES
(1,1,1,1,'Mon',5,'active'),(1,1,1,1,'Tue',5,'active'),(1,1,1,1,'Wed',5,'active'),(1,1,1,1,'Thu',5,'active'),(1,1,1,1,'Fri',5,'active'),
(1,2,2,2,'Mon',3,'active'),(1,2,2,2,'Wed',3,'active'),(1,2,2,2,'Fri',3,'active'),
(1,4,4,3,'Mon',2,'active'),(1,4,4,3,'Thu',2,'active'),
(1,5,6,4,'Tue',2,'active'),(1,5,6,4,'Thu',2,'active'),
(1,9,8,5,'Mon',1,'active');
-- Grade 1-B
INSERT INTO class_subjects (class_id, subject_id, teacher_id, time_slot_id, day_of_week, weekly_frequency, status) VALUES
(2,1,3,2,'Mon',5,'active'),(2,1,3,2,'Tue',5,'active'),(2,1,3,2,'Wed',5,'active'),(2,1,3,2,'Thu',5,'active'),(2,1,3,2,'Fri',5,'active'),
(2,2,6,3,'Mon',3,'active'),(2,2,6,3,'Wed',3,'active'),(2,2,6,3,'Fri',3,'active'),
(2,4,4,4,'Tue',2,'active'),(2,4,4,4,'Fri',2,'active'),
(2,5,6,5,'Mon',2,'active'),(2,5,6,5,'Thu',2,'active'),
(2,8,8,6,'Wed',1,'active');
-- Grade 2-A
INSERT INTO class_subjects (class_id, subject_id, teacher_id, time_slot_id, day_of_week, weekly_frequency, status) VALUES
(3,1,1,3,'Mon',5,'active'),(3,1,1,3,'Tue',5,'active'),(3,1,1,3,'Wed',5,'active'),(3,1,1,3,'Thu',5,'active'),(3,1,1,3,'Fri',5,'active'),
(3,2,2,4,'Mon',3,'active'),(3,2,2,4,'Wed',3,'active'),(3,2,2,4,'Fri',3,'active'),
(3,3,3,5,'Tue',2,'active'),(3,3,3,5,'Thu',2,'active'),
(3,4,4,6,'Mon',2,'active'),(3,4,4,6,'Thu',2,'active'),
(3,6,7,7,'Wed',1,'active');
-- Grade 2-B
INSERT INTO class_subjects (class_id, subject_id, teacher_id, time_slot_id, day_of_week, weekly_frequency, status) VALUES
(4,1,3,4,'Mon',5,'active'),(4,1,3,4,'Tue',5,'active'),(4,1,3,4,'Wed',5,'active'),(4,1,3,4,'Thu',5,'active'),(4,1,3,4,'Fri',5,'active'),
(4,2,6,5,'Mon',3,'active'),(4,2,6,5,'Wed',3,'active'),(4,2,6,5,'Fri',3,'active'),
(4,3,1,6,'Tue',2,'active'),(4,3,1,6,'Thu',2,'active'),
(4,5,4,7,'Mon',2,'active'),(4,5,4,7,'Wed',2,'active'),
(4,7,5,8,'Fri',1,'active');
-- Grade 3-A
INSERT INTO class_subjects (class_id, subject_id, teacher_id, time_slot_id, day_of_week, weekly_frequency, status) VALUES
(5,1,1,5,'Mon',5,'active'),(5,1,1,5,'Tue',5,'active'),(5,1,1,5,'Wed',5,'active'),(5,1,1,5,'Thu',5,'active'),(5,1,1,5,'Fri',5,'active'),
(5,2,2,6,'Mon',3,'active'),(5,2,2,6,'Wed',3,'active'),(5,2,2,6,'Fri',3,'active'),
(5,3,3,7,'Tue',2,'active'),(5,3,3,7,'Thu',2,'active'),
(5,4,4,8,'Mon',2,'active'),(5,4,4,8,'Thu',2,'active'),
(5,5,6,9,'Wed',2,'active'),
(5,6,7,10,'Fri',1,'active');
-- Grade 3-B
INSERT INTO class_subjects (class_id, subject_id, teacher_id, time_slot_id, day_of_week, weekly_frequency, status) VALUES
(6,1,3,6,'Mon',5,'active'),(6,1,3,6,'Tue',5,'active'),(6,1,3,6,'Wed',5,'active'),(6,1,3,6,'Thu',5,'active'),(6,1,3,6,'Fri',5,'active'),
(6,2,6,7,'Mon',3,'active'),(6,2,6,7,'Wed',3,'active'),(6,2,6,7,'Fri',3,'active'),
(6,3,1,8,'Tue',2,'active'),(6,3,1,8,'Thu',2,'active'),
(6,4,4,9,'Mon',2,'active'),(6,4,4,9,'Thu',2,'active'),
(6,7,5,10,'Wed',1,'active'),
(6,8,8,11,'Fri',1,'active');
-- Grade 4-A
INSERT INTO class_subjects (class_id, subject_id, teacher_id, time_slot_id, day_of_week, weekly_frequency, status) VALUES
(7,1,1,7,'Mon',5,'active'),(7,1,1,7,'Tue',5,'active'),(7,1,1,7,'Wed',5,'active'),(7,1,1,7,'Thu',5,'active'),(7,1,1,7,'Sat',5,'active'),
(7,2,2,8,'Mon',3,'active'),(7,2,2,8,'Wed',3,'active'),(7,2,2,8,'Sat',3,'active'),
(7,3,3,9,'Tue',2,'active'),(7,3,3,9,'Thu',2,'active'),
(7,5,6,10,'Mon',2,'active'),(7,5,6,10,'Wed',2,'active'),
(7,6,7,11,'Tue',1,'active'),
(7,9,8,12,'Sat',1,'active');
-- Grade 4-B
INSERT INTO class_subjects (class_id, subject_id, teacher_id, time_slot_id, day_of_week, weekly_frequency, status) VALUES
(8,1,3,8,'Mon',5,'active'),(8,1,3,8,'Tue',5,'active'),(8,1,3,8,'Wed',5,'active'),(8,1,3,8,'Thu',5,'active'),(8,1,3,8,'Sat',5,'active'),
(8,2,6,9,'Mon',3,'active'),(8,2,6,9,'Wed',3,'active'),(8,2,6,9,'Sat',3,'active'),
(8,3,7,10,'Tue',2,'active'),(8,3,7,10,'Thu',2,'active'),
(8,4,4,11,'Mon',2,'active'),(8,4,4,11,'Thu',2,'active'),
(8,7,5,12,'Tue',1,'active'),
(8,10,8,1,'Sat',1,'active');
-- Grade 5-A
INSERT INTO class_subjects (class_id, subject_id, teacher_id, time_slot_id, day_of_week, weekly_frequency, status) VALUES
(9,1,1,9,'Tue',5,'active'),(9,1,1,9,'Thu',5,'active'),(9,1,1,9,'Sat',5,'active'),
(9,2,2,10,'Tue',3,'active'),(9,2,2,10,'Thu',3,'active'),(9,2,2,10,'Sat',3,'active'),
(9,3,3,11,'Tue',2,'active'),(9,3,3,11,'Sat',2,'active'),
(9,4,4,12,'Tue',2,'active'),(9,4,4,12,'Thu',2,'active'),
(9,6,7,1,'Tue',1,'active'),
(9,5,6,2,'Thu',1,'active'),
(9,7,5,3,'Sat',1,'active');
-- Grade 5-B
INSERT INTO class_subjects (class_id, subject_id, teacher_id, time_slot_id, day_of_week, weekly_frequency, status) VALUES
(10,1,3,10,'Mon',5,'active'),(10,1,3,10,'Wed',5,'active'),(10,1,3,10,'Sat',5,'active'),
(10,2,6,11,'Mon',3,'active'),(10,2,6,11,'Wed',3,'active'),(10,2,6,11,'Sat',3,'active'),
(10,3,7,12,'Mon',2,'active'),(10,3,7,12,'Wed',2,'active'),
(10,4,4,1,'Mon',2,'active'),(10,4,4,1,'Sat',2,'active'),
(10,5,6,2,'Wed',2,'active'),
(10,8,8,3,'Mon',1,'active'),
(10,10,8,4,'Sat',1,'active');

-- Insert Expense Categories
INSERT INTO expense_categories (name, description, status) VALUES
    ('Rent', 'Building rent and lease payments', 'active'),
    ('Utilities', 'Electricity, water, gas bills', 'active'),
    ('Salaries', 'Teacher and staff salary payments', 'active'),
    ('Supplies', 'Office and school supplies', 'active'),
    ('Maintenance', 'Building and equipment maintenance', 'active'),
    ('Transport', 'Vehicle and transport expenses', 'active'),
    ('Internet & Phone', 'Internet and phone bills', 'active'),
    ('Miscellaneous', 'Other uncategorized expenses', 'active');

-- Migration: Remove image column from students table (safe to run even if column doesn't exist)
ALTER TABLE students DROP COLUMN image;
