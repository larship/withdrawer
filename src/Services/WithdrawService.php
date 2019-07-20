<?php

declare(strict_types=1);

namespace App\Services;

use Throwable;
use App\Repository\UserBalanceRepository;
use App\Exceptions\NotEnoughMoneyException;
use Doctrine\DBAL\Connection;

class WithdrawService
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var UserBalanceRepository
     */
    private $userBalanceRepository;

    /**
     * Статус списания - успешно выполнено
     */
    private const STATUS_SUCCESS = 'success';

    /**
     * Статус списания - не выполнено
     */
    private const STATUS_FAIL = 'fail';

    /**
     * @param Connection            $connection
     * @param UserBalanceRepository $userBalanceRepository
     */
    public function __construct(Connection $connection, UserBalanceRepository $userBalanceRepository)
    {
        $this->connection            = $connection;
        $this->userBalanceRepository = $userBalanceRepository;
    }

    /**
     * Метод, выполняющий проверку, можно ли списать у указанного польлзователя указанную сумму
     *
     * @param int   $userId Идентификатор пользователя, у которого необходимо выполнить списание
     * @param float $sum    Сумма списания
     *
     * @return bool
     */
    public function canWithdraw(int $userId, float $sum): bool
    {
        $balance = $this->userBalanceRepository->findByUserId($userId);

        if (empty($balance)) {
            return false;
        }

        return $balance->getBalance() >= $sum;
    }

    /**
     * Метод, выполняющий списание средств у пользователя
     *
     * @param int   $userId Идентификатор пользователя, у которого будет выполнено списание
     * @param float $sum    Сумма списания
     *
     * @throws NotEnoughMoneyException
     * @throws Throwable
     */
    public function withdraw(int $userId, float $sum): void
    {
        if (!$this->canWithdraw($userId, $sum)) {
            $this->logUserWithdraw($userId, $sum, static::STATUS_FAIL);
            throw new NotEnoughMoneyException();
        }

        $this->connection->query('START TRANSACTION')->execute();

        try {
            // Тут просто списываем, без зачисления куда-то в другое место
            // Дополнительно в историю списаний добавим запись
            $this->connection->prepare('
                UPDATE
                  user_balance
                SET
                  balance = balance - :sum
                WHERE
                  user_id = :userId 
            ')->execute(['userId' => $userId, 'sum' => $sum]);
            $this->logUserWithdraw($userId, $sum, static::STATUS_SUCCESS);
            $this->connection->query('COMMIT')->execute();
        } catch (Throwable $throwable) {
            $this->connection->query('ROLLBACK')->execute();
            $this->logUserWithdraw($userId, $sum, static::STATUS_FAIL);

            throw $throwable;
        }
    }

    /**
     * Метод, выполняющий логирование попытки списания средств
     *
     * @param int    $userId Идентификатор пользователя, у которого выполняется списание
     * @param float  $sum    Сумма списания
     * @param string $status Статус списания
     */
    public function logUserWithdraw(int $userId, float $sum, string $status): void
    {
        $this->connection->prepare('
            INSERT INTO
              user_balance_history
            VALUES
              (NULL, :userId, "withdraw", :sum, :status, NOW())
        ')->execute(['userId' => $userId, 'sum' => $sum, 'status' => $status]);
    }
}
