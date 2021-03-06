<?php

namespace Tlr\Frb\Tasks;

use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class AbstractTask
{
    /**
     * Whether or not we are debugging
     *
     * @var boolean
     */
    protected static $debugging = true;

    /**
     * The command instance
     *
     * @var Symfony\Component\Console\Command\Command
     */
    protected $command;

    /**
     * The input interface
     *
     * @var InputInterface
     */
    protected $input;

    /**
     * The output interface
     *
     * @var Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * The "section" name for the task.
     *
     * Should be overridden
     *
     * @var string
     */
    protected $section;

    /**
     * The logger instance
     *
     * @var Monolog\Logger
     */
    protected $log;

    public function __construct(Command $command, InputInterface $input, OutputInterface $output, Logger $log)
    {
        $this->command = $command;
        $this->input   = $input;
        $this->output  = $output;
        $this->log     = $log;
    }

    /**
     * Build a new task
     *
     * @param  string $class
     * @return Tlr\Frb\Tasks\AbstractTask
     */
    public function task(string $class) : AbstractTask
    {
        return new $class($this->command, $this->input, $this->output);
    }

    /**
     * Write some progress to stdout
     *
     * @param  string $message
     * @param  array $values
     * @return Tlr\Frb\Tasks\AbstractTask
     */
    public function formatProgress(string $message, ...$values) : AbstractTask
    {
        return $this->progress(sprintf($message, ...$values));
    }

    /**
     * Write some progress to stdout
     *
     * @param  string $message
     * @param  string $overrideSection
     * @return Tlr\Frb\Tasks\AbstractTask
     */
    public function progress(string $message, string $overrideSection = null, bool $shouldLog = true) : AbstractTask
    {
        if ($shouldLog) {
            $this->log->warning($message, $this->logContext());
        }

        $this->output->writeLn(
            $this->command->getHelper('formatter')
                ->formatSection($overrideSection ?? $this->section, $message)
        );

        return $this;
    }

    public function logContext() : array
    {
        $context = [];

        if (static::$debugging) {
            $callerInfo = sprintf(
                '%s@%s %s:%s',
                debug_backtrace()[2]['class'],
                debug_backtrace()[2]['function'],
                debug_backtrace()[1]['file'],
                debug_backtrace()[1]['line']
            );

            $context['caller'] = $callerInfo;
        }

        return $context;
    }

    /**
     * Run a process
     *
     * @param  Symfony\Component\Process\Process $process
     * @return Symfony\Component\Process\Process
     */
    public function runProcess(Process $process)
    {
        $this->log->notice($process->getCommandLine(), $this->logContext());

        $process->run();

        if ($process->isSuccessful()) {
            $this->log->info($process->getOutput());
        } else {
            $this->log->error($process->getOutput());
            throw new ProcessFailedException($process);
        }

        return $process;
    }

    /**
     * Log the full process output
     *
     * @param  Process $output
     * @return Tlr\Frb\Tasks\AbstractTask
     */
    public function log(Process $output) : AbstractTask
    {
        // @todo - send output to log
        // $this->output->writeLn(
        //     $this->command->getHelper('formatter')
        //         ->formatSection(':::LOG:::', $output->getOutput())
        // );

        return $this;
    }
}
