<?php
// Webhook to update a repo for each push on GitHub

date_default_timezone_set('Europe/Paris');

// App variables
$app_root = realpath(__DIR__ . '/../');
$composer = $app_root . '/composer.phar';

// Git variables
$branch = 'master';
$header = 'HTTP_X_HUB_SIGNATURE';
$secret = parse_ini_file($app_root . '/app/config/config.ini')['github_key'];

// Logging function to output content to /github_log.txt
function logHookResult($message, $success = false)
{
    $log_headers = "$message\n";
    if (! $success) {
        foreach ($_SERVER as $header => $value) {
            $log_headers .= "$header: $value \n";
        }
    }
    file_put_contents(__DIR__ . '/../logs/github_log.txt', $log_headers);
}

// CHECK: Download composer in the app root if it is not already there
if (! file_exists($composer)) {
    file_put_contents(
        $composer,
        file_get_contents('https://getcomposer.org/composer.phar')
    );
}

if (isset($_SERVER[$header])) {
    $validation = hash_hmac(
        'sha1',
        file_get_contents('php://input'),
        $secret
    );

    if ($validation == explode('=', $_SERVER[$header])[1]) {
        $log = '';

        // Aknowledge request since the script takes a few minutes
        ob_start();
        echo '{}';
        header($_SERVER['SERVER_PROTOCOL'] . ' 202 Accepted');
        header('Status: 202 Accepted');
        header('Content-Type: application/json');
        header('Content-Length: ' . ob_get_length());
        ob_end_flush();
        ob_flush();
        flush();

        // Pull latest changes
        $log = "Updating Git repository\n";
        exec("git checkout $branch >/dev/null 2>&1");
        exec("git pull origin $branch >/dev/null 2>&1");

        // Install or update dependencies
        if (file_exists($composer)) {
            chdir($app_root);

            // www-data does not have a HOME or COMPOSER_HOME, create one
            $cache_folder = "{$app_root}/.composer_cache";
            if (! is_dir($cache_folder)) {
                $log = "Creating folder {$cache_folder}\n";
                mkdir($cache_folder);
            }

            putenv("COMPOSER_HOME={$app_root}/.composer_cache");

            if (file_exists($app_root . '/vendor')) {
                $log .= "Updating Composer\n";
                exec("php {$composer} update > /dev/null 2>&1");
            } else {
                $log .= "Installing Composer\n";
                exec("php {$composer} install > /dev/null 2>&1");
            }
        }

        /*
            DISABLED to avoid permissions issues
            Execute webstatus.py to update repositories
            $log .= "Running webstatus.py\n";
            exec("{$app_root}/app/scripts/webstatus.py > /dev/null 2>&1");
        */

        $log .= 'Last update: ' . date('d-m-Y H:i:s');
        logHookResult($log, true);
    } else {
        logHookResult('Invalid GitHub secret');
    }
} else {
    logHookResult("{$header} header missing, define a secret key for your project in GitHub");
}
