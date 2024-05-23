CREATE TEMPORARY TABLE IF NOT EXISTS numbers (n INT);
INSERT INTO numbers (n) VALUES (1),(2),(3),(4),(5),(6),(7),(8),(9),(10),(11),(12),(13),(14),(15),(16),(17),(18),(19),(20),(21),(22),(23),(24),(25),(26),(27),(28),(29),(30),(31),(32),(33),(34),(35),(36),(37),(38),(39),(40),(41),(42),(43),(44),(45),(46),(47),(48),(49),(50);

DROP TABLE IF EXISTS comments;


DROP TABLE IF EXISTS posts;
CREATE TABLE IF NOT EXISTS posts
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(255)     NOT NULL,
    text       TEXT             NOT NULL,
    is_active  BOOLEAN          NOT NULL DEFAULT true,
    created_at TIMESTAMP                 DEFAULT CURRENT_TIMESTAMP,
    updated_at INT(11) UNSIGNED NOT NULL
);

INSERT INTO posts (title, text, is_active, created_at, updated_at)
SELECT
    CONCAT('Post ', n) AS title,
    CONCAT('Text for post ', n) AS text,
    IF(MOD(n, 2) = 0, true, false) AS is_active,
    NOW() - INTERVAL FLOOR(RAND() * 365) DAY AS created_at,
    UNIX_TIMESTAMP() - RAND() * 365 * 86400 AS updated_at
FROM
    numbers
LIMIT 50;

CREATE TABLE IF NOT EXISTS comments
(
    id           INT AUTO_INCREMENT PRIMARY KEY,
    post_id      INT  NOT NULL,
    name         TEXT NOT NULL,
    email        VARCHAR(20) DEFAULT NULL,
    comment_text TEXT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status_code  VARCHAR(20) NOT NULL,
    FOREIGN KEY (post_id) REFERENCES posts (id)
);

INSERT INTO comments (post_id, name, email, comment_text, created_at, status_code)
VALUES
    (1, 'John Smith', 'j@j.com', 'This is the first comment for post 1.', NOW(), 'approved'),
    (1, 'Jane Doe', 'j@d.com', 'This is the second comment for post 1.', NOW(), 'new'),
    (1, 'Michael Johnson', 'm@j.com', 'This is the third comment for post 1.', NOW(), 'new'),
    (2, 'Sarah Lee', 's@l.com', 'This is the first comment for post 2.', NOW(), 'approved'),
    (2, 'David Kim', 'd@k.com', 'This is the second comment for post 2.', NOW(), 'new'),
    (2, 'Emily Chen', 'e@c.com', 'This is the third comment for post 2.', NOW(), 'new'),
    (3, 'Tom Johnson', 't@j.com', 'This is the first comment for post 3.', NOW(), 'approved'),
    (3, 'Emily Chen', 'e@c.com', 'This is the second comment for post 3.', NOW(), 'new'),
    (3, 'David Kim', 'd@k.com', 'This is the third comment for post 3.', NOW(), 'new'),
    (4, 'Sarah Lee', 's@l.com', 'This is the first comment for post 4.', NOW(), 'approved'),
    (4, 'Emily Chen', 'e@c.com', 'This is the second comment for post 4.', NOW(), 'new'),
    (4, 'Tom Johnson', 't@j.com', 'This is the third comment for post 4.', NOW(), 'new'),
    (5, 'Jane Doe', 'j@d.com', 'This is the first comment for post 5.', NOW(), 'approved'),
    (5, 'Michael Johnson', 'm@j.com', 'This is the second comment for post 5.', NOW(), 'new'),
    (5, 'Sarah Lee', 's@l.com', 'This is the third comment for post 5.', NOW(), 'new'),
    (6, 'David Kim', 'd@k.com', 'This is the first comment for post 6.', NOW(), 'approved'),
    (6, 'Emily Chen', 'e@c.com', 'This is the second comment for post 6.', NOW(), 'new'),
    (6, 'Tom Johnson', 't@j.com', 'This is the third comment for post 6.', NOW(), 'new'),
    (7, 'Jane Doe', 'j@d.com', 'This is the first comment for post 7.', NOW(), 'approved'),
    (7, 'Michael Johnson', 'm@j.com', 'This is the second comment for post 7.', NOW(), 'new'),
    (7, 'Sarah Lee', 's@l.com', 'This is the third comment for post 7.', NOW(), 'new'),
    (8, 'David Kim', 'd@k.com', 'This is the first comment for post 8.', NOW(), 'approved'),
    (8, 'Emily Chen', 'e@c.com', 'This is the second comment for post 8.', NOW(), 'new'),
    (8, 'Tom Johnson', 't@j.com', 'This is the third comment for post 8.', NOW(), 'new'),
    (9, 'Jane Doe', 'j@d.com', 'This is the first comment for post 9.', NOW(), 'approved'),
    (9, 'Michael Johnson', 'm@j.com', 'This is the second comment for post 9.', NOW(), 'new'),
    (9, 'Sarah Lee', 's@l.com', 'This is the third comment for post 9.', NOW(), 'new'),
    (10, 'David Kim', 'd@k.com', 'This is the first comment for post 10.', NOW(), 'approved'),
    (10, 'Emily Chen', 'e@c.com', 'This is the second comment for post 10.', NOW(), 'new'),
    (10, 'Tom Johnson', 't@j.com', 'This is the third comment for post 10.', NOW(), 'new')
    ;

