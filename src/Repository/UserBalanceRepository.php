<?php

declare(strict_types=1);

namespace App\Repository;

use Throwable;
use App\Entity\UserBalance;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Doctrine\RegistryInterface;

class UserBalanceRepository extends ServiceEntityRepository
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
     * Получить баланс пользователя
     *
     * @param int $userId Идентификатор пользолвателя
     *
     * @return UserBalance|null
     */
    public function findByUserId(int $userId): ?UserBalance
    {
        // Требование использовать Raw SQL при работе с ORM
        $rawSql = '
            SELECT * FROM user_balance WHERE user_id = :userId
        ';

        try {
            $statement = $this->connection->prepare($rawSql);
            $statement->execute(['userId' => $userId]);
            $userBalanceData = $statement->fetch();
        } catch (Throwable $throwable) {
            return null;
        }

        if (empty($userBalanceData)) {
            return null;
        }

        $userBalance = new UserBalance();
        $userBalance->setUserId((int) $userBalanceData['user_id']);
        $userBalance->setBalance((float) $userBalanceData['balance']);
        return $userBalance;
    }
}
