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

## How To Run This Project Locally

### Requirements
- XAMPP (Apache + MySQL)
- Any browser

### Setup Steps

1. Download or clone this repository
   git clone https://github.com/SaifKhalid-SK/lms-project.git

2. Copy the project folder to:
   C:\xampp\htdocs\lms\

3. Start XAMPP
   - Start Apache
   - Start MySQL

4. Create the database
   - Open localhost/phpmyadmin
   - Create a new database named: lms_db
   - Click Import tab
   - Choose file: database/lms_complete.sql
   - Click Go

5. Open the project
   localhost/lms/login.php

### Demo Login Credentials

| Role       | Username  | Password  |
|------------|-----------|-----------|
| Admin      | admin     | admin123  |
| CS Teacher | teacher1  | teach123  |
| CS Teacher | teacher2  | teach123  |
| BA Teacher | teacher3  | teach123  |
| BA Teacher | teacher4  | teach123  |
| EE Teacher | teacher5  | teach123  |
| EE Teacher | teacher6  | teach123  |
| Student    | (register via register.php) | - |

## Project Structure
lms/
├── db.php
├── login.php
├── register.php
├── logout.php
├── Database/
|   ├──lms_complete.sql
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
