<?php

declare(strict_types=1);

use Gallib\PSSG\Config;

beforeEach(function () {
    $this->filename = sys_get_temp_dir()
        . '/pssg-config-'
        . uniqid()
        . '.yml';
});

afterEach(function () {
    if (is_file($this->filename)) {
        unlink($this->filename);
    }
});

test('loads configuration values', function () {
    file_put_contents(
        $this->filename,
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

    $config = new Config($this->filename);

    expect($config->getSiteName())->toBe('Test site')
        ->and($config->getPagesDirectory())->toBe('content')
        ->and($config->getTemplatesDirectory())->toBe('views')
        ->and($config->getAssetsDirectory())->toBe('static')
        ->and($config->getOutputDirectory())->toBe('dist')
        ->and($config->getServerHost())->toBe('127.0.0.1')
        ->and($config->getServerPort())->toBe(9000);
});

test('uses default configuration values', function () {
    file_put_contents(
        $this->filename,
        '{}'
    );

    $config = new Config($this->filename);

    expect($config->getSiteName())->toBe('PSSG')
        ->and($config->getPagesDirectory())->toBe('pages')
        ->and($config->getTemplatesDirectory())->toBe('templates')
        ->and($config->getAssetsDirectory())->toBe('assets')
        ->and($config->getOutputDirectory())->toBe('site')
        ->and($config->getServerHost())->toBe('0.0.0.0')
        ->and($config->getServerPort())->toBe(8000);
});

test('throws an exception when configuration file does not exist', function () {
    expect(
        fn () => new Config($this->filename)
    )->toThrow(
        RuntimeException::class,
        sprintf(
            'Configuration file "%s" not found.',
            $this->filename
        )
    );
});

test('throws an exception when configuration structure is invalid', function () {
    file_put_contents(
        $this->filename,
        <<<'YAML'
site: invalid
YAML
    );

    new Config($this->filename);
})->throws(
    RuntimeException::class,
    'The configuration file has an invalid structure.'
);

test('throws an exception when a string property has an invalid type', function () {
    file_put_contents(
        $this->filename,
        <<<'YAML'
directories:
    pages: 42
YAML
    );

    $config = new Config($this->filename);

    $config->getPagesDirectory();
})->throws(
    RuntimeException::class,
    'The "directories.pages" property must be a string.'
);

test('throws an exception when server port has an invalid type', function () {
    file_put_contents(
        $this->filename,
        <<<'YAML'
server:
    port: invalid
YAML
    );

    $config = new Config($this->filename);

    $config->getServerPort();
})->throws(
    RuntimeException::class,
    'The "server.port" property must be an integer.'
);