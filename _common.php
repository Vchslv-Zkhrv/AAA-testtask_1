<?php

define('MYSQL_HOST', 'mysql');
define('MYSQL_USER', $_ENV['MYSQL_USER']);
define('MYSQL_PASSWORD', $_ENV['MYSQL_PASSWORD']);
define('MYSQL_DB', $_ENV['MYSQL_DATABASE']);


function connect(): PDO
{
    return new PDO('mysql:host='.MYSQL_HOST.';port=3306;dbname='.MYSQL_DB, MYSQL_USER, MYSQL_PASSWORD);
}


function code(string $value): string
{
    return "<code>$value</code>";
}


function bold(string $value): string
{
    return "<strong>$value</strong>";
}
