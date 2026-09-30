<?php

declare(strict_types=1);

use Gallib\PSSG\Command\InitCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function (): void {
    $this->directory = sys_get_temp_dir()
        . '/pssg-init-' . uniqid();

    mkdir($this->directory);

    $this->originalDirectory = getcwd();

    chdir($this->directory);
});

afterEach(function (): void {
    if ($this->originalDirectory !== false) {
        chdir($this->originalDirectory);
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $this->directory,
            FilesystemIterator::SKIP_DOTS
        ),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }

    rmdir($this->directory);
});

it('initializes a PSSG project', function (): void {
    $command = new InitCommand();
    $tester = new CommandTester($command);

    $status = $tester->execute([]);

    expect($status)->toBe(Command::SUCCESS)
        ->and('pages')->toBeDirectory()
        ->and('templates')->toBeDirectory()
        ->and('assets')->toBeDirectory()
        ->and('pssg.yml')->toBeFile()
        ->and('pages/index.md')->toBeFile()
        ->and('templates/default.php')->toBeFile();
});

it('does not initialize when a project element already exists', function (): void {
    mkdir('templates');

    $command = new InitCommand();
    $tester = new CommandTester($command);

    expect(fn () => $tester->execute([]))
        ->toThrow(
            RuntimeException::class,
            '"templates" already exists.'
        );

    expect('templates')->toBeDirectory()
        ->and('pages')->not->toBeDirectory()
        ->and('assets')->not->toBeDirectory()
        ->and('pssg.yml')->not->toBeFile();
});

it('creates the default project files', function (): void {
    $command = new InitCommand();
    $tester = new CommandTester($command);

    $tester->execute([]);

    expect(file_get_contents('pssg.yml'))
        ->toContain('name: My PSSG Site')
        ->and(file_get_contents('pages/index.md'))
        ->toContain('# Hello PSSG')
        ->and(file_get_contents('templates/default.php'))
        ->toContain('<?= $content ?>');
});
