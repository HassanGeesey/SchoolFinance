# English Institute Management System - User Guide

## Getting Started

### Login
- Go to `http://localhost/IMS/v2/`
- Username: `admin`
- Password: `admin123`

---

## Dashboard
After login, you see the Dashboard showing:
- Total active classes
- Total students
- Total teachers
- This month's revenue
- Class schedule grid

---

## Teachers

Teachers are assigned to **Subjects**, not directly to classes. When a class has courses with certain subjects, the teachers for those subjects automatically teach the class.

### Add Teacher
1. Click **Teachers** in the sidebar
2. Click **Add Teacher** button
3. Fill in: Name, Email, Phone, Qualification, Specialization, Experience
4. Select the **Subjects** this teacher can teach
5. Click **Save Teacher**

### Edit Teacher
1. Go to **Teachers** page
2. Click the **Edit** button next to a teacher
3. Update the details and subjects
4. Click **Update Teacher**

---

## Subjects

### Add Subject
1. Click **Subjects** in the sidebar
2. Click **Add Subject** button
3. Fill in: Name, Code, Description, Credit Hours
4. Click **Save Subject**

---

## Courses

### What is a Course?
A course is a program that groups multiple subjects together.
Example: "IELTS Preparation" course includes Speaking, Writing, Reading, and Grammar.

### Add Course
1. Click **Courses** in the sidebar
2. Click **Add Course** button
3. Fill in: Name, Code, Description, Duration (months), Fee
4. Select the subjects for this course
5. Click **Save Course**

---

## Classes

### What is a Class?
A class is a scheduled session with:
- A room
- A time slot (day/time)
- A teacher
- A level (Basic, Beginner, Elementary, etc.)
- One or more courses

### Create Class
1. Click **Classes** in the sidebar
2. Click **New Class** button (or quick button on dashboard)
3. Fill in:
   - Class Name (e.g., "Morning IELTS Batch")
   - Select Room
   - Select Time Slot
   - Select Level
   - Select one or more Courses (teachers will be assigned based on course subjects)
   - Select Days of Week (e.g., Mon, Wed, Fri)
   - Start Date
4. Click **Create Class**

**Note:** Teachers are automatically assigned based on the subjects in the selected courses.

### View Class
1. Go to **Classes** page
2. Click on any class to see:
   - Class details
   - Enrolled students
   - Assigned courses
   - Exam history

### Edit Class
Click the **Edit** button on any class page.

### Delete Class
Click the **Delete** button on any class page. Students will be unassigned but not deleted.

---

## Students

### Enroll Student
1. Click **Students** in the sidebar
2. Fill in the student name
3. Select a class from the dropdown
4. Click **Enroll**

### Transfer Student
1. Go to **Students** page
2. Use the **Move** dropdown next to a student
3. Select the new class
4. Click **Transfer**

### Drop Out Student
1. Go to **Students** page
2. Click the **orange icon** (user with slash) next to a student
3. The student will be marked as "dropped" and unassigned from their class
4. They can be restored later using the **green icon** (undo)

### Delete Student
Click the **red trash icon** to permanently delete a student from the system.

---

## Attendance

### Mark Attendance
1. Click **Attendance** in the sidebar
2. Select a **Class** from the dropdown
3. Select the **Date**
4. For each student, select: Present, Absent, Late, or Excused
5. Click **Save Attendance**

---

## Exams

### Schedule Exam
1. Go to **Exams** page
2. Fill in: Class, Exam Name, Date, Type, Total Marks, Passing Marks
3. Click **Schedule Exam**

### Enter Marks (Manual)
1. Go to **Exams** page
2. Click **Manual** button on a scheduled exam
3. Enter marks for each student
4. Click **Save & Complete Exam**

### Import Marks from CSV
1. Go to **Exams** page
2. Click **Import** button on a scheduled exam
3. Click **Download Template** to get a pre-filled CSV
4. Fill in the marks in the CSV file
5. Upload the CSV file
6. Click **Import Marks**

**CSV Format:**
```
student_id, student_name, marks_obtained
1, John Smith, 85
2, Jane Doe, 92
```

### Delete Exam
Click the **trash icon** on any exam to delete it and all its results.

---

## Fee Structure

### Set Up Fees
1. Click **Fee Structure** in the sidebar
2. Click **Add Fee Structure**
3. Select a course, enter amount, and fee type (monthly, quarterly, yearly, one-time)
4. Click **Save**

### Record Payment
1. Click **Payments** in the sidebar
2. Click **Record Payment**
3. Select student, fee structure, enter amount and payment date
4. Click **Save Payment**
5. Click **Print Receipt** to print a receipt

---

## Time Slots

### Add Time Slot
1. Click **Time Slots** in the sidebar
2. Enter start time and end time
3. Click **Add Time Slot**

### Delete Time Slot
- Click **Delete** next to a time slot
- Cannot delete if used by an active class

---

## Reports

Click **Reports** in the sidebar to see:
- Total students per class
- Attendance statistics
- Revenue reports
- Exam results

---

## Quick Tips

1. **Navigation**: Use the sidebar to move between pages
2. **Back buttons**: Every page has a back button to return to previous page
3. **Delete**: Always confirm before deleting anything
4. **Edit**: Use Edit buttons to modify existing records

---

## Order of Setup

When starting fresh, follow this order:

1. Add **Teachers**
2. Add **Subjects**
3. Add **Courses** (link subjects to courses)
4. Add **Time Slots** (if needed)
5. Create **Classes** (assign teacher, room, time, courses)
6. Enroll **Students** to classes
7. Set up **Fee Structure**
8. Start taking **Attendance** and **Exams**
