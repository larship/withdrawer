<?php

declare(strict_types=1);

namespace App\Repository;

use Throwable;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Doctrine\RegistryInterface;

class UserRepository extends ServiceEntityRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param RegistryInterface $registry
     * @param Connection        $connection
     */
    public function __construct(RegistryInterface $registry, Connection $connection)
    {
        parent::__construct($registry, User::class);

        $this->connection = $connection;
    }

    /**
     * Получить сущность User по имени пользователя
     *
     * @param string $username Имя пользователя
     *
     * @return User|null
     */
    public function findByUsername(string $username): ?User
    {
        // Требование использовать Raw SQL при работе с ORM
        $rawSql = '
            SELECT * FROM user WHERE username = :username
        ';

        try {
            $statement = $this->connection->prepare($rawSql);
            $statement->execute(['username' => $username]);
            $userData = $statement->fetch();
        } catch (Throwable $throwable) {
            return null;
        }

        if (empty($userData)) {
            return null;
        }

        $user = new User();
        $user->setId($userData['id']);
        $user->setUsername($userData['username']);
        $user->setPassword($userData['password']);
        return $user;
    }
}
