<?php

declare(strict_types=1);

namespace NGSOFT\Clock;

use NGSOFT\DataStructure\Map;

final class TimedTask
{
    private Map $tasks;

    private function __construct()
    {
        $this->tasks = new Map();
    }

    /**
     * Create a timed task.
     */
    public static function createTask(mixed $task, bool $highResolution = true, bool $autoStart = true): Chronometer
    {
        $chrono = new Chronometer($highResolution);

        if ($autoStart)
        {
            $chrono->start();
        }

        return self::getInstance()->add($task, $chrono);
    }

    /**
     * Get chronometer for timed task.
     */
    public static function getTask(mixed $task): Chronometer
    {
        return self::getInstance()->get($task);
    }

    /**
     * Add a lap time to the task and returns that lap.
     */
    public static function addLapTime(mixed $task, string $label): Chronometer
    {
        return self::getTask($task)->addLapTime($label);
    }

    /**
     * Reads a task current time execution.
     */
    public static function readTask(mixed $task): float
    {
        return self::getTask($task)->read();
    }

    /**
     * Read a task total time execution.
     */
    public static function readTotalTask(mixed $task): float
    {
        return self::getTask($task)->readTotalTime();
    }

    /**
     * Pause a task and reads the time elapsed.
     */
    public static function pauseTask(mixed $task): float
    {
        return self::getTask($task)->pause();
    }

    private function add(mixed $task, Chronometer $chrono): Chronometer
    {
        if ($this->tasks->has($task))
        {
            throw new \LogicException('A task can only be started once.');
        }

        $this->tasks->add($task, $chrono);
        return $chrono;
    }

    private function get(mixed $task): Chronometer
    {
        if ( ! $this->tasks->has($task))
        {
            throw new \InvalidArgumentException('Invalid task provided');
        }

        return $this->tasks->get($task);
    }

    private static function getInstance(): self
    {
        static $instance;
        return $instance ??= new self();
    }
}
