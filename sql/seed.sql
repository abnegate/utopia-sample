CREATE DATABASE IF NOT EXISTS `test`;

USE test;

CREATE TABLE IF NOT EXISTS `notes`
(
    note_id BIGINT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    title   VARCHAR(128)                      NOT NULL,
    body    TEXT                              NOT NULL
);

INSERT INTO `notes`
VALUES (DEFAULT, 'My First Title', 'The body of my first note'),
       (DEFAULT, 'My Second Title', 'The body of my second note'),
       (DEFAULT, 'My Third Title', 'The body of my third note');