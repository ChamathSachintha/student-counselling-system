-- Student Counselling System Database with dummy data and admin account

CREATE DATABASE IF NOT EXISTS student_counselling;

USE student_counselling;


SET FOREIGN_KEY_CHECKS = 0;


DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;

-- USERS TABLE

CREATE TABLE users (

    user_id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,

    email VARCHAR(100) NOT NULL UNIQUE,

    password VARCHAR(255) NOT NULL,

    role ENUM(
        'student',
        'counselor',
        'admin'
    )
    DEFAULT 'student'

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- STUDENTS TABLE

CREATE TABLE students (

    student_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    student_number VARCHAR(30),

    course VARCHAR(100),

    year INT,

    phone VARCHAR(20),


    CONSTRAINT students_user_fk

    FOREIGN KEY (user_id)

    REFERENCES users(user_id)

    ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- APPOINTMENTS TABLE

CREATE TABLE appointments (

    appointment_id INT AUTO_INCREMENT PRIMARY KEY,


    student_id INT NOT NULL,


    counselor_id INT NOT NULL,


    appointment_date DATE NOT NULL,


    appointment_time TIME NOT NULL,


    reason VARCHAR(255),


    status ENUM(

        'Pending',

        'Approved',

        'Rejected',

        'Completed',

        'Cancelled'

    )

    DEFAULT 'Pending',



    CONSTRAINT appointments_student_fk

    FOREIGN KEY(student_id)

    REFERENCES students(student_id)

    ON DELETE CASCADE,



    CONSTRAINT appointments_counselor_fk

    FOREIGN KEY(counselor_id)

    REFERENCES users(user_id)

    ON DELETE CASCADE


) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- MESSAGES TABLE

CREATE TABLE messages (

    message_id INT AUTO_INCREMENT PRIMARY KEY,


    sender_id INT NOT NULL,


    receiver_id INT NOT NULL,


    message TEXT NOT NULL,


    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,



    CONSTRAINT messages_sender_fk

    FOREIGN KEY(sender_id)

    REFERENCES users(user_id)

    ON DELETE CASCADE,



    CONSTRAINT messages_receiver_fk

    FOREIGN KEY(receiver_id)

    REFERENCES users(user_id)

    ON DELETE CASCADE


) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- FEEDBACK TABLE

CREATE TABLE feedback (

    feedback_id INT AUTO_INCREMENT PRIMARY KEY,


    student_id INT NOT NULL,


    counselor_id INT NOT NULL,


    rating INT NOT NULL,


    comment VARCHAR(500),



    CONSTRAINT feedback_student_fk

    FOREIGN KEY(student_id)

    REFERENCES students(student_id)

    ON DELETE CASCADE,



    CONSTRAINT feedback_counselor_fk

    FOREIGN KEY(counselor_id)

    REFERENCES users(user_id)

    ON DELETE CASCADE


) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



SET FOREIGN_KEY_CHECKS = 1;

-- DUMMY USERS

-- Demo passwords are plain text: user, sarah, and admin.

INSERT INTO users
(
    user_id,
    name,
    email,
    password,
    role
)
VALUES

(
    1,
    'user',
    'user@gmail.com',
    'user',
    'student'
),

(
    2,
    'Sarah Perera',
    'sarah@gmail.com',
    'sarah',
    'counselor'
),

(
    3,
    'admin',
    'admin@gmail.com',
    'admin',
    'admin'
);


-- DUMMY STUDENT PROFILE

INSERT INTO students
(
    student_id,
    user_id,
    student_number,
    course,
    year,
    phone
)
VALUES

(
    1,
    1,
    'STU001',
    'Information Technology',
    2,
    '0771234567'
);