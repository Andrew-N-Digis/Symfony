<?php
declare(strict_types=1);

namespace App\Service\Finance;

use App\Entity\BalanceHistory;
use App\Entity\UserWallet;
use Doctrine\ORM\EntityManagerInterface;

class BalanceService
{
    private EntityManagerInterface $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function increaseBalance(UserWallet &$userWallet, float $amount, int $type): void
    {
        try {
            $oldBalance = $userWallet->getBalance();
            $userWallet->increaseBalance($amount);
            $newBalance = $userWallet->getBalance();

            $balanceHistory = BalanceHistory::init($amount, $oldBalance, $newBalance, $type);

            $userWallet->addBalanceHistory($balanceHistory);

            $this->manager->persist($userWallet);
            $this->manager->flush();
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    public function decreaseBalance(UserWallet &$userWallet, float $amount, int $type): void
    {
        try {
            $oldBalance = $userWallet->getBalance();
            $userWallet->decreaseBalance($amount);
            $newBalance = $userWallet->getBalance();

            if ($newBalance < 0) {
                throw new Exception('Not enough amount on balance');
            }

            $balanceHistory = BalanceHistory::init($amount * (-1), $oldBalance, $newBalance, $type);

            $userWallet->addBalanceHistory($balanceHistory);

            $this->manager->persist($userWallet);
            $this->manager->flush();
        } catch (\Exception $exception) {
            throw new \Exception();
        }
    }
}