-- PeerTutoringMatchmaker Database Schema
-- Import this file into phpMyAdmin

-- Drop existing tables if they exist (in correct order due to foreign keys)
DROP TABLE IF EXISTS Message;
DROP TABLE IF EXISTS Notification;
DROP TABLE IF EXISTS Review;
DROP TABLE IF EXISTS Cancellation;
DROP TABLE IF EXISTS RescheduleRequest;
DROP TABLE IF EXISTS Appointment;
DROP TABLE IF EXISTS Availability;
DROP TABLE IF EXISTS Service;
DROP TABLE IF EXISTS Category;
DROP TABLE IF EXISTS ProviderProfile;
DROP TABLE IF EXISTS Users;
DROP TABLE IF EXISTS Roles;

-- Create Roles table
CREATE TABLE Roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Users table
CREATE TABLE Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(255),
    role_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES Roles(id)
);

-- Create Category table
CREATE TABLE Category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create ProviderProfile table
CREATE TABLE ProviderProfile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    bio TEXT,
    qualifications TEXT,
    experience_years INT DEFAULT 0,
    hourly_rate DECIMAL(10, 2),
    profile_image VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    rating_average DECIMAL(3, 2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- Create Service table
CREATE TABLE Service (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    duration_minutes INT DEFAULT 60,
    price DECIMAL(10, 2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES ProviderProfile(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES Category(id)
);

-- Create Availability table
CREATE TABLE Availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_recurring BOOLEAN DEFAULT TRUE,
    specific_date DATE DEFAULT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES ProviderProfile(id) ON DELETE CASCADE
);

-- Create Appointment table
CREATE TABLE Appointment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    provider_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'rescheduled', 'no_show') DEFAULT 'pending',
    client_notes TEXT,
    provider_notes TEXT,
    total_price DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES Users(id),
    FOREIGN KEY (provider_id) REFERENCES ProviderProfile(id),
    FOREIGN KEY (service_id) REFERENCES Service(id)
);

-- Create RescheduleRequest table
CREATE TABLE RescheduleRequest (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    requested_by INT NOT NULL,
    original_date DATE NOT NULL,
    original_start_time TIME NOT NULL,
    new_date DATE NOT NULL,
    new_start_time TIME NOT NULL,
    new_end_time TIME NOT NULL,
    reason TEXT,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES Appointment(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES Users(id)
);

-- Create Cancellation table
CREATE TABLE Cancellation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    cancelled_by INT NOT NULL,
    reason TEXT NOT NULL,
    cancelled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES Appointment(id) ON DELETE CASCADE,
    FOREIGN KEY (cancelled_by) REFERENCES Users(id)
);