DROP TABLE IF EXISTS tags;
CREATE TABLE IF NOT EXISTS tags
(
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    description TEXT
);


INSERT INTO tags (name, description)
VALUES ('HTML', 'HyperText Markup Language is the standard markup language for documents designed to be displayed in a web browser.'),
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
       ('docker', 'Docker is a tool for building and running applications in containers.')
       ;

DROP TABLE IF EXISTS posts_tags;
CREATE TABLE IF NOT EXISTS posts_tags
(
    post_id INT NOT NULL,
    tag_id  INT NOT NULL,
    PRIMARY KEY (post_id, tag_id)
);

INSERT INTO posts_tags (post_id, tag_id)
SELECT
    (n*13 + 11)%29 + 1 AS post_id,
    (n*5 + 7)%12 + 1 AS tag_id
FROM
    numbers
LIMIT 50;

DROP TABLE IF EXISTS composite_key_table;
CREATE TABLE IF NOT EXISTS composite_key_table
(
    column1 INT          NOT NULL,
    column2 VARCHAR(255) NOT NULL,
    column3 DATE         NOT NULL,
    PRIMARY KEY (column1, column2, column3)
);

INSERT INTO composite_key_table (column1, column2, column3)
SELECT
    n AS column1,
    CONCAT('text ', n) AS column2,
    NOW() - INTERVAL FLOOR(RAND() * 365) DAY AS column3
FROM
    numbers
LIMIT 50;

DROP TABLE IF EXISTS config;
CREATE TABLE IF NOT EXISTS config
(
    name VARCHAR(255) NOT NULL,
    value VARCHAR(255) NOT NULL,
    PRIMARY KEY (name)
);

INSERT INTO config (name, value)
VALUES
    ('language', 'en'),
    ('theme', 'dark'),
    ('timezone', 'UTC'),
    ('date_format', 'Y-m-d H:i:s'),
    ('time_format', 'H:i:s'),
    ('number_format', '0.00'),
    ('is_active', '1'),
    ('max_posts', '10')
;

DROP TABLE IF EXISTS sequence;
CREATE TABLE IF NOT EXISTS sequence
(
    id INT AUTO_INCREMENT PRIMARY KEY
);

INSERT INTO sequence (id)
VALUES (1),
       (2),
       (3),
       (4),
       (5),
       (6),
       (7),
       (8),
       (9),
       (10)
;


DROP TEMPORARY TABLE IF EXISTS numbers;
