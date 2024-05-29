CREATE TEMPORARY TABLE IF NOT EXISTS numbers (n INTEGER);
INSERT INTO numbers (n) VALUES
    (1),(2),(3),(4),(5),(6),(7),(8),(9),(10),
    (11),(12),(13),(14),(15),(16),(17),(18),(19),(20),
    (21),(22),(23),(24),(25),(26),(27),(28),(29),(30),
    (31),(32),(33),(34),(35),(36),(37),(38),(39),(40),
    (41),(42),(43),(44),(45),(46),(47),(48),(49),(50);

DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS posts_tags;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS users;

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    login TEXT NOT NULL,
    name TEXT DEFAULT NULL,
    birthdate DATE DEFAULT NULL,
    UNIQUE (login)
);

INSERT INTO users (login, name) VALUES
    ('admin', NULL),
    ('user', 'John Smith')
;

CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    text TEXT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at INTEGER NOT NULL,
    user_id INTEGER,
    FOREIGN KEY (user_id) REFERENCES users (id)
);

INSERT INTO posts (title, text, is_active, created_at, updated_at, user_id)
SELECT
    'Post ' || n AS title,
    'Text for post ' || n AS text,
    CASE WHEN n % 2 = 0 THEN 1 ELSE 0 END AS is_active,
    DATETIME('now', '-' || (ABS(RANDOM() % 365)) || ' days') AS created_at,
    (STRFTIME('%s', 'now') - ABS(RANDOM() % (365 * 86400))) AS updated_at,
    CASE WHEN n % 3 = 0 THEN 1 WHEN n % 3 = 1 THEN 2 ELSE NULL END AS user_id
FROM numbers
LIMIT 50;

CREATE TABLE IF NOT EXISTS comments
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    email TEXT DEFAULT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status_code TEXT NOT NULL,
    FOREIGN KEY (post_id) REFERENCES posts (id)
);

INSERT INTO comments (post_id, name, email, comment_text, created_at, status_code) VALUES
    (1, 'John Smith', 'j@j.com', 'This is the first comment for post 1.', CURRENT_TIMESTAMP, 'approved'),
    (1, 'Jane Doe', 'j@d.com', 'This is the second comment for post 1.', CURRENT_TIMESTAMP, 'new'),
    (1, 'Michael Johnson', 'm@j.com', 'This is the third comment for post 1.', CURRENT_TIMESTAMP, 'new'),
    (2, 'Sarah Lee', 's@l.com', 'This is the first comment for post 2.', CURRENT_TIMESTAMP, 'approved'),
    (2, 'David Kim', 'd@k.com', 'This is the second comment for post 2.', CURRENT_TIMESTAMP, 'new'),
    (2, 'Emily Chen', 'e@c.com', 'This is the third comment for post 2.', CURRENT_TIMESTAMP, 'new'),
    (3, 'Tom Johnson', 't@j.com', 'This is the first comment for post 3.', CURRENT_TIMESTAMP, 'approved'),
    (3, 'Emily Chen', 'e@c.com', 'This is the second comment for post 3.', CURRENT_TIMESTAMP, 'new'),
    (3, 'David Kim', 'd@k.com', 'This is the third comment for post 3.', CURRENT_TIMESTAMP, 'new'),
    (4, 'Sarah Lee', 's@l.com', 'This is the first comment for post 4.', CURRENT_TIMESTAMP, 'approved'),
    (4, 'Emily Chen', 'e@c.com', 'This is the second comment for post 4.', CURRENT_TIMESTAMP, 'new'),
    (4, 'Tom Johnson', 't@j.com', 'This is the third comment for post 4.', CURRENT_TIMESTAMP, 'new'),
    (5, 'Jane Doe', 'j@d.com', 'This is the first comment for post 5.', CURRENT_TIMESTAMP, 'approved'),
    (5, 'Michael Johnson', 'm@j.com', 'This is the second comment for post 5.', CURRENT_TIMESTAMP, 'new'),
    (5, 'Sarah Lee', 's@l.com', 'This is the third comment for post 5.', CURRENT_TIMESTAMP, 'new'),
    (6, 'David Kim', 'd@k.com', 'This is the first comment for post 6.', CURRENT_TIMESTAMP, 'approved'),
    (6, 'Emily Chen', 'e@c.com', 'This is the second comment for post 6.', CURRENT_TIMESTAMP, 'new'),
    (6, 'Tom Johnson', 't@j.com', 'This is the third comment for post 6.', CURRENT_TIMESTAMP, 'new'),
    (7, 'Jane Doe', 'j@d.com', 'This is the first comment for post 7.', CURRENT_TIMESTAMP, 'approved'),
    (7, 'Michael Johnson', 'm@j.com', 'This is the second comment for post 7.', CURRENT_TIMESTAMP, 'new'),
    (7, 'Sarah Lee', 's@l.com', 'This is the third comment for post 7.', CURRENT_TIMESTAMP, 'new'),
    (8, 'David Kim', 'd@k.com', 'This is the first comment for post 8.', CURRENT_TIMESTAMP, 'approved'),
    (8, 'Emily Chen', 'e@c.com', 'This is the second comment for post 8.', CURRENT_TIMESTAMP, 'new'),
    (8, 'Tom Johnson', 't@j.com', 'This is the third comment for post 8.', CURRENT_TIMESTAMP, 'new'),
    (9, 'Jane Doe', 'j@d.com', 'This is the first comment for post 9.', CURRENT_TIMESTAMP, 'approved'),
    (9, 'Michael Johnson', 'm@j.com', 'This is the second comment for post 9.', CURRENT_TIMESTAMP, 'new'),
    (9, 'Sarah Lee', 's@l.com', 'This is the third comment for post 9.', CURRENT_TIMESTAMP, 'new'),
    (10, 'David Kim', 'd@k.com', 'This is the first comment for post 10.', CURRENT_TIMESTAMP, 'approved'),
    (10, 'Emily Chen', 'e@c.com', 'This is the second comment for post 10.', CURRENT_TIMESTAMP, 'new'),
    (10, 'Tom Johnson', 't@j.com', 'This is the third comment for post 10.', CURRENT_TIMESTAMP, 'new'),
    (10, 'David Kim', 'd@k.com', 'This is the fourth comment for post 10.', CURRENT_TIMESTAMP, 'rejected'),
    (10, 'Emily Chen', 'e@c.com', 'This is the fifth comment for post 10.', CURRENT_TIMESTAMP, 'rejected'),
    (10, 'Tom Johnson', 't@j.com', 'This is the sixth comment for post 10.', CURRENT_TIMESTAMP, 'rejected'),
    (10, 'David Kim', 'd@k.com', 'This is the seventh comment for post 10.', CURRENT_TIMESTAMP, 'rejected'),
    (10, 'Emily Chen', 'e@c.com', 'This is the eighth comment for post 10.', CURRENT_TIMESTAMP, 'rejected'),
    (10, 'Tom Johnson', 't@j.com', 'This is the ninth comment for post 10.', CURRENT_TIMESTAMP, 'rejected'),
    (10, 'David Kim', 'd@k.com', 'This is the tenth comment for post 10.', CURRENT_TIMESTAMP, 'rejected'),
    (10, 'Emily Chen', 'e@c.com', 'This is the eleventh comment for post 10.', CURRENT_TIMESTAMP, 'rejected')
