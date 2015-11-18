<?php
require_once "config.php";

$pdo = new PDO($CFG->pdo, $CFG->dbuser, $CFG->dbpass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/*
CREATE DATABASE gmane DEFAULT CHARACTER SET utf8 ;

GRANT ALL ON gmane.* TO 'fred'@'localhost' IDENTIFIED BY 'zap';
GRANT ALL ON gmane.* TO 'fred'@'127.0.0.1' IDENTIFIED BY 'zap';

CREATE TABLE messages (
    message_id INTEGER NOT NULL,
    list_id INTEGER,
    status INTEGER,
    message TEXT,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT 0,

   PRIMARY KEY(message_id, list_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

*/
