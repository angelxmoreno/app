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
        static::createWritableDirectories($rootDir, $io);
        static::askPermissionChange($rootDir, $io);
        $salt = hash('sha256', Security::randomBytes(64));
        static::setValueInEnv($rootDir, $io, '__SALT__', $salt, 'Security.Salt');

        $appName = Inflector::camelize(basename($rootDir));
        static::setValueInEnv($rootDir, $io, '__APP_NAME__', $appName, 'Application name');

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
        $from = $rootDir . '/.env.default';
        $to = $rootDir . '/.env';
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
     * Set the security.salt value in .env
     *
     * @param string $rootDir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @param string $newKey key to set in the file
     * @return void
     */
    public static function setSecuritySaltInEnv($rootDir, $io, $newKey)
    {
        $env_file = $rootDir . '/.env';
        $content = file_get_contents($env_file);

        $content = str_replace('__SALT__', $newKey, $content, $count);

        if ($count == 0) {
            $io->write('No Security.salt placeholder to replace.');

            return;
        }

        $result = file_put_contents($env_file, $content);
        if ($result) {
            $io->write('Updated Security.salt value in .env');

            return;
        }
        $io->write('Unable to update Security.salt value.');
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
        $content = file_get_contents($env_file);

        $content = str_replace($find, $replace, $content, $count);

        if ($count == 0) {
            $io->write("No {$placeholder_name} placeholder to replace.");

            return;
        }

        $result = file_put_contents($env_file, $content);
        if ($result) {
            $io->write("Updated {$placeholder_name} value in .env");

            return;
        }
        $io->write("Unable to update {$placeholder_name} value.");
    }
}