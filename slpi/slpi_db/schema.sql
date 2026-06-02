-- ============================================================
--  SLPI Workshop Hub — Full MySQL Database Schema
--  File: schema.sql
--  Run: mysql -u root -p slpi_db < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS slpi_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE slpi_db;

-- ─────────────────────────────────────────
-- 1. ADMIN USERS  (admin1 / admin2 / super)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(50)  NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
  role        ENUM('admin1','admin2','super') NOT NULL,
  label       VARCHAR(60)  NOT NULL,
  email       VARCHAR(120) NOT NULL UNIQUE,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- 2. PUBLIC USERS  (login / signup pages)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  email       VARCHAR(120) NOT NULL UNIQUE,
  phone       VARCHAR(30)  DEFAULT NULL,
  nic         VARCHAR(20)  DEFAULT NULL COMMENT 'National ID / NIC',
  workplace   VARCHAR(120) DEFAULT NULL,
  password    VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- 3. TRAINERS  (trainers.html)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS trainers (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  role        VARCHAR(120) NOT NULL COMMENT 'Job title / speciality',
  bio         TEXT         DEFAULT NULL,
  avatar_url  VARCHAR(500) DEFAULT NULL,
  email       VARCHAR(120) DEFAULT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- 4. WORKSHOPS  (admin1 / admin2 owned)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS workshops (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug             VARCHAR(80)  NOT NULL UNIQUE COMMENT 'URL-safe identifier e.g. digital-journalism',
  owner            ENUM('admin1','admin2') NOT NULL,
  trainer_id       INT UNSIGNED DEFAULT NULL,
  title            VARCHAR(200) NOT NULL,
  category         ENUM('Digital','Ethics','Video','Writing','Photography','Other') NOT NULL DEFAULT 'Other',
  description      TEXT         DEFAULT NULL,
  image_url        VARCHAR(500) DEFAULT NULL,
  workshop_date    DATE         DEFAULT NULL,
  workshop_time    VARCHAR(60)  DEFAULT NULL COMMENT 'e.g. 9:00 AM – 5:00 PM',
  location         VARCHAR(200) DEFAULT NULL,
  max_participants SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  is_active        TINYINT(1)   NOT NULL DEFAULT 1,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ws_trainer FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- 5. ENROLLMENTS  (admin panels + profile)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS enrollments (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workshop_id  INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED NOT NULL,
  name         VARCHAR(120) NOT NULL COMMENT 'Snapshot at enroll time',
  email        VARCHAR(120) NOT NULL,
  phone        VARCHAR(30)  DEFAULT NULL,
  nic          VARCHAR(20)  DEFAULT NULL,
  workplace    VARCHAR(120) DEFAULT NULL,
  status       ENUM('enrolled','selected','rejected') NOT NULL DEFAULT 'enrolled',
  is_selected  TINYINT(1)   NOT NULL DEFAULT 0,
  enrolled_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_enroll (workshop_id, user_id),
  CONSTRAINT fk_enroll_ws   FOREIGN KEY (workshop_id) REFERENCES workshops(id) ON DELETE CASCADE,
  CONSTRAINT fk_enroll_user FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- 6. ATTENDANCE  (profile.html stats)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS attendance (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  enrollment_id INT UNSIGNED NOT NULL UNIQUE,
  attended      TINYINT(1)   NOT NULL DEFAULT 0,
  marked_by     INT UNSIGNED DEFAULT NULL COMMENT 'admin.id who recorded it',
  notes         TEXT         DEFAULT NULL,
  marked_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_att_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- 7. EMAIL LOG  (rejection emails)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS email_log (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  enrollment_id INT UNSIGNED DEFAULT NULL COMMENT 'NULL if enrollment deleted',
  workshop_id   INT UNSIGNED DEFAULT NULL,
  user_id       INT UNSIGNED DEFAULT NULL,
  recipient_name  VARCHAR(120) NOT NULL,
  recipient_email VARCHAR(120) NOT NULL,
  workshop_title  VARCHAR(200) NOT NULL COMMENT 'Snapshot',
  subject         VARCHAR(300) NOT NULL,
  body            TEXT         NOT NULL,
  sent_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_email_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE SET NULL,
  CONSTRAINT fk_email_workshop   FOREIGN KEY (workshop_id)   REFERENCES workshops(id)   ON DELETE SET NULL,
  CONSTRAINT fk_email_user       FOREIGN KEY (user_id)       REFERENCES users(id)        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- 8. USER NOTIFICATIONS  (profile.html)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS user_notifications (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NOT NULL,
  enrollment_id   INT UNSIGNED DEFAULT NULL,
  type            ENUM('enrolled','selected','rejected','deselected') NOT NULL,
  subject         VARCHAR(300) NOT NULL,
  body            TEXT         NOT NULL,
  workshop_title  VARCHAR(200) NOT NULL,
  is_read         TINYINT(1)   NOT NULL DEFAULT 0,
  sent_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- 9. TESTIMONIALS / FEEDBACK  (index.html)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS testimonials (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED DEFAULT NULL COMMENT 'NULL for anonymous',
  author_name VARCHAR(120) NOT NULL,
  author_role VARCHAR(120) DEFAULT NULL,
  message     TEXT         NOT NULL,
  avatar_url  VARCHAR(500) DEFAULT NULL,
  is_approved TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_testimonial_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- 10. RESOURCES  (resources.html)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS resources (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(200) NOT NULL,
  description  TEXT         DEFAULT NULL,
  type         ENUM('PDF','Video','Guide','Other') NOT NULL DEFAULT 'Other',
  link_url     VARCHAR(500) DEFAULT NULL,
  is_active    TINYINT(1)   NOT NULL DEFAULT 1,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- 11. UPCOMING EVENTS  (upcoming.html)
--     (mirrors workshops but also standalone)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS upcoming_events (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workshop_id  INT UNSIGNED DEFAULT NULL COMMENT 'Link to workshops table if applicable',
  title        VARCHAR(200) NOT NULL,
  schedule     VARCHAR(200) DEFAULT NULL COMMENT 'e.g. 9:00 AM – 5:00 PM',
  location     VARCHAR(200) DEFAULT NULL,
  trainer_name VARCHAR(120) DEFAULT NULL,
  event_date   DATE         DEFAULT NULL,
  status       ENUM('Open','Filling Up','Closed','Cancelled') NOT NULL DEFAULT 'Open',
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_event_workshop FOREIGN KEY (workshop_id) REFERENCES workshops(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- 12. ADMIN SESSIONS  (server-side sessions)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_sessions (
  id         CHAR(64)     NOT NULL PRIMARY KEY COMMENT 'SHA-256 token',
  admin_id   INT UNSIGNED NOT NULL,
  ip_address VARCHAR(45)  DEFAULT NULL,
  user_agent TEXT         DEFAULT NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME     NOT NULL,
  CONSTRAINT fk_sess_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- SEED DATA
-- ─────────────────────────────────────────

-- Admins (passwords are bcrypt of: Admin1@2025 / Admin2@2025 / Super@2025)
INSERT INTO admins (username, password, role, label, email) VALUES
('admin1', '$2y$12$placeholder_hash_admin1', 'admin1', 'Admin 1', 'admin1@slpi.lk'),
('admin2', '$2y$12$placeholder_hash_admin2', 'admin2', 'Admin 2', 'admin2@slpi.lk'),
('super',  '$2y$12$placeholder_hash_super',  'super',  'Super Admin', 'super@slpi.lk');

-- Trainers
INSERT INTO trainers (name, role, bio, avatar_url) VALUES
('Sarah Johnson', 'Digital Journalism Specialist',
 '15+ years covering digital media transitions. Former editor at three national publications and TEDx speaker.',
 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=200&h=200&fit=crop'),
('Michael Chen', 'Media Ethics Advisor',
 'Former press freedom advocate with 20 years in investigative reporting. Author of two books on ethical journalism.',
 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=200&h=200&fit=crop'),
('Emma Wilson', 'Broadcast & Video Journalist',
 'Emmy-nominated documentary filmmaker with a decade of on-the-ground reporting across Asia and the Middle East.',
 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=200&h=200&fit=crop'),
('David Park', 'Investigative Reporter',
 'Pulitzer Prize finalist. Expert in data-driven investigations, FOIA strategies, and source protection.',
 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop'),
('Amara Perera', 'Photojournalism Lead',
 'Award-winning photojournalist whose work has appeared in Time, National Geographic, and Reuters.',
 'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=200&h=200&fit=crop'),
('Raj Nair', 'Data Journalism Expert',
 'Former data scientist turned journalist. Pioneered interactive data storytelling formats.',
 'https://images.unsplash.com/photo-1519345182560-3f2917c472ef?w=200&h=200&fit=crop');

-- Workshops
INSERT INTO workshops (slug, owner, trainer_id, title, category, description, image_url, workshop_date, workshop_time, location, max_participants, is_active) VALUES
('digital-journalism', 'admin1', 1, 'Digital Journalism Fundamentals', 'Digital',
 'Master the essentials of digital news reporting, social media storytelling, and online publishing platforms.',
 'https://images.unsplash.com/photo-1488590528505-98d2b5aba04b?w=600&h=300&fit=crop',
 '2025-06-15', '9:00 AM – 5:00 PM', 'SLPI Auditorium, Colombo', 30, 1),

('media-ethics', 'admin1', 2, 'Media Ethics & Integrity', 'Ethics',
 'Explore the ethical frameworks that guide responsible journalism, press freedom, and accountability reporting.',
 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=600&h=300&fit=crop',
 '2025-07-03', '10:00 AM – 4:00 PM', 'Online (Zoom)', 25, 1),

('video-storytelling', 'admin2', 3, 'Video Storytelling Workshop', 'Video',
 'Learn cinematic techniques for broadcast journalism, documentary production, and mobile video reporting.',
 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=600&h=300&fit=crop',
 '2025-07-20', '9:00 AM – 6:00 PM', 'Media Lab, SLPI', 20, 1),

('investigative-reporting', 'admin2', 4, 'Investigative Reporting', 'Writing',
 'Deep-dive into source protection, FOIA requests, data analysis, and long-form investigative storytelling.',
 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=600&h=300&fit=crop',
 '2025-08-05', '9:00 AM – 5:00 PM', 'SLPI Conference Room', 20, 1),

('data-journalism', 'admin1', 1, 'Data Journalism & Visualization', 'Digital',
 'Transform raw data into compelling stories using modern tools, charts, and interactive visualizations.',
 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=600&h=300&fit=crop',
 '2025-08-18', '10:00 AM – 5:00 PM', 'Online (Zoom)', 30, 1),

('news-photography', 'admin2', 3, 'News Photography', 'Writing',
 'Develop your visual narrative skills — from breaking news shots to portrait photography.',
 'https://images.unsplash.com/photo-1456324504439-367cee3b3c32?w=600&h=300&fit=crop',
 '2025-09-02', '8:00 AM – 4:00 PM', 'SLPI Studio, Colombo', 15, 1);

-- Demo public user (password: Demo1234)
INSERT INTO users (name, email, phone, nic, workplace, password) VALUES
('Demo User', 'demo@example.com', '+94711234567', '199900001234', 'Freelance', '$2y$12$placeholder_demo_hash');

-- Upcoming Events (mirrors workshops + standalone)
INSERT INTO upcoming_events (workshop_id, title, schedule, location, trainer_name, event_date, status) VALUES
(1, 'Digital Journalism Fundamentals', '9:00 AM – 5:00 PM', 'SLPI Auditorium, Colombo', 'Sarah Johnson',  '2025-06-15', 'Open'),
(2, 'Media Ethics & Integrity',        '10:00 AM – 4:00 PM','Online (Zoom)',             'Michael Chen',   '2025-07-03', 'Open'),
(3, 'Video Storytelling Workshop',     '9:00 AM – 6:00 PM', 'Media Lab, SLPI',           'Emma Wilson',    '2025-07-20', 'Open'),
(4, 'Investigative Reporting Masterclass','9:00 AM – 5:00 PM','SLPI Conference Room',    'David Park',     '2025-08-05', 'Filling Up'),
(5, 'Data Journalism & Visualization', '10:00 AM – 5:00 PM','Online (Zoom)',             'Sarah Johnson',  '2025-08-18', 'Open'),
(6, 'News Photography',                '8:00 AM – 4:00 PM', 'SLPI Studio, Colombo',      'Amara Perera',   '2025-09-02', 'Open'),
(NULL,'Mobile Journalism (MoJo)',       '9:00 AM – 5:00 PM', 'SLPI Media Lab',           'Emma Wilson',    '2025-09-20', 'Filling Up'),
(NULL,'Digital Security for Journalists','10:00 AM – 3:00 PM','Online (Zoom)',            'Raj Nair',       '2025-10-10', 'Open');

-- Resources
INSERT INTO resources (title, description, type, link_url, is_active) VALUES
('SLPI Ethics Guidelines', 'A comprehensive guide to ethical journalism practice in Sri Lanka, covering source protection, accuracy, and editorial independence.', 'PDF', '#', 1),
('Introduction to Data Journalism', 'A two-hour recorded workshop session covering spreadsheet basics, data cleaning, and simple visualisation techniques for newsrooms.', 'Video', '#', 1),
('Digital Security for Journalists', 'Best practices for protecting yourself and your sources online — covering encrypted communications, password hygiene, and device security.', 'Guide', '#', 1),
('Story Structure Templates', 'Downloadable templates for news stories, long-form features, and investigative reports to help structure your reporting effectively.', 'PDF', '#', 1),
('Interview Techniques Masterclass', 'Expert guidance on planning interviews, building rapport, handling hostile subjects, and capturing compelling quotes.', 'Video', '#', 1),
('Social Media Verification Toolkit', 'Practical tools and workflows for verifying user-generated content, photos, and videos circulating on social media platforms.', 'Guide', '#', 1),
('Sri Lanka Press Law Handbook', 'An accessible overview of press freedom laws, defamation regulations, and journalist rights under Sri Lankan legislation.', 'PDF', '#', 1),
('Mobile Journalism (MoJo) Workshop', 'A recorded session on shooting, editing, and publishing professional-quality video reports using only a smartphone.', 'Video', '#', 1),
('Trauma-Informed Reporting', 'Guidelines for covering sensitive stories involving trauma, violence, and marginalised communities with care and dignity.', 'Guide', '#', 1);

-- Testimonials
INSERT INTO testimonials (author_name, author_role, message, avatar_url, is_approved) VALUES
('Jane Doe',   'Student Journalist',  'The SLPI workshops really enhanced my reporting skills. The instructors were outstanding and the practical exercises were invaluable.',
 'https://randomuser.me/api/portraits/women/44.jpg', 1),
('John Smith', 'Media Professional',  'Excellent instructors and very practical exercises. I learned a lot from the digital journalism course. The content was relevant and up-to-date.',
 'https://randomuser.me/api/portraits/men/46.jpg', 1),
('Emily Davis','Journalism Enthusiast','The workshops are engaging and very professional. A great experience overall — I''ve already signed up for my second course with SLPI!',
 'https://randomuser.me/api/portraits/women/65.jpg', 1);

-- Sample enrollments (uses demo user id=1)
INSERT INTO enrollments (workshop_id, user_id, name, email, phone, nic, workplace, status, is_selected) VALUES
(1, 1, 'Amal Perera',         'amal@email.com',    '0771234567', '199012345678', 'Daily Mirror',    'enrolled', 0),
(2, 1, 'Nadia Fernando',      'nadia@email.com',   '0779876543', '199512345678', 'Independent',     'enrolled', 0),
(3, 1, 'Kasun Silva',         'kasun@email.com',   '0772345678', '200012345678', 'Student',         'enrolled', 0),
(4, 1, 'Priya Raj',           'priya@email.com',   '0773456789', '199712345678', 'Colombo Gazette', 'enrolled', 0),
(5, 1, 'Sampath Wijesinghe',  'sampath@email.com', '0774567890', '200112345678', 'Freelance',       'enrolled', 0),
(6, 1, 'Tharushi Bandara',    'tharushi@email.com','0775678901', '199812345678', 'TV1',             'enrolled', 0);
