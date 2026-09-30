<?php

declare(strict_types=1);

use Gallib\PSSG\Config;
use Gallib\PSSG\Project;

beforeEach(function () {
    $this->directory = sys_get_temp_dir()
        . '/pssg-project-'
        . uniqid();

    mkdir($this->directory);

    $this->configFile = $this->directory . '/pssg.yml';

    file_put_contents(
        $this->configFile,
        <<<'YAML'
site:
    name: Test site

directories:
    pages: content
    templates: views
    assets: static
    output: dist

server:
    host: 127.0.0.1
    port: 9000
YAML
    );

    mkdir($this->directory . '/content');
    mkdir($this->directory . '/views');
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

test('provides project information', function () {
    $config = new Config($this->configFile);

    $project = new Project(
        root: $this->directory,
        config: $config,
    );

    expect($project->getRoot())->toBe($this->directory)
        ->and($project->getSiteName())->toBe('Test site')
        ->and($project->getPagesDirectory())
        ->toBe($this->directory . '/content')
        ->and($project->getTemplatesDirectory())
        ->toBe($this->directory . '/views')
        ->and($project->getAssetsDirectory())
        ->toBe($this->directory . '/static')
        ->and($project->getSiteDirectory())
        ->toBe($this->directory . '/dist')
        ->and($project->getServerHost())->toBe('127.0.0.1')
        ->and($project->getServerPort())->toBe(9000);
});

test('validates a valid project structure', function () {
    $this->expectNotToPerformAssertions();

    $config = new Config($this->configFile);

    $project = new Project(
        root: $this->directory,
        config: $config,
    );

    $project->validate();
});

test('throws an exception when pages directory does not exist', function () {
    rmdir($this->directory . '/content');

    $config = new Config($this->configFile);

    $project = new Project(
        root: $this->directory,
        config: $config,
    );

    expect(
        fn () => $project->validate()
    )->toThrow(
        RuntimeException::class,
        sprintf(
            'Pages directory "%s" not found.',
            $this->directory . '/content'
        )
    );
});

test('throws an exception when templates directory does not exist', function () {
    rmdir($this->directory . '/views');

    $config = new Config($this->configFile);

    $project = new Project(
        root: $this->directory,
        config: $config,
    );

    expect(
        fn () => $project->validate()
    )->toThrow(
        RuntimeException::class,
        sprintf(
            'Templates directory "%s" not found.',
            $this->directory . '/views'
        )
    );
});

test('creates a project from current directory', function () {
    $currentDirectory = getcwd();

    if ($currentDirectory === false) {
        throw new RuntimeException(
            'Unable to determine the current directory.'
        );
    }

    chdir($this->directory);

    try {
        $project = Project::fromCurrentDirectory();

        expect($project->getRoot())->toBe($this->directory)
            ->and($project->getSiteName())->toBe('Test site')
            ->and($project->getPagesDirectory())
            ->toBe($this->directory . '/content')
            ->and($project->getTemplatesDirectory())
            ->toBe($this->directory . '/views');
    } finally {
        chdir($currentDirectory);
    }
});