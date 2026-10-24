-- ActiveRecord test schema for PostgreSQL.
-- Loaded into the prado_unitest database as prado_unitest by .github/scripts/setup-pgsql.sh.
-- That script creates the database with the C locale: the ordering tests expect a binary
-- sort, in which '+GX Service' comes before every letter.
-- Same tables and rows as scripts/mysql/database.sql.

DROP TABLE IF EXISTS departments CASCADE;
CREATE TABLE departments (
  department_id BIGSERIAL NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  active BOOLEAN NOT NULL DEFAULT FALSE,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (department_id)
);

INSERT INTO departments (department_id, name, description, active, sort_order) VALUES
(1, 'Facilities', NULL, FALSE, 1),
(2, 'Marketing', NULL, TRUE, 2),
(3, 'Sales', NULL, FALSE, 3),
(4, 'Human resources', NULL, TRUE, 4),
(5, '+GX Service', NULL, TRUE, 5),
(6, 'Services', NULL, TRUE, 6),
(7, 'Logistics', NULL, TRUE, 7),
(8, 'Research and Development', NULL, TRUE, 8);
SELECT setval('departments_department_id_seq', 8);

DROP TABLE IF EXISTS department_sections CASCADE;
CREATE TABLE department_sections (
  department_id BIGINT NOT NULL,
  section_id BIGINT NOT NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (department_id, section_id)
);

INSERT INTO department_sections (department_id, section_id, sort_order) VALUES
(1, 1, 1),
(1, 2, 2),
(2, 3, 3),
(2, 4, 4),
(2, 5, 5);

DROP TABLE IF EXISTS simple_users CASCADE;
CREATE TABLE simple_users (
  username VARCHAR(255) NOT NULL,
  PRIMARY KEY (username)
);

INSERT INTO simple_users (username) VALUES
('tom'), ('matt'), ('greg'), ('mickey'), ('brad'), ('zach'), ('ian'), ('lola'), ('david'), ('sam');

DROP TABLE IF EXISTS blogs CASCADE;
CREATE TABLE blogs (
  blog_id BIGSERIAL NOT NULL,
  blog_name VARCHAR(255) NOT NULL,
  blog_author VARCHAR(255) NOT NULL,
  PRIMARY KEY (blog_id)
);

INSERT INTO blogs (blog_id, blog_name, blog_author) VALUES
(1, 'personal blog', 'personal blog');
SELECT setval('blogs_blog_id_seq', 1);

DROP TABLE IF EXISTS users CASCADE;
CREATE TABLE users (
  username VARCHAR(40) NOT NULL,
  password VARCHAR(40) DEFAULT NULL,
  email VARCHAR(40) DEFAULT NULL,
  first_name VARCHAR(40) DEFAULT NULL,
  last_name VARCHAR(40) DEFAULT NULL,
  job_title VARCHAR(40) DEFAULT NULL,
  work_phone VARCHAR(40) DEFAULT NULL,
  work_fax VARCHAR(40) DEFAULT NULL,
  active BOOLEAN DEFAULT TRUE,
  department_id BIGINT NULL,
  salutation VARCHAR(40) DEFAULT NULL,
  hint_question VARCHAR(40) DEFAULT NULL,
  hint_answer VARCHAR(40) DEFAULT NULL,
  PRIMARY KEY (username)
);

INSERT INTO users VALUES ('admin', '123456', 'Joe.Dalton@somewhere.com', 'Joe', 'Dalton', 'Ceo', '+1 234 567890', '+1 234 567890', TRUE, 1, 'Dear', 'fav color', 'red');
