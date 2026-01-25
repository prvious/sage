<?php

namespace App\Services;

use Illuminate\Support\Sleep;
use Symfony\Component\Process\Process;

class ProcessStreamer
{
    /**
     * Stream process output line by line.
     */
    public function stream(Process $process, callable $callback): void
    {
        while ($process->isRunning()) {
            $output = $process->getIncrementalOutput();
            $errorOutput = $process->getIncrementalErrorOutput();

            if ($output !== '') {
                foreach (explode("\n", rtrim($output, "\n")) as $line) {
                    if ($line !== '') {
                        $callback($line, 'stdout');
                    }
                }
            }

            if ($errorOutput !== '') {
                foreach (explode("\n", rtrim($errorOutput, "\n")) as $line) {
                    if ($line !== '') {
                        $callback($line, 'stderr');
                    }
                }
            }

            Sleep::for(100)->milliseconds();
        }

        $remainingOutput = $process->getIncrementalOutput();
        $remainingErrorOutput = $process->getIncrementalErrorOutput();

        if ($remainingOutput !== '') {
            foreach (explode("\n", rtrim($remainingOutput, "\n")) as $line) {
                if ($line !== '') {
                    $callback($line, 'stdout');
                }
            }
        }

        if ($remainingErrorOutput !== '') {
            foreach (explode("\n", rtrim($remainingErrorOutput, "\n")) as $line) {
                if ($line !== '') {
                    $callback($line, 'stderr');
                }
            }
        }
    }
}