;

DROP TABLE IF EXISTS tags;
CREATE TABLE IF NOT EXISTS tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT
);

INSERT INTO tags (name, description) VALUES
    ('HTML', 'HyperText Markup Language is the standard markup language for documents designed to be displayed in a web browser.'),
    ('CSS', 'Cascading Style Sheets is a style sheet language used for describing the presentation of a document written in HTML.'),
    ('JS', 'JavaScript is a programming language that is one of the core technologies of the World Wide Web, alongside HTML and CSS.'),
    ('PHP', 'PHP is a server-side scripting language that is embedded in HTML.'),
    ('SQL', 'Structured Query Language is used to access and manipulate databases.'),
    ('MySQL', 'MySQL is a relational database management system.'),
    ('PostgreSQL', 'PostgreSQL is a powerful, open source object-relational database system.'),
    ('SQLite', 'SQLite is a cross-platform embedded database program. It is developed by SQLite AB and is released under the MIT license.'),
    ('scrum', 'Agile software development is a method of software development that relies on the principles of the Scrum framework.'),
    ('git', 'Git is a free and open source distributed version control system designed to handle everything from small to very large projects with speed and efficiency.'),
    ('github', 'GitHub is a web-based hosting service for Git repositories.'),
    ('docker', 'Docker is a tool for building and running applications in containers.');

CREATE TABLE IF NOT EXISTS posts_tags (
    post_id INTEGER NOT NULL,
    tag_id INTEGER NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES posts (id),
    FOREIGN KEY (tag_id) REFERENCES tags (id)
);

INSERT INTO posts_tags (post_id, tag_id)
SELECT
    (n * 13 + 11) % 29 + 1 AS post_id,
    (n * 5 + 7) % 12 + 1 AS tag_id
FROM numbers
LIMIT 50;

DROP TABLE IF EXISTS composite_key_table;
CREATE TABLE IF NOT EXISTS composite_key_table
(
    column1 INTEGER NOT NULL,
    column2 TEXT NOT NULL,
    column3 DATE NOT NULL,
    PRIMARY KEY (column1, column2, column3)
);

INSERT INTO composite_key_table (column1, column2, column3)
SELECT
    n AS column1,
    'text ' || n AS column2,
    DATE('now', '-' || (ABS(RANDOM() % 365)) || ' days') AS column3
FROM numbers
LIMIT 50;

DROP TABLE IF EXISTS config;
CREATE TABLE IF NOT EXISTS config (
    name TEXT NOT NULL,
    value TEXT NOT NULL,
    PRIMARY KEY (name)
);

INSERT INTO config (name, value) VALUES
    ('language', 'en'),
    ('theme', 'dark'),
    ('timezone', 'UTC'),
    ('date_format', 'Y-m-d H:i:s'),
    ('time_format', 'H:i:s'),
    ('number_format', '0.00'),
    ('is_active', '1'),
    ('max_posts', '10');

DROP TABLE IF EXISTS sequence;
CREATE TABLE IF NOT EXISTS sequence (
    id INTEGER PRIMARY KEY AUTOINCREMENT
);

INSERT INTO sequence (id) VALUES (1), (2), (3), (4), (5), (6), (7), (8), (9), (10);

DROP TABLE IF EXISTS numbers;
