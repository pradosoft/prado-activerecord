-- ActiveRecord test schema for Microsoft SQL Server.
-- Loaded into the prado_unitest database by .github/scripts/setup-sqlsrv.sh (sqlcmd).
-- Requires SQL Server 2016+ for DROP TABLE IF EXISTS.
-- Same tables and rows as scripts/mysql/database.sql.

DROP TABLE IF EXISTS departments;
CREATE TABLE departments (
  department_id BIGINT NOT NULL IDENTITY(1,1),
  name VARCHAR(255) NOT NULL,
  description VARCHAR(MAX) NULL,
  active BIT NOT NULL DEFAULT 0,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (department_id)
);
GO

SET IDENTITY_INSERT departments ON;
INSERT INTO departments (department_id, name, description, active, sort_order) VALUES
(1, 'Facilities', NULL, 0, 1),
(2, 'Marketing', NULL, 1, 2),
(3, 'Sales', NULL, 0, 3),
(4, 'Human resources', NULL, 1, 4),
(5, '+GX Service', NULL, 1, 5),
(6, 'Services', NULL, 1, 6),
(7, 'Logistics', NULL, 1, 7),
(8, 'Research and Development', NULL, 1, 8);
SET IDENTITY_INSERT departments OFF;
GO

DROP TABLE IF EXISTS department_sections;
CREATE TABLE department_sections (
  department_id BIGINT NOT NULL,
  section_id BIGINT NOT NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (department_id, section_id)
);
GO

INSERT INTO department_sections (department_id, section_id, sort_order) VALUES
(1, 1, 1),
(1, 2, 2),
(2, 3, 3),
(2, 4, 4),
(2, 5, 5);
GO

DROP TABLE IF EXISTS simple_users;
CREATE TABLE simple_users (
  username VARCHAR(255) NOT NULL,
  PRIMARY KEY (username)
);
GO

INSERT INTO simple_users (username) VALUES
('tom'), ('matt'), ('greg'), ('mickey'), ('brad'), ('zach'), ('ian'), ('lola'), ('david'), ('sam');
GO

DROP TABLE IF EXISTS blogs;
CREATE TABLE blogs (
  blog_id BIGINT NOT NULL IDENTITY(1,1),
  blog_name VARCHAR(255) NOT NULL,
  blog_author VARCHAR(255) NOT NULL,
  PRIMARY KEY (blog_id)
);
GO

SET IDENTITY_INSERT blogs ON;
INSERT INTO blogs (blog_id, blog_name, blog_author) VALUES
(1, 'personal blog', 'personal blog');
SET IDENTITY_INSERT blogs OFF;
GO

DROP TABLE IF EXISTS users;
CREATE TABLE users (
  username VARCHAR(40) NOT NULL,
  password VARCHAR(40) NULL,
  email VARCHAR(40) NULL,
  first_name VARCHAR(40) NULL,
  last_name VARCHAR(40) NULL,
  job_title VARCHAR(40) NULL,
  work_phone VARCHAR(40) NULL,
  work_fax VARCHAR(40) NULL,
  active BIT DEFAULT 1,
  department_id BIGINT NULL,
  salutation VARCHAR(40) NULL,
  hint_question VARCHAR(40) NULL,
  hint_answer VARCHAR(40) NULL,
  PRIMARY KEY (username)
);
GO

INSERT INTO users VALUES ('admin', '123456', 'Joe.Dalton@somewhere.com', 'Joe', 'Dalton', 'Ceo', '+1 234 567890', '+1 234 567890', 1, 1, 'Dear', 'fav color', 'red');
GO
