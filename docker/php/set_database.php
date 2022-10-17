<?php
// This file is paste in each MS, added from filesGeneralPathCopy.php

require __DIR__.'/../../vendor/autoload.php';

define('DATABASE_CONNECTION_TIMEOUT', 15);
define('STYLE_NORMAL', 0);
define('STYLE_SUCCESS', 1);
define('STYLE_WARNING', 2);
define('STYLE_ERROR', 3);

$env = "dev";

if( isset($argv[1])) {
    $env = $argv[1];
}

$databaseUrl = getDatabaseUrl();
exitIfMissingDatabaseUrl($databaseUrl);
printMessage('Looks like you have a database !', STYLE_SUCCESS);
waitDatabaseConnection($databaseUrl, DATABASE_CONNECTION_TIMEOUT);
dropDatabase($env);
createDatabase($env);
migrate($env);
loadFixtures($env);
printMessage('Done. Have a nice day!', STYLE_SUCCESS);

// *** Functions ***

/**
 * Get the database URL from the .env file.
 */
function getDatabaseUrl()
{
    (new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__.'/../../.env');

    return parse_url($_ENV['DATABASE_URL']);
}

/**
 * Exit the script without raising an error if missing database URL.
 *
 * Useful for microservices which does not require a database.
 */
function exitIfMissingDatabaseUrl(array &$databaseUrl)
{
    if (empty($databaseUrl['path'])) {
        printMessage('No database URL set in .env file. Skipping database generation.', STYLE_WARNING);
        echo "\e[33m\e[0m\n";
        exit;
    }
}

/**
 * Wait for database connection.
 */
function waitDatabaseConnection(array $databaseUrl, int $maximumWaintingTimeInSeconds)
{
    printMessage('Waiting database connection...'. json_encode($databaseUrl), STYLE_WARNING);

    $start = time();

    while (true) {
        if (fsockopen($databaseUrl['host'].':'.($databaseUrl['port'] ?? 3306))) {
            break;
        }
        $now = time();

        if ($now - $start > $maximumWaintingTimeInSeconds) {
            printMessage("Failed to connect to database. Maximum waiting time {$maximumWaintingTimeInSeconds} seconds expired.", STYLE_ERROR);
            exit(1);
        }
    }

    printMessage('Connected to database.', STYLE_SUCCESS);
}

/**
 * Drop the database if it exists.
 */
function dropDatabase($env)
{
    printMessage('Dropping existing database...', STYLE_WARNING);
    echo shell_exec("php bin/console doctrine:database:drop --env=$env --if-exists --force");
}

/**
 * Create the database.
 */
function createDatabase($env)
{
    printMessage('Creating database...', STYLE_WARNING);
    echo shell_exec("php bin/console doctrine:database:create --env=$env --if-not-exists");
}

/**
 * Run migration files.
 */
function migrate($env)
{
    printMessage('Running migration files...', STYLE_WARNING);
    echo shell_exec("php bin/console doctrine:migrations:migrate --env=$env --no-interaction --allow-no-migration");
}

/**
 * Load fixtures.
 */
function loadFixtures($env)
{
    printMessage('Loading fixtures...', STYLE_WARNING);
    echo shell_exec("php bin/console hautelook:fixtures:load --env=$env --no-interaction");
}

/**
 * Print a styled message.
 */
function printMessage(string $message, int $styleIndex = 0)
{
    $styles = ["\e[0m", "\e[32m", "\e[33m", "\e[31m"];
    echo "{$styles[$styleIndex]}{$message}{$styles[0]}\n";
}
