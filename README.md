# English Institute Management System (IMS)

A comprehensive web-based management system for English language institutes. Built with PHP, MySQL, and Tailwind CSS.

![IMS Screenshot](IMS.jpg)

## Features

### User Management
- **Admin Login** - Secure authentication with session management
- **Dashboard** - Overview with stats, class schedule, and quick actions

### Academic Management
- **Teachers** - Add, edit, delete teachers. Teachers are assigned to subjects.
- **Subjects** - Manage subjects (Grammar, Speaking, Writing, Reading, etc.)
- **Courses** - Create courses and link multiple subjects to them
- **Classes** - Create scheduled classes with room, time slot, and course assignments
- **Attendance** - Mark and track student attendance (Present, Absent, Late, Excused)

### Examination System
- **Schedule Exams** - Create exams with customizable marks and passing criteria
- **Enter Marks** - Manual entry or CSV import for exam results
- **Results** - Automatic grading (A+, A, B+, B, C, D, F)
- **CSV Template** - Download pre-filled templates for bulk import

### Student Management
- **Enroll Students** - Add students and assign to classes
- **Transfer Students** - Move students between classes
- **Drop Out** - Mark students as dropped (with restore option)
- **Delete** - Permanently remove students

### Finance
- **Fee Structure** - Define fees per course (monthly, quarterly, yearly, one-time)
- **Payments** - Record payments with multiple methods (cash, card, bank transfer)
- **Receipts** - Generate and print payment receipts

### Reports
- Analytics dashboard with statistics

## Tech Stack

- **Backend:** PHP 7.4+
- **Database:** MySQL (XAMPP)
- **Frontend:** HTML5, Tailwind CSS, Font Awesome
- **Architecture:** Single-page PHP application with PDO

## Installation

### Prerequisites
- XAMPP (or any PHP + MySQL stack)
- PHP 7.4 or higher
- MySQL 5.7+

### Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   ```

2. **Move to htdocs**
   ```bash
   # For XAMPP on Windows
   copy the v2 folder to C:\xampp\htdocs\IMS
   ```

3. **Create the database**
   ```bash
   cd C:\xampp\htdocs\IMS\v2
   "C:\xampp\mysql\bin\mysql.exe" -u root < database.sql
   ```

4. **Start Apache and MySQL** in XAMPP Control Panel

5. **Access the application**
   ```
   http://localhost/IMS/v2/
   ```

### Login Credentials
- **Username:** admin
- **Password:** admin123

## Database Schema

```
┌─────────────────┐
│     levels      │  Basic → Beginner → Elementary → ...
├─────────────────┤
│     rooms       │  Room A, Room B, Lab 1, ...
├─────────────────┤
│   time_slots    │  08:00-09:00, 09:00-10:00, ...
├─────────────────┤
│     teachers    │  Name, email, qualification, ...
├─────────────────┤
│    subjects     │  Grammar, Speaking, Writing, ...
├─────────────────┤
│  teacher_subjects │  (links teachers to subjects)
├─────────────────┤
│     courses     │  IELTS Prep, Business English, ...
├─────────────────┤
│  course_subjects │  (links courses to subjects)
├─────────────────┤
│     classes    │  Room + Time Slot + Level + Courses
├─────────────────┤
│   class_courses │  (links classes to courses)
├─────────────────┤
│    students    │  Name, class, enrollment date, status
├─────────────────┤
│   attendance   │  Student attendance records
├─────────────────┤
│     exams      │  Scheduled exams
├─────────────────┤
│  exam_results  │  Student marks and grades
├─────────────────┤
│  fee_structures│  Fee definitions per course
├─────────────────┤
│  fee_payments  │  Payment records
└─────────────────┘
```

## Project Structure

```
IMS/v2/
├── config.php          # Database connection & session
├── login.php           # Admin login
├── logout.php          # Session destroy
├── header.php          # Navigation sidebar
├── footer.php          # Footer scripts
│
├── index.php           # Dashboard
│
├── teachers.php        # Teacher management
├── teacher_create.php
├── teacher_edit.php
│
├── subjects.php        # Subject management
├── subject_create.php
├── subject_edit.php
│
├── courses.php         # Course management
├── course_create.php
├── course_edit.php
│
├── classes.php         # Class management
├── class_create.php
├── class_edit.php
├── class_view.php
├── class_delete.php
│
├── students.php        # Student management
│
├── attendance.php      # Attendance marking
├── get_students.php    # AJAX endpoint
│
├── exams.php           # Exam management
├── enter_marks.php    # Manual marks entry
├── import_marks.php    # CSV import
├── download_template.php # CSV template download
├── exam_results.php    # View results
│
├── fee_structures.php  # Fee management
├── fee_payments.php    # Record payments
├── receipt.php         # Print receipt
│
├── timeslots.php       # Time slot management
│
├── reports.php         # Analytics
│
├── database.sql        # Database schema
└── USE.md              # User guide
```

## How It Works

### Teacher Assignment
Teachers are assigned to **Subjects**, not directly to classes. When a class includes courses that have certain subjects, the teachers for those subjects automatically teach the class.

### Class Creation
1. Select a room and time slot
2. Choose a level (Basic, Beginner, Elementary, etc.)
3. Select one or more courses
4. Teachers are automatically assigned based on course subjects

### Student Enrollment
1. Add student with name
2. Assign to an active class
3. Student can be transferred, dropped out, or deleted

### Exam Flow
1. Schedule an exam for a class
2. Enter marks manually OR download CSV template
3. Fill in marks and import
4. View results with automatic grading

## Configuration

Edit `config.php` to change database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'english_institute');
define('DB_USER', 'root');
define('DB_PASS', '');
```

## Screenshots

### Dashboard
- Overview cards showing active classes, students, teachers, revenue
- Class schedule grid showing rooms and time slots
- Quick action buttons

### Exam Import
- Download pre-filled CSV template
- Upload completed CSV file
- View student list for reference

### Student Management
- Enroll new students
- Transfer between classes
- Mark as dropped out (with restore option)
- Delete permanently

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For issues or questions, please create an issue on GitHub.
