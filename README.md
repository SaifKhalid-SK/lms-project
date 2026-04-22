# EduLMS — Learning Management System

A complete web-based Learning Management System built with PHP, MySQL, and Bootstrap 5.

## Features

### Student Portal
- Register and auto-enroll in subjects by department and semester
- View subject cards with live attendance percentage
- Grade book with Quiz and Assignment breakdown
- Attendance records by date (Present/Absent)
- Announcement board per subject
- Weekly timetable
- Fee invoice and challan

### Teacher Portal
- View today's scheduled classes on dashboard
- Mark attendance for enrolled students
- Enter grades for quizzes and assignments
- Post announcements visible to students instantly
- View all assigned subjects

### Admin Panel
- Real-time online/offline status of students and teachers
- Search and view detailed profiles
- Add new teachers with login credentials
- Manage student fee records

## Tech Stack
- **Backend:** PHP (Procedural)
- **Database:** MySQL via XAMPP
- **Frontend:** HTML, CSS, Bootstrap 5, Font Awesome
- **Authentication:** PHP Sessions with role-based access

## Database
- 12 tables covering users, teachers, subjects,
  enrollments, attendance, grades, announcements,
  timetable and fees
- Cross-table username uniqueness enforcement
- Auto-enrollment on student registration

## Setup Instructions
1. Install XAMPP
2. Start Apache and MySQL
3. Create database `lms_db` in phpMyAdmin
4. Import `schema.sql` then `seed.sql`
5. Place project in `C:\xampp\htdocs\lms\`
6. Open `localhost/lms/login.php`

## Login Credentials (Demo)
| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| CS Teacher (Sem 1-4) | teacher1 | teach123 |
| CS Teacher (Sem 5-8) | teacher2 | teach123 |

## Project Structure
lms/
├── db.php
├── login.php
├── register.php
├── logout.php
├── student/
│   ├── dashboard.php
│   ├── subject.php
│   ├── timetable.php
│   ├── profile.php
│   └── fees.php
├── teacher/
│   ├── dashboard.php
│   ├── subject_manage.php
│   ├── all_subjects.php
│   └── profile.php
└── admin/
    ├── dashboard.php
    ├── student_detail.php
    ├── teacher_detail.php
    ├── add_teacher.php
    └── add_fees.php
