<?php

declare(strict_types=1);

namespace Gallib\PSSG;

use Symfony\Component\Process\Process;

class Server
{
    /**
     * Process running the PHP development server.
     *
     * @var Process
     */
    private Process $process;

    /**
     * Creates a development server for the specified directory.
     *
     * @param string  $directory Path to the directory to serve.
     * @param string  $host      Server host.
     * @param integer $port      Server port.
     *
     * @return void
     */
    public function __construct(
        private readonly string $directory,
        private readonly string $host = '0.0.0.0',
        private readonly int $port = 8000,
    ) {
        $this->process = new Process([
            PHP_BINARY,
            '-S',
            "$this->host:$this->port",
            '-t',
            $this->directory,
        ]);
    }

    /**
     * Starts the development server.
     *
     * @return void
     */
    public function start(): void
    {
        $this->process->start();
    }

    /**
     * Checks whether the development server is running.
     *
     * @return boolean True if the server is running, false otherwise.
     */
    public function isRunning(): bool
    {
        return $this->process->isRunning();
    }
}
