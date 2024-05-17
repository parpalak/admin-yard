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
    created_at   TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts (id)
);

INSERT INTO comments (post_id, name, email, comment_text, created_at)
VALUES
    (1, 'John Smith', 'j@j.com', 'This is the first comment for post 1.', NOW()),
    (1, 'Jane Doe', 'j@d.com', 'This is the second comment for post 1.', NOW()),
    (1, 'Michael Johnson', 'm@j.com', 'This is the third comment for post 1.', NOW()),
    (2, 'Sarah Lee', 's@l.com', 'This is the first comment for post 2.', NOW()),
    (2, 'David Kim', 'd@k.com', 'This is the second comment for post 2.', NOW()),
    (2, 'Emily Chen', 'e@c.com', 'This is the third comment for post 2.', NOW()),
    (3, 'Tom Johnson', 't@j.com', 'This is the first comment for post 3.', NOW()),
    (3, 'Emily Chen', 'e@c.com', 'This is the second comment for post 3.', NOW()),
    (3, 'David Kim', 'd@k.com', 'This is the third comment for post 3.', NOW()),
    (4, 'Sarah Lee', 's@l.com', 'This is the first comment for post 4.', NOW()),
    (4, 'Emily Chen', 'e@c.com', 'This is the second comment for post 4.', NOW()),
    (4, 'Tom Johnson', 't@j.com', 'This is the third comment for post 4.', NOW()),
    (5, 'Jane Doe', 'j@d.com', 'This is the first comment for post 5.', NOW()),
    (5, 'Michael Johnson', 'm@j.com', 'This is the second comment for post 5.', NOW()),
    (5, 'Sarah Lee', 's@l.com', 'This is the third comment for post 5.', NOW()),
    (6, 'David Kim', 'd@k.com', 'This is the first comment for post 6.', NOW()),
    (6, 'Emily Chen', 'e@c.com', 'This is the second comment for post 6.', NOW()),
    (6, 'Tom Johnson', 't@j.com', 'This is the third comment for post 6.', NOW()),
    (7, 'Jane Doe', 'j@d.com', 'This is the first comment for post 7.', NOW()),
    (7, 'Michael Johnson', 'm@j.com', 'This is the second comment for post 7.', NOW()),
    (7, 'Sarah Lee', 's@l.com', 'This is the third comment for post 7.', NOW()),
    (8, 'David Kim', 'd@k.com', 'This is the first comment for post 8.', NOW()),
    (8, 'Emily Chen', 'e@c.com', 'This is the second comment for post 8.', NOW()),
    (8, 'Tom Johnson', 't@j.com', 'This is the third comment for post 8.', NOW()),
    (9, 'Jane Doe', 'j@d.com', 'This is the first comment for post 9.', NOW()),
    (9, 'Michael Johnson', 'm@j.com', 'This is the second comment for post 9.', NOW()),
    (9, 'Sarah Lee', 's@l.com', 'This is the third comment for post 9.', NOW()),
    (10, 'David Kim', 'd@k.com', 'This is the first comment for post 10.', NOW()),
    (10, 'Emily Chen', 'e@c.com', 'This is the second comment for post 10.', NOW()),
    (10, 'Tom Johnson', 't@j.com', 'This is the third comment for post 10.', NOW())
    ;

DROP TABLE IF EXISTS tags;
CREATE TABLE IF NOT EXISTS tags
(
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    description TEXT
);


INSERT INTO tags (name, description)
SELECT
    CONCAT('Tag ', n) AS name,
    CONCAT('Description for tag ', n) AS description
FROM
    numbers
LIMIT 50;

DROP TABLE IF EXISTS my_table;
CREATE TABLE IF NOT EXISTS my_table
(
    column1 INT          NOT NULL,
    column2 VARCHAR(255) NOT NULL,
    column3 DATE         NOT NULL,
    PRIMARY KEY (column1, column2, column3)
);

INSERT INTO my_table (column1, column2, column3)
SELECT
    n AS column1,
    CONCAT('text ', n) AS column2,
    NOW() - INTERVAL FLOOR(RAND() * 365) DAY AS column3
FROM
    numbers
LIMIT 50;


DROP TEMPORARY TABLE IF EXISTS numbers;
