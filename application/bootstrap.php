<?php version_compare(PHP_VERSION, '7.2', '>=') || die('codesaur need PHP 7.2 or newer.');

if (!function_exists('codesaur_error_log')) {
    function codesaur_error_log($errno, $errstr, $errfile, $errline)
    {
        switch ($errno) {
            case E_USER_ERROR:
                error_log("Error: $errstr \n Fatal error on line $errline in file $errfile \n");
                break;
            case E_USER_WARNING:
                error_log("Warning: $errstr \n in $errfile on line $errline \n");
                break;
            case E_USER_NOTICE:
                error_log("Notice: $errstr \n in $errfile on line $errline \n");
                break;
            default:
                if ($errno != 2048) {
                    error_log("#$errno: $errstr \n in $errfile on line $errline \n");
                }
                break;
        }

        return true;
    }
}

if (!function_exists('codesaur_bootstrap')) {
    function codesaur_bootstrap()
    {
        error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);

        $this_dir = dirname(__FILE__);
        $autoload = "$this_dir/../vendor/autoload.php";
        if (!file_exists($autoload)) {
            die("codesaur exit: <strong>$autoload is missing!</strong>");
        }
        $composer = require($autoload);

        ini_set('log_errors', 'On');
        ini_set('error_log', "$this_dir/../logs/code.log");

        try {
            $domain_parts = explode('.', $_SERVER['SERVER_NAME']);
            $env_name = count($domain_parts) == 3 ? '.env_' . $domain_parts[0] : null;
            $dotenv = Dotenv\Dotenv::createImmutable("$this_dir/..", $env_name);
            $dotenv->load();
            foreach ($_ENV as &$env) {
                if ($env == 'true') {
                    $env = true;
                } elseif ($env == 'false') {
                    $env = false;
                }
            }
        } catch (Exception $ex) {
            die("codesaur exit: <strong>{$ex->getMessage()}</strong>");
        }

        define('CODESAUR_DEVELOPMENT', isset($_ENV['CODESAUR_APP_ENV']) ? $_ENV['CODESAUR_APP_ENV'] != 'production' : false);
        ini_set('display_errors', CODESAUR_DEVELOPMENT ? 'On' : 'Off');
        set_error_handler('codesaur_error_log');

        if (!empty($_ENV['CODESAUR_TIME_ZONE'])) {
            date_default_timezone_set($_ENV['CODESAUR_TIME_ZONE']);
        }

        return $composer;
    }
}

return codesaur_bootstrap();
