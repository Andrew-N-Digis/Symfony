<?php
declare(strict_types=1);

namespace App\Entity;

use App\Helper\FormatHelper;
use App\Repository\UserWalletRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserWalletRepository", repositoryClass=UserWalletRepository::class)
 */
class UserWallet
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="wallets", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Currency::class, inversedBy="userWallets", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $currency;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $wallet;

    /**
     * @ORM\Column(type="float")
     */
    private $balance = 0;

    /**
     * @ORM\OneToMany(targetEntity=BalanceHistory::class, mappedBy="user_wallet", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $balanceHistories;

    /**
     * @ORM\OneToMany(targetEntity=Payment::class, mappedBy="wallet", orphanRemoval=true)
     */
    private $payments;

    /**
     * @ORM\OneToMany(targetEntity=FinanceLog::class, mappedBy="from_wallet")
     */
    private $logs;

    /**
     * @ORM\OneToMany(targetEntity=FinanceLog::class, mappedBy="to_wallet")
     */
    private $to_wallet_logs;

    /**
     * @ORM\OneToMany(targetEntity=CryptoWallet::class, mappedBy="user_wallet", orphanRemoval=true, cascade={"persist"})
     */
    private $cryptoWallets;

    public function __construct()
    {
        $this->balanceHistories = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->logs = new ArrayCollection();
        $this->to_wallet_logs = new ArrayCollection();
        $this->cryptoWallets = new ArrayCollection();
    }

    public static function init(Currency $currency, string $walletNumber): UserWallet
    {
        return (new self())
            ->setCurrency($currency)
            ->setWallet($walletNumber);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getWallet(): ?string
    {
        return $this->wallet;
    }

    public function setWallet(string $wallet): self
    {
        $this->wallet = $wallet;

        return $this;
    }

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function increaseBalance(float $amount): self
    {
        $currency = $this->getCurrency();
        $this->balance = FormatHelper::formatByCurrency($currency, $this->balance + $amount);

        return $this;
    }

    public function decreaseBalance(float $amount): self
    {
        if (($this->balance - $amount) < 0) {
            throw new Exception('Mot enough money');
        }

        $currency = $this->getCurrency();
        $this->balance = FormatHelper::formatByCurrency($currency, $this->balance - $amount);

        return $this;
    }

    public function getBalanceHistories(): Collection
    {
        return $this->balanceHistories;
    }

    public function addBalanceHistory(BalanceHistory $balanceHistory): self
    {
        if (!$this->balanceHistories->contains($balanceHistory)) {
            $this->balanceHistories[] = $balanceHistory;
            $balanceHistory->setUserWallet($this);
        }

        return $this;
    }

    public function removeBalanceHistory(BalanceHistory $balanceHistory): self
    {
        if ($this->balanceHistories->contains($balanceHistory)) {
            $this->balanceHistories->removeElement($balanceHistory);
            // set the owning side to null (unless already changed)
            if ($balanceHistory->getUserWallet() === $this) {
                $balanceHistory->setUserWallet(null);
            }
        }

        return $this;
    }

    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(FinanceLog $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs[] = $log;
            $log->setFromWallet($this);
        }

        return $this;
    }

    public function removeLog(FinanceLog $log): self
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getFromWallet() === $this) {
                $log->setFromWallet(null);
            }
        }

        return $this;
    }

    public function getToWalletLogs(): Collection
    {
        return $this->to_wallet_logs;
    }

    public function addToWalletLog(FinanceLog $toWalletLog): self
    {
        if (!$this->to_wallet_logs->contains($toWalletLog)) {
            $this->to_wallet_logs[] = $toWalletLog;
            $toWalletLog->setToWallet($this);
        }

        return $this;
    }

    public function removeToWalletLog(FinanceLog $toWalletLog): self
    {
        if ($this->to_wallet_logs->removeElement($toWalletLog)) {
            // set the owning side to null (unless already changed)
            if ($toWalletLog->getToWallet() === $this) {
                $toWalletLog->setToWallet(null);
            }
        }

        return $this;
    }

    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setWallet($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getWallet() === $this) {
                $payment->setWallet(null);
            }
        }

        return $this;
    }

    public function getCryptoWallets(): Collection
    {
        return $this->cryptoWallets;
    }

    public function addCryptoWallet(CryptoWallet $cryptoWallet): self
    {
        if (!$this->cryptoWallets->contains($cryptoWallet)) {
            $this->cryptoWallets[] = $cryptoWallet;
            $cryptoWallet->setUserWallet($this);
        }

        return $this;
    }

    public function removeCryptoWallet(CryptoWallet $cryptoWallet): self
    {
        if ($this->cryptoWallets->removeElement($cryptoWallet)) {
            // set the owning side to null (unless already changed)
            if ($cryptoWallet->getUserWallet() === $this) {
                $cryptoWallet->setUserWallet(null);
            }
        }

        return $this;
    }
}