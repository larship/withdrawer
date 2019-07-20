<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserBalanceRepository")
 */
class UserBalance
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    private $userId;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $balance;

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): void
    {
        $this->balance = $balance;
    }
}