-- Create Review table
CREATE TABLE Review (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL UNIQUE,
    reviewer_id INT NOT NULL,
    provider_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    is_approved BOOLEAN DEFAULT TRUE,
    is_flagged BOOLEAN DEFAULT FALSE,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES Appointment(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES Users(id),
    FOREIGN KEY (provider_id) REFERENCES ProviderProfile(id)
);

-- Create Message table
CREATE TABLE Message (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    appointment_id INT DEFAULT NULL,
    subject VARCHAR(255),
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES Users(id),
    FOREIGN KEY (receiver_id) REFERENCES Users(id),
    FOREIGN KEY (appointment_id) REFERENCES Appointment(id) ON DELETE SET NULL
);

-- Create Notification table
CREATE TABLE Notification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- Insert default roles
INSERT INTO Roles (name, description) VALUES
('admin', 'Full system access and management capabilities'),
('provider', 'Tutoring service provider with profile and availability management'),
('user', 'Registered user who can book tutoring sessions');

-- Insert sample categories
INSERT INTO Category (name, description, icon) VALUES
('Mathematics', 'Algebra, Calculus, Statistics, and more', 'math'),
('Science', 'Physics, Chemistry, Biology', 'science'),
('Languages', 'English, Spanish, French, and other languages', 'language'),
('Computer Science', 'Programming, Data Structures, Web Development', 'computer'),
('Business', 'Accounting, Economics, Marketing', 'business'),
('Arts', 'Music, Drawing, Design', 'arts'),
('Test Prep', 'SAT, GRE, GMAT preparation', 'test'),
('Writing', 'Essay writing, Creative writing, Academic writing', 'writing');

-- Insert sample admin user (password: admin123)
INSERT INTO Users (email, password, first_name, last_name, phone, location, role_id) VALUES
('admin@peertutoring.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Admin', '555-0100', 'Main Campus', 1);

-- Insert sample providers (password: password123)
INSERT INTO Users (email, password, first_name, last_name, phone, location, role_id) VALUES
('john.tutor@peertutoring.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', '555-0101', 'Science Building', 2),
('sarah.tutor@peertutoring.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Johnson', '555-0102', 'Library', 2),
('mike.tutor@peertutoring.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike', 'Williams', '555-0103', 'Computer Lab', 2);

-- Insert sample regular users (password: password123)
INSERT INTO Users (email, password, first_name, last_name, phone, location, role_id) VALUES
('alice.student@peertutoring.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice', 'Brown', '555-0201', 'Dormitory A', 3),
('bob.student@peertutoring.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob', 'Davis', '555-0202', 'Dormitory B', 3);

-- Insert provider profiles
INSERT INTO ProviderProfile (user_id, bio, qualifications, experience_years, hourly_rate, is_verified, rating_average, total_reviews) VALUES
(2, 'Experienced math and physics tutor with a passion for helping students understand complex concepts.', 'B.S. in Physics, M.S. in Mathematics', 5, 35.00, TRUE, 4.8, 24),
(3, 'Language enthusiast specializing in English and Spanish tutoring for all levels.', 'B.A. in Linguistics, TESOL Certified', 3, 30.00, TRUE, 4.6, 18),
(4, 'Computer science graduate with industry experience. Specializing in programming and web development.', 'B.S. in Computer Science, AWS Certified', 4, 40.00, TRUE, 4.9, 32);

-- Insert sample services
INSERT INTO Service (provider_id, category_id, title, description, duration_minutes, price) VALUES
(1, 1, 'Calculus Tutoring', 'One-on-one calculus tutoring covering derivatives, integrals, and applications.', 60, 35.00),
(1, 2, 'Physics Fundamentals', 'Physics tutoring for mechanics, thermodynamics, and electromagnetism.', 60, 35.00),
(2, 3, 'English Conversation', 'Practice English conversation skills with a native speaker.', 45, 25.00),
(2, 3, 'Spanish for Beginners', 'Learn Spanish from scratch with interactive lessons.', 60, 30.00),
(3, 4, 'Python Programming', 'Learn Python programming from basics to advanced concepts.', 60, 40.00),
(3, 4, 'Web Development', 'HTML, CSS, and JavaScript fundamentals for web development.', 90, 55.00);

-- Insert sample availability
INSERT INTO Availability (provider_id, day_of_week, start_time, end_time) VALUES
(1, 1, '09:00:00', '12:00:00'),
(1, 1, '14:00:00', '17:00:00'),
(1, 3, '09:00:00', '12:00:00'),
(1, 3, '14:00:00', '17:00:00'),
(1, 5, '10:00:00', '15:00:00'),
(2, 2, '10:00:00', '14:00:00'),
(2, 4, '10:00:00', '14:00:00'),
(2, 6, '09:00:00', '13:00:00'),
(3, 1, '13:00:00', '18:00:00'),
(3, 2, '13:00:00', '18:00:00'),
(3, 4, '13:00:00', '18:00:00');

-- Insert sample appointments
INSERT INTO Appointment (client_id, provider_id, service_id, appointment_date, start_time, end_time, status, client_notes, total_price) VALUES
(5, 1, 1, CURDATE() + INTERVAL 2 DAY, '10:00:00', '11:00:00', 'confirmed', 'Need help with integration techniques', 35.00),
(6, 3, 5, CURDATE() + INTERVAL 3 DAY, '14:00:00', '15:00:00', 'pending', 'Want to learn Python basics', 40.00),
(5, 2, 3, CURDATE() - INTERVAL 5 DAY, '11:00:00', '11:45:00', 'completed', 'Preparing for English interview', 25.00);

-- Insert sample review for completed appointment
INSERT INTO Review (appointment_id, reviewer_id, provider_id, rating, comment) VALUES
(3, 5, 2, 5, 'Sarah was an excellent tutor! She helped me prepare for my interview and gave great tips. Highly recommended!');

-- Update provider rating based on review
UPDATE ProviderProfile SET rating_average = 5.0, total_reviews = 1 WHERE id = 2;

-- Insert sample notifications
INSERT INTO Notification (user_id, type, title, message, link) VALUES
(5, 'booking_confirmed', 'Booking Confirmed', 'Your calculus tutoring session with John Smith has been confirmed for ' || DATE_FORMAT(CURDATE() + INTERVAL 2 DAY, '%M %d, %Y'), 'MyBookings.php'),
(2, 'new_booking', 'New Booking Request', 'You have a new booking request from Alice Brown for Calculus Tutoring', 'ProviderDashboard.php'),
(6, 'booking_pending', 'Booking Submitted', 'Your booking request for Python Programming with Mike Williams is pending confirmation', 'MyBookings.php');
