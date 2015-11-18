<?php

$CFG = new stdClass();

$CFG->pdo       = 'mysql:host=127.0.0.1;port=8889;dbname=gmane'; // MAMP
$CFG->dbuser    = 'fred';
$CFG->dbpass    = 'zap';

$CFG->expire = 7*24*60*60;  // A week
$CFG->maxtext = 200000;

// Only add these at the end and keep the same order unless
// you completely empty out the messages table.
$ALLOWED = array(
'gmane.comp.cms.sakai.devel'
);

