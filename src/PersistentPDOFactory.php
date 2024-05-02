<?php

declare(strict_types=1);

namespace MazeDEV\DatabaseConnector;

use PDO;
use Psr\Container\ContainerInterface;

class PersistentPDOFactory
{
    public function __invoke(ContainerInterface $container): PersistentPDO
    {
        $config = $container->get('config');
        $pdoConfig = $config['persistentpdo'] ?? null;

        if (null === $pdoConfig) 
        {
            throw new \Exception(
                "'persistentpdo' Config is missing, please check our docs: " . $config['docs'] . '#user-content-pdo'
            );
        }

        if (! isset($pdoConfig['dsn'])) 
        {
            throw new \Exception(
                "no 'dsn' value set in persistentpdo Config, please check our docs:" . $config['docs'] . '#user-content-pdo'
            );
        }

        if (! isset($pdoConfig['username'])) 
        {
            throw new \Exception(
                "no 'username' value set in persistentpdo Config, please check our docs:" . $config['docs'] . '#user-content-pdo'
            );
        }

        if (! isset($pdoConfig['password'])) 
        {
            throw new \Exception(
                "no 'password' value set in persistentpdo Config, please check our docs:" . $config['docs'] . '#user-content-pdo'
            );
        }


        $connection = new PDO(
            (string) $pdoConfig['dsn'],
            (string) ($pdoConfig['username'] ?? null),
            (string) ($pdoConfig['password'] ?? null)
        );

        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

        return new PersistentPDO($connection);
    }
}
