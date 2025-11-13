-- CourseworkWeb1V2 Database Schema (v2)
-- Minimal Q&A schema focusing on users, modules, questions, contacts

CREATE DATABASE IF NOT EXISTS courseworkweb1v2;

USE courseworkweb1v2;

DROP TABLE IF EXISTS question_votes;
DROP TABLE IF EXISTS answers;
DROP TABLE IF EXISTS question_tags;
DROP TABLE IF EXISTS contacts;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS modules;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tags created after questions (see below)

CREATE TABLE modules (
  module_id INT AUTO_INCREMENT PRIMARY KEY,
  module_code VARCHAR(20) NOT NULL UNIQUE,
  module_name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  content TEXT NOT NULL,
  image VARCHAR(255) DEFAULT NULL,
  user_id INT NOT NULL,
  module_id INT NOT NULL,
  status ENUM('pending','approved','rejected') DEFAULT 'approved',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_q_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_q_module FOREIGN KEY (module_id) REFERENCES modules(module_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tags (after questions exist)
CREATE TABLE tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE question_tags (
  question_id INT NOT NULL,
  tag_id INT NOT NULL,
  PRIMARY KEY (question_id, tag_id),
  CONSTRAINT fk_qt_q FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  CONSTRAINT fk_qt_t FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Answers table (each answer belongs to a question and a user)
CREATE TABLE answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question_id INT NOT NULL,
  user_id INT NOT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_a_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  CONSTRAINT fk_a_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Question votes table (for upvote/downvote functionality)
CREATE TABLE question_votes (
  user_id INT NOT NULL,
  question_id INT NOT NULL,
  value TINYINT NOT NULL COMMENT '1 for upvote, -1 for downvote',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, question_id),
  CONSTRAINT fk_qv_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_qv_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes for FK columns
CREATE INDEX idx_questions_user ON questions(user_id);
CREATE INDEX idx_questions_module ON questions(module_id);
CREATE INDEX idx_answers_q ON answers(question_id);
CREATE INDEX idx_answers_user ON answers(user_id);
CREATE INDEX idx_qt_q ON question_tags(question_id);
CREATE INDEX idx_qt_t ON question_tags(tag_id);
CREATE INDEX idx_qv_question ON question_votes(question_id);

-- Seed data
INSERT INTO users (username,email,password,role) VALUES
('admin','thuong.admin@gmail.com', '$2y$10$rUcSjkKMu5CMDFN2AOjcGeLl1IUygXPoMNFTyOSfIzyStw0TQIV4K', 'admin');
-- Password 123; replace after import if needed.

INSERT INTO modules (module_code,module_name) VALUES
('COMP101','Programming Basics'),
('COMP1841','Web Development'),
('MATH100','Discrete Math');

-- Optional sample data (uncomment if needed)
-- INSERT INTO questions (title, content, user_id, module_id, status) VALUES ('Sample question','How to set up PDO?', 1, 2, 'approved');
-- INSERT INTO answers (question_id, user_id, content) VALUES (1, 1, 'Use PDO with DSN and prepared statements.');

ALTER TABLE contacts 
ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER message;

-- Create database
CREATE DATABASE IF NOT EXISTS `comp1841_coursework` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `comp1841_coursework`;

-- Create users table (if not exists in your system)
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create contacts table
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `is_replied` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `replied_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


