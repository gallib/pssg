<?php

declare(strict_types=1);

use Gallib\PSSG\FileWatcher;

beforeEach(function () {
    $this->directory = sys_get_temp_dir()
        . '/pssg-watcher-'
        . uniqid();

    mkdir($this->directory);

    $this->filename = $this->directory . '/index.md';

    file_put_contents(
        $this->filename,
        '# Initial content'
    );
});

afterEach(function () {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $this->directory,
            FilesystemIterator::SKIP_DOTS
        ),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        /** @var SplFileInfo $file */

        if ($file->isDir()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }

    rmdir($this->directory);
});

test('detects no change', function () {
    $watcher = new FileWatcher([
        $this->directory,
    ]);

    expect($watcher->hasChanged())->toBeFalse();
});

test('detects a modified file', function () {
    $watcher = new FileWatcher([
        $this->directory,
    ]);

    file_put_contents(
        $this->filename,
        '# Modified content'
    );

    expect($watcher->hasChanged())->toBeTrue();
});

test('detects a new file', function () {
    $watcher = new FileWatcher([
        $this->directory,
    ]);

    file_put_contents(
        $this->directory . '/about.md',
        '# About'
    );

    expect($watcher->hasChanged())->toBeTrue();
});

test('detects a deleted file', function () {
    $watcher = new FileWatcher([
        $this->directory,
    ]);

    unlink($this->filename);

    expect($watcher->hasChanged())->toBeTrue();
});

test('updates its state after detecting a change', function () {
    $watcher = new FileWatcher([
        $this->directory,
    ]);

    file_put_contents(
        $this->filename,
        '# Modified content'
    );

    expect($watcher->hasChanged())->toBeTrue()
        ->and($watcher->hasChanged())->toBeFalse();
});
