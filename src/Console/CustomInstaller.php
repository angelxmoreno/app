<?php
namespace App\Console;

use Cake\Utility\Inflector;
use Cake\Utility\Security;
use Composer\Script\Event;
use Exception;

/**
 * Class CustomInstaller
 *
 * @package App\Console
 */
class CustomInstaller extends Installer
{

    /**
     * @inheritdoc
     */
    public static function postInstall(Event $event)
    {
        $io = $event->getIO();

        $rootDir = dirname(dirname(__DIR__));

        static::createEnvFile($rootDir, $io);
        static::createDockerComposeFile($rootDir, $io);
        static::createWritableDirectories($rootDir, $io);
        static::askPermissionChange($rootDir, $io);
        $salt = hash('sha256', Security::randomBytes(64));
        static::setValueInEnv($rootDir, $io, '__SALT__', $salt, 'Security.Salt');

        $appName = Inflector::camelize(basename($rootDir));
        static::setValueInEnv($rootDir, $io, '__APP_NAME__', $appName, 'Application name');
        static::askContainerShortName($rootDir, $io, $appName);

        if (class_exists('\Cake\Codeception\Console\Installer')) {
            \Cake\Codeception\Console\Installer::customizeCodeceptionBinary($event);
        }
    }

    /**
     * Create the .env file if it does not exist.
     *
     * @param string $rootDir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function createEnvFile($rootDir, $io)
    {
        $from = $rootDir . '/config/.env.default';
        $to = $rootDir . '/.env';
        if (!file_exists($to)) {
            copy($from, $to);
            $io->write('Created `.env` file');
        }
    }

    /**
     * Create the docker-compose file if it does not exist.
     *
     * @param string $rootDir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function createDockerComposeFile($rootDir, $io)
    {
        $from = $rootDir . '/config/docker-compose.default';
        $to = $rootDir . '/docker-compose.yml';
        if (!file_exists($to)) {
            copy($from, $to);
            $io->write('Created `.env` file');
        }
    }

    /**
     * Ask if the permissions should be changed
     *
     * @param string $rootDir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function askPermissionChange($rootDir, $io)
    {
        if ($io->isInteractive()) {
            $validator = function ($arg) {
                if (in_array($arg, ['Y', 'y', 'N', 'n'])) {
                    return $arg;
                }
                throw new Exception('This is not a valid answer. Please choose Y or n.');
            };
            $setFolderPermissions = $io->askAndValidate(
                '<info>Set Folder Permissions ? (Default to Y)</info> [<comment>Y,n</comment>]? ',
                $validator,
                10,
                'Y'
            );

            if (in_array($setFolderPermissions, ['Y', 'y'])) {
                static::setFolderPermissions($rootDir, $io);
            }
        } else {
            static::setFolderPermissions($rootDir, $io);
        }
    }

    /**
     * Ask for container short name
     *
     * @param string $rootDir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function askContainerShortName($rootDir, $io, $appName)
    {
        $containerShortName = Inflector::dasherize($appName);

        $rules = [
            'Must start with a letter.',
            'Must be in lower-case'
        ];

        if ($io->isInteractive()) {
            $validator = function ($arg) use ($rules) {
                if (preg_match('/^[a-z][a-z0-9\-\._]*$/', $arg, $matches)) {
                    return $arg;
                } else {
                    throw new Exception('Invalid short-name. ' . implode(" ", $rules));
                }
            };
            $ask = "<info>Short-name for containers ? (Default to {$containerShortName})</info>";
            $ask .= "<comment>\n";
            $ask .= implode("\n", $rules);
            $ask .= "</comment>\n ";
            $containerShortName = $io->askAndValidate($ask, $validator, 10, $containerShortName);
        }

        static::setValueInDockerYaml($rootDir, $io, '__CONTAINER_SHORT_NAME__', $containerShortName, 'Container short-name');
        static::setValueInEnv($rootDir, $io, '__CONTAINER_SHORT_NAME__', $containerShortName, 'Container short-name');
    }

    /**
     * Replace a given string with a value for the .env file
     *
     * @param string $rootDir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @param string $find String to find in .env file
     * @param string $replace String replacing in .env file
     * @param string $placeholder_name String name of the replacement
     * @return void
     */
    public static function setValueInEnv($rootDir, $io, $find, $replace, $placeholder_name)
    {
        $env_file = $rootDir . '/.env';
        static::replaceInFile($env_file, $io, $find, $replace, $placeholder_name);
    }

    /**
     * Replace a given string with a value for the docker-compose file
     *
     * @param string $rootDir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @param string $find String to find in docker-compose file
     * @param string $replace String replacing in docker-compose file
     * @param string $placeholder_name String name of the replacement
     * @return void
     */
    public static function setValueInDockerYaml($rootDir, $io, $find, $replace, $placeholder_name)
    {
        $env_file = $rootDir . '/docker-compose.yml';
        static::replaceInFile($env_file, $io, $find, $replace, $placeholder_name);
    }

    /**
     * Replace a given string with a value for the .env file
     *
     * @param string $file_path The path of the file to make replacements
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @param string $find String to find in file
     * @param string $replace String to replacing with in file
     * @param string $placeholder_name String name of the replacement
     * @return void
     */
    public static function replaceInFile($file_path, $io, $find, $replace, $placeholder_name)
    {
        $content = file_get_contents($file_path);

        $content = str_replace($find, $replace, $content, $count);

        if ($count == 0) {
            $io->write("No {$placeholder_name} placeholder to replace.");

            return;
        }

        $result = file_put_contents($file_path, $content);
        if ($result) {
            $io->write("Updated {$placeholder_name} value in {$file_path}");

            return;
        }
        $io->write("Unable to update {$placeholder_name} value.");
    }
}
