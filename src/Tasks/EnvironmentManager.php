<?php

namespace Tlr\Frb\Tasks;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Tlr\Frb\Tasks\AbstractTask;

class EnvironmentManager extends AbstractTask
{
    /**
     * The "section" name for the task.
     *
     * @var string
     */
    protected $section = 'Environment';

    /**
     * Create the env directory
     *
     * @param  string $root
     * @return Tlr\Frb\Tasks\EnvironmentManager
     */
    public function createEnvDirectory() : EnvironmentManager
    {
        $files = new Filesystem;

        $this->progress('Creating .deploy directory');

        if ($files->exists(runPath('.deploy'))) {
            throw new \Exception('The .deploy directory already exists');
        }

        $files->mkdir(runPath('.deploy'));

        $this->copySampleFileToEnv('.gitignore');

        return $this;
    }

    /**
     * Copy the sample file to the env directory
     *
     * @param  string      $file
     * @param  string|null $name
     * @return Tlr\Frb\Tasks\EnvironmentManager
     */
    public function copySampleFileToEnv(string $file, string $name = null) : EnvironmentManager
    {
        if ($name) {
            $this->formatProgress('Creating [%s]', $name);
        } else {
            $this->formatProgress('Copying [%s]', $file);
        }

        $files = new Filesystem;
        $filename = $name ?? $file;

        if ($files->exists(frbEnvPath($filename))) {
            throw new \Exception(sprintf('The file [%s] already exists', frbEnvPath($filename)));
        }

        $files->copy(frbCliPath("fragments/$file"), frbEnvPath($filename));

        return $this;
    }

    /**
     * Clean the logs in the given folder
     *
     * @param  string $folder
     * @return Tlr\Frb\Tasks\EnvironmentManager
     */
    public function cleanLogs(string $folder) : EnvironmentManager
    {
        $finder = (new Finder)->files()->in($folder)->name('*.log');
        $files = new Filesystem;

        $this->progress(sprintf('Cleaning up %s log files.', $finder->count()), null, false);

        foreach ($finder as $file) {
            $files->remove($file->getRealPath());
        }

        $this->progress('Done.', null, false);

        return $this;
    }
}
