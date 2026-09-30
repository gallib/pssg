<?php

declare(strict_types=1);

namespace Gallib\PSSG;

class FileWatcher
{
    /**
     * Previous state of the watched files.
     *
     * @var array<string, array{mtime: int, size: int}>
     */
    private array $previousState;

    /**
     * Creates a watcher for the specified directories.
     *
     * @param array<int, string> $directories Paths to the directories to watch.
     *
     * @return void
     */
    public function __construct(
        private readonly array $directories,
    ) {
        $this->previousState = $this->getFilesState();
    }

    /**
     * Checks whether the watched files have changed.
     *
     * @return boolean True if a change is detected, false otherwise.
     */
    public function hasChanged(): bool
    {
        $currentState = $this->getFilesState();

        if ($currentState === $this->previousState) {
            return false;
        }

        $this->previousState = $currentState;

        return true;
    }

    /**
     * Returns the current state of all watched files.
     *
     * @return array<string, array{mtime: int, size: int}>
     */
    private function getFilesState(): array
    {
        clearstatcache();

        /** @var array<string, array{mtime: int, size: int}> $state */
        $state = [];

        foreach ($this->directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $directory,
                    \FilesystemIterator::SKIP_DOTS
                )
            );

            foreach ($files as $file) {
                /** @var \SplFileInfo $file */

                if (!$file->isFile()) {
                    continue;
                }

                $state[$file->getPathname()] = [
                    'mtime' => $file->getMTime(),
                    'size' => $file->getSize(),
                ];
            }
        }

        return $state;
    }
}
