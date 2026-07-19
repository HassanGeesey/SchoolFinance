# IMS Refactoring Plan

## Phase 0: Admin-Only System (Single Role)

### Goal
Remove teacher login capability, strip role system, keep teachers as data records only.

### Changes

#### 0A. Config (`config.php`)
- Remove `isTeacher()` function
- Simplify `isAdmin()` to always return true
- Remove `requireAdmin()` function (never called anywhere)

#### 0B. Login (`login.php`)
- Hardcode `$_SESSION['role'] = 'admin'` always
- Remove teacher-specific session logic (teacher_id storage)
- Change footer text to "Admin access only"

#### 0C. Header/Sidebar (`header.php`)
- Remove `$is_teacher`, `$is_admin`, `$user_role` variables
- Remove all `if ($is_admin)` conditional wrappers around sidebar sections
- Change subtitle to always show 'Administration'
- Remove role from avatar tooltip

#### 0D. Dashboard (`index.php`)
- Remove `$is_admin` variable and if/else branching
- Remove entire teacher dashboard branch (welcome card, Mark Attendance/Enter Marks buttons, My Classes table)
- Remove teacher class queries
- Keep only admin dashboard view

#### 0E. Teacher Management
- **`teachers.php`**: Remove "Add Login" section, "Delete Login" section, teacher-user creation from CSV import
- **`teacher_create.php`**: Remove "Create Login Account" checkbox, username/password fields, user creation logic

#### 0F. Users Management (`users.php`)
- Remove ability to create 'teacher' role users
- Remove teacher from role dropdown

#### 0G. Database Schema (`database.sql`)
- Change `users.role` ENUM to `'admin'` only
- Remove `teachers.user_id` column and FK

---

## Phase 1: Delete Module Files (14 files)

Delete:
- `attendance.php`
- `subjects.php`, `subject_create.php`, `subject_edit.php`
- `courses.php`, `course_create.php`, `course_edit.php`
- `exams.php`, `exam.php`, `exam_results.php`
- `enter_marks.php`, `import_marks.php`, `download_template.php`

---

## Phase 2: Database Schema Cleanup

### DROP Tables
- `attendance`
- `subjects`, `teacher_subjects`
- `courses`, `course_subjects`, `class_courses`
- `exams`, `exam_results`, `student_results`, `class_exam_results`

### Remove FKs
- Remove `course_id` from `classes` table
- Remove `course_id` from `fee_structures` table

---

## Phase 3: Simplify Classes

### Add custom `name` field
- Add optional text input for class names (e.g., "Beginner Batch A")
- Currently auto-named from level name

### Remove course references
- **`class_create.php`**: Remove course queries, checkboxes, class_courses INSERTs
- **`class_edit.php`**: Same removals
- **`class_view.php`**: Remove exams card, courses card, attendance/exams action buttons
- **`class_delete.php`**: Remove attendance, class_courses, class_exam_results, exams cascade deletes

---

## Phase 4: Clean Up Navigation (`header.php`)

- Remove sidebar links: Courses, Attendance, Exams
- Remove breadcrumb titles for all deleted pages
- Remove "New Exam" from Quick Add dropdown
- Rename "My Classes" → "Classes"

---

## Phase 5: Clean Up Dashboard (`index.php`)

- Remove Attendance quick action button

---

## Phase 6: Clean Up Reports (`reports.php`)

- Remove Exams Held stat card
- Remove Attendance Overview section

---

## Phase 7: Clean Up Cascading Deletes

- **`students.php`**: Remove DELETE FROM attendance, exam_results, student_results
- **`class_delete.php`**: Remove DELETE FROM attendance, class_courses, class_exam_results, exams, exam_results

---

## Phase 8: Remove Subject References from Teachers

- **`teachers.php`**: Remove subject JOIN and subjects column
- **`teacher_create.php`**: Remove subject query, checkboxes, INSERTs
- **`teacher_edit.php`**: Remove subject query, assigned IDs, teacher_subjects DELETE/INSERT, subject checkboxes

---

## Phase 9: Clean Up Fee Structures (`fee_structures.php`)

- Remove `course_id` references
- Remove "Link to Course" dropdown
- Remove "Course" column from table

---

## Phase 10: Verify

- Test login, dashboard, class CRUD, student management, teacher management, fee management
- Ensure no broken references remain
