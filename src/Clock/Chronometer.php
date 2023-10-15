<?php

declare(strict_types=1);

namespace NGSOFT\Clock;

use NGSOFT\DataStructure\Sort;
use NGSOFT\Traits\ReversibleIteratorTrait;
use Psr\Clock\ClockInterface;

final class Chronometer implements ClockInterface, \ArrayAccess, \Countable, \IteratorAggregate
{
    use ReversibleIteratorTrait;

    private State $state     = State::Idle;
    private float $startTime = 0.0;
    private float $runTime   = 0.0;

    private float $totalTime = 0.0;

    /**
     * @var array<string,self>
     */
    private array $lapTimes  = [];

    public function __construct(
        private readonly bool $hrTime = true
    ) {
    }

    public function __clone(): void
    {
        $this->lapTimes = [];
    }

    public function read(): float
    {
        if (State::Started === $this->state)
        {
            return self::getMicroTime($this->hrTime) - $this->startTime + $this->runTime;
        }

        return $this->runTime;
    }

    public function readTimeString(): string
    {
        $date = self::convertMicroTime($this->read());

        return $date->format('H:i:s.u');
    }

    public function readDateInterval(): \DateInterval
    {
        return self::convertMicroTime($this->read())
            ->diff(self::convertMicroTime(0.0))
        ;
    }

    public function readTotalTime(): float
    {
        if (State::Stopped === $this->state)
        {
            return $this->totalTime;
        }

        return $this->read();
    }

    public function readTotalTimeString(): string
    {
        $date = self::convertMicroTime($this->readTotalTime());
        return $date->format('H:i:s.u');
    }

    public function readTotalDateInterval(): \DateInterval
    {
        return self::convertMicroTime($this->readTotalTime())
            ->diff(self::convertMicroTime(0.0))
        ;
    }

    public function start(): bool
    {
        if (State::Stopped === $this->state)
        {
            return false;
        }

        if (State::Started !== $this->state)
        {
            if (State::Paused === $this->state)
            {
                return $this->resume();
            }

            $this->startTime = self::getMicroTime($this->hrTime);
            $this->state     = State::Started;
            return true;
        }

        return false;
    }

    public function resume(): bool
    {
        if (State::Idle === $this->state)
        {
            return $this->start();
        }

        if (State::Paused === $this->state)
        {
            $this->startTime = self::getMicroTime($this->hrTime);
            $this->state     = State::Started;
            return true;
        }

        return false;
    }

    public function pause(): float
    {
        if (State::Started === $this->state)
        {
            $this->runTime += self::getMicroTime($this->hrTime) - $this->startTime;
            $this->totalTime = $this->runTime;
            $this->state     = State::Paused;
        }

        return $this->read();
    }

    public function addLapTime(string $label): self
    {
        if (State::Started !== $this->state)
        {
            return $this;
        }

        $lap                    = clone $this;
        $lap->stop();

        /** @var self $prev */
        foreach ($this->entries(Sort::DESC) as $prev)
        {
            $lap->runTime -= $prev->runTime;
        }

        $this->lapTimes[$label] = $lap;

        return $lap;
    }

    public function now(): \DateTimeImmutable
    {
        return self::convertMicroTime(self::getMicroTime($this->hrTime));
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->lapTimes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->lapTimes[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // noop
    }

    public function offsetUnset(mixed $offset): void
    {
        // noop
    }

    public static function convertMicroTime(float $seconds): \DateTimeImmutable
    {
        $seconds = number_format($seconds, 6, '.', '');

        if ( ! str_contains($seconds, '.'))
        {
            return \DateTimeImmutable::createFromFormat('U', $seconds);
        }

        return \DateTimeImmutable::createFromFormat('U.u', $seconds);
    }

    public function entries(Sort $sort = Sort::ASC): iterable
    {
        $laps = $this->lapTimes;

        if (Sort::DESC === $sort)
        {
            $laps = array_reverse($laps);
        }

        yield from $laps;
    }

    private function stop(): void
    {
        $this->pause();
        $this->state = State::Stopped;
    }

    private static function getMicroTime(bool $hrTime = true): float
    {
        /** @var bool $highResolution */
        static $highResolution;
        $highResolution ??= function_exists('hrtime');

        if ($hrTime && $highResolution)
        {
            return hrtime(true) / 1e+9;
        }

        if (2 === sscanf(microtime(), '%f %f', $micro, $sec))
        {
            return $sec + $micro;
        }

        return microtime(true);
    }
}
