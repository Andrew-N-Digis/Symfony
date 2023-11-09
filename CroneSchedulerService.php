<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Schedules;
use App\Repository\SchedulesRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Output\NullOutput;

class CroneSchedulerService
{
    private KernelInterface $kernel;
    private SchedulesRepository $schedulesRepository;
    private EntityManagerInterface $manager;
    
    private const STATUS_DONE = 0;
    private const STATUS_PROCESS = 1;

    public function __construct(
        KernelInterface $kernel,
        SchedulesRepository $schedulesRepository,
        EntityManagerInterface $manager
    ) {
        $this->kernel = $kernel;
        $this->schedulesRepository = $schedulesRepository;
        $this->manager = $manager;
        date_default_timezone_set('Europe/London');
    }

    public function CryptoConversion(): void
    {
        if ((new DateTime())->format('H:i') === '00:00') {
            $schedule = $this->makeSchedule('app:check-balance');
            if ($schedule->getStatus() !== self::STATUS_PROCESS) {
                $schedule->setStatus(self::STATUS_PROCESS);
                $schedule->setStartedAt(new DateTime());
                $this->manager->persist($schedule);
                $this->manager->flush();

                try {
                    $this->runApp('app:check-balance');

                    $schedule->setSuccess(true);
                } catch (\Exception $e) {
                    $schedule->setSuccess(false);
                }

                $schedule->setStatus(self::STATUS_DONE);
                $schedule->setEndedAt(new DateTime());
                $this->manager->persist($schedule);
                $this->manager->flush();
            }
        }
    }

    public function checkWallets(): void
    {
        $schedule = $this->makeSchedule('app:check-wallets');

        $now = new DateTime();
        $last = $schedule->getEndedAt() ?? (new DateTime())->sub(new \DateInterval('PT2H'));
        $timeDiff = \round(\abs($now->getTimestamp() - $last->getTimestamp()) / 60, 2);

        if ($timeDiff >= 60) {
            if ($schedule->getStatus() !== self::STATUS_PROCESS) {
                $schedule->setStatus(self::STATUS_PROCESS);
                $schedule->setStartedAt(new DateTime());
                $this->manager->persist($schedule);
                $this->manager->flush();

                try {
                    $this->runApp('app:check-wallets');
                    $schedule->setSuccess(true);
                } catch (\Exception $e) {
                    $schedule->setSuccess(false);
                }

                $schedule->setStatus(self::STATUS_DONE);
                $schedule->setEndedAt(new DateTime());
                $this->manager->persist($schedule);
                $this->manager->flush();
            }
        }
    }

    public function dailyReported(): void
    {
        if ((new DateTime())->format('H:i') === '22:00') {
            $schedule = $this->makeSchedule('app:write-report');
            if ($schedule->getStatus() !== self::STATUS_PROCESS) {
                $schedule->setStatus(self::STATUS_PROCESS);
                $schedule->setStartedAt(new DateTime());
                $this->manager->persist($schedule);
                $this->manager->flush();

                try {
                    $this->runApp('app:write-report');
                    $schedule->setSuccess(true);
                } catch (\Exception $e) {
                    $schedule->setSuccess(false);
                }

                $schedule->setStatus(self::STATUS_DONE);
                $schedule->setEndedAt(new DateTime());
                $this->manager->persist($schedule);
                $this->manager->flush();
            }
        }
    }

    public function saveExchangeRate(): void
    {
        if ((new DateTime())->format('H:i') === '22:00') {
            $schedule = $this->makeSchedule('app:save-exchange-rate');
            if ($schedule->getStatus() !== self::STATUS_PROCESS) {
                $schedule->setStatus(self::STATUS_PROCESS);
                $schedule->setStartedAt(new DateTime());
                $this->manager->persist($schedule);
                $this->manager->flush();

                try {
                    $this->runApp('app:save-exchange-rate');
                    $schedule->setSuccess(true);
                } catch (\Exception $e) {
                    $schedule->setSuccess(false);
                }

                $schedule->setStatus(self::STATUS_DONE);
                $schedule->setEndedAt(new DateTime());
                $this->manager->persist($schedule);
                $this->manager->flush();
            }
        }
    }

    public function makeSchedule(string $name): Schedules
    {
        $schedule = $this->schedulesRepository->findOneBy(['name' => $name]);

        return $schedule ?? (new Schedules())::init($name);
    }

    public function runApp(string $commandName): void
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => $commandName
        ]);

        $output = new NullOutput();
        $application->run($input, $output);
    }
}