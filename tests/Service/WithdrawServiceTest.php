<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\UserBalance;
use App\Exceptions\NotEnoughMoneyException;
use App\Repository\UserBalanceRepository;
use App\Services\WithdrawService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WithdrawServiceTest extends KernelTestCase
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
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->connection            = $kernel->getContainer()
            ->get('doctrine.dbal.default_connection');
        $this->userBalanceRepository = $kernel->getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository(UserBalance::class);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->connection->close();
        $this->connection            = null;
        $this->userBalanceRepository = null;
    }

    /**
     * Тест метода canWithdraw
     *
     * @covers WithdrawService::canWithdraw
     */
    public function testCanWithdraw(): void
    {
        $withdrawService = new WithdrawService($this->connection, $this->userBalanceRepository);
        $this->assertTrue($withdrawService->canWithdraw(1, 1));
        $this->assertTrue($withdrawService->canWithdraw(1, 100));
        $this->assertTrue($withdrawService->canWithdraw(1, 99.99));
        $this->assertFalse($withdrawService->canWithdraw(1, 101));
        $this->assertFalse($withdrawService->canWithdraw(1, 9999999));
        $this->assertFalse($withdrawService->canWithdraw(1, 100.01));
        $this->assertFalse($withdrawService->canWithdraw(1, 100.01));
        $this->assertFalse($withdrawService->canWithdraw(1, 0));
        $this->assertFalse($withdrawService->canWithdraw(1, -1));
        $this->assertFalse($withdrawService->canWithdraw(1, -100));
    }

    /**
     * Тест метода withdraw
     *
     * @covers WithdrawService::withdraw
     */
    public function testWithdraw(): void
    {
        $withdrawService = new WithdrawService($this->connection, $this->userBalanceRepository);
        $withdrawService->withdraw(1, 1);
        $balance = $this->userBalanceRepository->findByUserId(1);
        $this->assertTrue($this->isFloatsEqual(99, $balance->getBalance()));

        $withdrawService->withdraw(1, 10);
        $balance = $this->userBalanceRepository->findByUserId(1);
        $this->assertTrue($this->isFloatsEqual(89, $balance->getBalance()));

        $withdrawService->withdraw(1, 89);
        $balance = $this->userBalanceRepository->findByUserId(1);
        $this->assertTrue($this->isFloatsEqual(0, $balance->getBalance()));

        $this->assertWithdrawNotEnoughMoneyException($withdrawService, 1, 1);
        $this->assertWithdrawNotEnoughMoneyException($withdrawService, 1, 0);
        $this->assertWithdrawNotEnoughMoneyException($withdrawService, 1, 0.01);
        $this->assertWithdrawNotEnoughMoneyException($withdrawService, 1, -1);
        $this->assertWithdrawNotEnoughMoneyException($withdrawService, 1, -999);
    }

    /**
     * Проверяет, что будет вызвано исключение NotEnoughMoneyException
     *
     * @param WithdrawService $withdrawService
     * @param int             $userId
     * @param float           $sum
     */
    private function assertWithdrawNotEnoughMoneyException(WithdrawService $withdrawService, int $userId, float $sum): void
    {
        $exceptionClassName = '';
        try {
            $withdrawService->withdraw($userId, $sum);
        } catch (NotEnoughMoneyException $exception) {
            $exceptionClassName = NotEnoughMoneyException::class;
        }
        $this->assertEquals(NotEnoughMoneyException::class, $exceptionClassName);
    }

    /**
     * Сравнивает два числа с плавающей точкой на равенство
     *
     * @param float $left
     * @param float $right
     *
     * @return bool
     */
    private function isFloatsEqual(float $left, float $right): bool
    {
        return abs($left - $right) < PHP_FLOAT_EPSILON;
    }
}
