-- ActiveRecord test schema for SQLite.
-- SqliteTestDatabase builds a file from this script in the system temporary directory
-- once per test process; the db-sqlite suite's shared cases read and write that file.
-- Same tables and rows as scripts/mysql/database.sql.

CREATE TABLE departments (
  department_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  active INTEGER NOT NULL DEFAULT 0,
  sort_order INTEGER NOT NULL DEFAULT 0
);

INSERT INTO departments (department_id, name, description, active, sort_order) VALUES
(1, 'Facilities', NULL, 0, 1),
(2, 'Marketing', NULL, 1, 2),
(3, 'Sales', NULL, 0, 3),
(4, 'Human resources', NULL, 1, 4),
(5, '+GX Service', NULL, 1, 5),
(6, 'Services', NULL, 1, 6),
(7, 'Logistics', NULL, 1, 7),
(8, 'Research and Development', NULL, 1, 8);

CREATE TABLE department_sections (
  department_id INTEGER NOT NULL,
  section_id INTEGER NOT NULL,
  sort_order INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY (department_id, section_id)
);

INSERT INTO department_sections (department_id, section_id, sort_order) VALUES
(1, 1, 1),
(1, 2, 2),
(2, 3, 3),
(2, 4, 4),
(2, 5, 5);

CREATE TABLE simple_users (
  username VARCHAR(255) NOT NULL PRIMARY KEY
);

INSERT INTO simple_users (username) VALUES
('tom'), ('matt'), ('greg'), ('mickey'), ('brad'), ('zach'), ('ian'), ('lola'), ('david'), ('sam');

CREATE TABLE blogs (
  blog_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  blog_name VARCHAR(255) NOT NULL,
  blog_author VARCHAR(255) NOT NULL
);

INSERT INTO blogs (blog_id, blog_name, blog_author) VALUES
(1, 'personal blog', 'personal blog');

CREATE TABLE users (
  username VARCHAR(40) NOT NULL PRIMARY KEY,
  password VARCHAR(40) DEFAULT NULL,
  email VARCHAR(40) DEFAULT NULL,
  first_name VARCHAR(40) DEFAULT NULL,
  last_name VARCHAR(40) DEFAULT NULL,
  job_title VARCHAR(40) DEFAULT NULL,
  work_phone VARCHAR(40) DEFAULT NULL,
  work_fax VARCHAR(40) DEFAULT NULL,
  active INTEGER DEFAULT 1,
  department_id INTEGER NULL,
  salutation VARCHAR(40) DEFAULT NULL,
  hint_question VARCHAR(40) DEFAULT NULL,
  hint_answer VARCHAR(40) DEFAULT NULL
);

INSERT INTO users VALUES ('admin', '123456', 'Joe.Dalton@somewhere.com', 'Joe', 'Dalton', 'Ceo', '+1 234 567890', '+1 234 567890', 1, 1, 'Dear', 'fav color', 'red');
