<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace Thelia\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Thelia\Command\ContainerAwareCommand;
use Thelia\Install\Database;

/**
 * try to install a new instance of Thelia
 *
 * Class Install
 * @package Thelia\Command
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */
class Install extends ContainerAwareCommand
{
    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName("thelia:install")
            ->setDescription("Install thelia using cli tools. For now Thelia only use mysql database")
            ->setHelp("The <info>thelia:install</info> command install Thelia database and create config file needed.")
            ->addOption(
                "db_host",
                null,
                InputOption::VALUE_OPTIONAL,
                "host for your database"
            )
            ->addOption(
                "db_username",
                null,
                InputOption::VALUE_OPTIONAL,
                "username for your database"
            )
            ->addOption(
                "db_password",
                null,
                InputOption::VALUE_OPTIONAL,
                "password for your database"
            )
            ->addOption(
                "db_name",
                null,
                InputOption::VALUE_OPTIONAL,
                "database name"
            )
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            '',
            'Welcome to Thelia install process',
            'You need information about your database configuration (host, username, password, database name, etc)',
            ''
        ));

        $this->checkPermission($output);

        $connectionInfo = array(
            "host" => $input->getOption("db_host"),
            "dbName" => $input->getOption("db_name"),
            "username" => $input->getOption("db_username"),
            "password" => $input->getOption("db_password")
        );

        while (false === $connection = $this->tryConnection($connectionInfo, $output)) {
                $connectionInfo = $this->getConnectionInfo($input, $output);
        }

        $database = new Database($connection);

        $database->createDatabase($connectionInfo["dbName"]);

        $output->writeln(array(
            "",
            "<info>Creating Thelia database, please wait</info>",
            ""
        ));
        $database->insertSql($connectionInfo["dbName"]);

        $output->writeln(array(
            "",
            "<info>Database created without errors</info>",
            "<info>Creating file configuration, please wait</info>",
            ""
        ));

        $this->createConfigFile($connectionInfo);

        $output->writeln(array(
            "",
            "<info>Config file created with success. Your thelia is installed</info>",
            ""
        ));
    }

    /**
     * Test if needed directories have write permission
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function checkPermission(OutputInterface $output)
    {
        $output->writeln(array(
            "Checking some permissions"
        ));

        $confDir = THELIA_ROOT . "local/config";
        $cacheDir = THELIA_ROOT . "cache";
        $logDir = THELIA_ROOT . "log";

        $conf   = is_writable($confDir);
        $cache  = is_writable($cacheDir);
        $log    = is_writable($logDir);

        $output->writeln(array(
           sprintf(
               "<info>config directory(%s)...</info> %s",
               $confDir,
               $conf ? "<info>Ok</info>" : "<error>Fail</error>"
           ),
           sprintf(
               "<info>cache directory(%s)...</info> %s"
               ,$cacheDir,
               $cache ? "<info>Ok</info>" : "<error>Fail</error>"
           ),
           sprintf(
               "<info>log directory(%s)...</info> %s",
               $logDir,
               $log ? "<info>Ok</info>" : "<error>Fail</error>"
           ),
        ));

        if ($conf === false || $cache === false || $log === false) {
           $output->writeln(array(
              "",
              "<error>Please put correct permission and reload install process</error>"
           ));
            exit;
        }

    }

    /**
     * rename database config file and complete it
     *
     * @param array $connectionInfo
     */
    protected function createConfigFile($connectionInfo)
    {
        $fs = new Filesystem();

        $sampleConfigFile = THELIA_ROOT . "/local/config/database.yml.sample";
        $configFile = THELIA_ROOT . "/local/config/database.yml";

        $fs->copy($sampleConfigFile, $configFile, true);

        $configContent = file_get_contents($configFile);

        $configContent = str_replace("%DRIVER%", "mysql", $configContent);
        $configContent = str_replace("%USERNAME%", $connectionInfo["username"], $configContent);
        $configContent = str_replace("%PASSWORD%", $connectionInfo["password"], $configContent);
        $configContent = str_replace(
            "%DSN%",
            sprintf("mysql:host=%s;dbname=%s", $connectionInfo["host"], $connectionInfo["dbName"]),
            $configContent
        );

        file_put_contents($configFile, $configContent);

        // FA - no, as no further install will be possible
        // $fs->remove($sampleConfigFile);

        $fs->remove($this->getContainer()->getParameter("kernel.cache_dir"));

    }

    /**
     * test database access
     *
     * @param $connectionInfo
     * @param  OutputInterface $output
     * @return bool|\PDO
     */
    protected function tryConnection($connectionInfo, OutputInterface $output)
    {

        if (is_null($connectionInfo["dbName"])) {
            return false;
        }

        $dsn = "mysql:host=%s";

        try {
            $connection = new \PDO(
                sprintf($dsn, $connectionInfo["host"]),
                $connectionInfo["username"],
                $connectionInfo["password"]
            );
        } catch (\PDOException $e) {
            $output->writeln(array(
                "<error>Wrong connection information</error>"
            ));

            return false;
        }

        return $connection;
    }

    /**
     * Ask to user all needed information
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return array
     */
    protected function getConnectionInfo(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $connectionInfo = array();

        $connectionInfo["host"] = $dialog->askAndValidate(
            $output,
            $this->decorateInfo("Database host : "),
            function ($answer) {
                $answer = trim($answer);
                if (is_null($answer)) {
                    throw new \RuntimeException("You must specify database host");
                }

                return $answer;
            }
        );

        $connectionInfo["dbName"] = $dialog->askAndValidate(
            $output,
            $this->decorateInfo("Database Name (if database does not exists, Thelia will try to create it) : "),
            function ($answer) {
                $answer = trim($answer);

                if (is_null($answer)) {
                    throw new \RuntimeException("You must specify database name");
                }

                return $answer;
            }
        );

        $connectionInfo["username"] = $dialog->askAndValidate(
            $output,
            $this->decorateInfo("Databse username : "),
            function ($answer) {
                $answer = trim($answer);

                if (is_null($answer)) {
                    throw new \RuntimeException("You must sprcify database username");
                }

                return $answer;
            }
        );

        $connectionInfo["password"] = $dialog->askHiddenResponse(
            $output,
            $this->decorateInfo("Database password : ")
        );

        return $connectionInfo;
    }

    protected function decorateInfo($text)
    {
        return sprintf("<info>%s</info>", $text);
    }

}
