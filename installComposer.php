<?php
putenv("HOME=" . getcwd());

if (!file_exists('vendor/autoload.php') && !file_exists('logs/composerDone.txt')) {
    file_put_contents('logs/composerDone.txt', '');
    installComposer();
}

function installComposer()
{
    $composerCmd = getComposerCommand();

    $cmd = "$composerCmd install 2>&1";
    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);

    file_put_contents('logs/installLog.txt', implode(PHP_EOL, $output), FILE_APPEND);

    try {
        require_once 'vendor/autoload.php';
    } catch (\Throwable $th) {
        $returnCode = 1;
    }
    return ('Composer installed via: ' . $composerCmd);
}

function getComposerCommand()
{
    // Check if Composer is already installed globally
    exec('composer --version 2>&1', $output, $returnCode);

    if ($returnCode === 0) {
        return 'composer'; // Use global Composer if available
    }

    // If composer.phar already exists, use it
    if (file_exists('composer.phar')) {
        return 'php composer.phar';
    }

    // Composer is missing, install it locally
    installComposerLocally();
    return 'php composer.phar';
}

function installComposerLocally()
{
    // echo "Composer is not installed. Installing locally...\n";

    exec("php -r \"copy('https://getcomposer.org/installer', 'test/composer-setup.php');\"", $output, $returnCode);
    if (!empty($output))
        print_r($output);
    if ($returnCode !== 0) {
        die("Failed to download Composer setup script.\n");
    }

    // Run the setup script to install Composer locally
    exec("php test/composer-setup.php", $output, $returnCode);
    unlink('test/composer-setup.php'); // Remove setup script after installation

    if ($returnCode !== 0) {
        die("Failed to install Composer.\n");
    }

    // echo "Composer installed locally as composer.phar.\n";
}
