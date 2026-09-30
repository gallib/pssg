<?php

declare(strict_types=1);

use Gallib\PSSG\Builder;

beforeEach(function () {
    $this->directory = sys_get_temp_dir()
        . '/pssg-builder-'
        . uniqid();

    $this->pages = $this->directory . '/pages';
    $this->templates = $this->directory . '/templates';
    $this->assets = $this->directory . '/assets';
    $this->site = $this->directory . '/site';

    mkdir($this->directory);
    mkdir($this->pages);
    mkdir($this->templates);

    file_put_contents(
        $this->templates . '/default.php',
        <<<'PHP'
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= htmlspecialchars($title ?? 'Default title') ?></title>
</head>
<body>
    <?= $content ?>
</body>
</html>
PHP
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

test('builds a markdown page', function () {
    file_put_contents(
        $this->pages . '/index.md',
        '# Hello PSSG'
    );

    $builder = new Builder(
        source: $this->pages,
        destination: $this->site,
        assets: $this->assets,
        templates: $this->templates,
        siteName: 'Test site',
    );

    $builder->build();

    $filename = $this->site . '/index.html';

    expect($filename)->toBeFile();

    $html = file_get_contents($filename);

    expect($html)
        ->toContain('<h1>Hello PSSG</h1>');
});

test('preserves the directory structure', function () {
    mkdir(
        $this->pages . '/php',
        recursive: true
    );

    file_put_contents(
        $this->pages . '/index.md',
        '# Home'
    );

    file_put_contents(
        $this->pages . '/php/index.md',
        '# PHP'
    );

    file_put_contents(
        $this->pages . '/php/tableaux.md',
        '# Arrays'
    );

    $builder = new Builder(
        source: $this->pages,
        destination: $this->site,
        assets: $this->assets,
        templates: $this->templates,
        siteName: 'Test site',
    );

    $builder->build();

    expect($this->site . '/index.html')->toBeFile()
        ->and($this->site . '/php/index.html')->toBeFile()
        ->and($this->site . '/php/tableaux.html')->toBeFile();
});

test('passes front matter data to the template', function () {
    file_put_contents(
        $this->pages . '/index.md',
        <<<'MARKDOWN'
---
title: My page
author: John Doe
---

# Hello
MARKDOWN
    );

    file_put_contents(
        $this->templates . '/default.php',
        <<<'PHP'
<h1><?= htmlspecialchars($title) ?></h1>
<p><?= htmlspecialchars($author) ?></p>
<?= $content ?>
PHP
    );

    $builder = new Builder(
        source: $this->pages,
        destination: $this->site,
        assets: $this->assets,
        templates: $this->templates,
        siteName: 'Test site',
    );

    $builder->build();

    $html = file_get_contents(
        $this->site . '/index.html'
    );

    expect($html)
        ->toContain('<h1>My page</h1>')
        ->toContain('<p>John Doe</p>')
        ->toContain('<h1>Hello</h1>');
});

test('uses the template specified in front matter', function () {
    file_put_contents(
        $this->templates . '/article.php',
        <<<'PHP'
<article>
    <?= $content ?>
</article>
PHP
    );

    file_put_contents(
        $this->pages . '/index.md',
        <<<'MARKDOWN'
---
template: article.php
---

# Hello
MARKDOWN
    );

    $builder = new Builder(
        source: $this->pages,
        destination: $this->site,
        assets: $this->assets,
        templates: $this->templates,
        siteName: 'Test site',
    );

    $builder->build();

    $html = file_get_contents(
        $this->site . '/index.html'
    );

    expect($html)
        ->toContain('<article>')
        ->toContain('<h1>Hello</h1>')
        ->toContain('</article>');
});

test('uses a template from a subdirectory', function () {
    mkdir(
        $this->templates . '/layouts',
        recursive: true
    );

    file_put_contents(
        $this->templates . '/layouts/article.php',
        <<<'PHP'
<article class="article">
    <?= $content ?>
</article>
PHP
    );

    file_put_contents(
        $this->pages . '/index.md',
        <<<'MARKDOWN'
---
template: layouts/article.php
---

# Hello
MARKDOWN
    );

    $builder = new Builder(
        source: $this->pages,
        destination: $this->site,
        assets: $this->assets,
        templates: $this->templates,
        siteName: 'Test site',
    );

    $builder->build();

    $html = file_get_contents(
        $this->site . '/index.html'
    );

    expect($html)
        ->toContain('<article class="article">')
        ->toContain('<h1>Hello</h1>');
});

test('rejects a template outside the templates directory', function () {
    file_put_contents(
        $this->directory . '/secret.php',
        <<<'PHP'
<p>Secret</p>
PHP
    );

    file_put_contents(
        $this->pages . '/index.md',
        <<<'MARKDOWN'
---
template: ../secret.php
---

# Hello
MARKDOWN
    );

    $builder = new Builder(
        source: $this->pages,
        destination: $this->site,
        assets: $this->assets,
        templates: $this->templates,
        siteName: 'Test site',
    );

    expect(
        fn () => $builder->build()
    )->toThrow(RuntimeException::class);
});

test('copies assets to the destination directory', function () {
    mkdir(
        $this->assets . '/css',
        recursive: true
    );

    mkdir(
        $this->assets . '/images',
        recursive: true
    );

    file_put_contents(
        $this->assets . '/css/style.css',
        'body { margin: 0; }'
    );

    file_put_contents(
        $this->assets . '/images/logo.txt',
        'logo'
    );

    file_put_contents(
        $this->pages . '/index.md',
        '# Home'
    );

    $builder = new Builder(
        source: $this->pages,
        destination: $this->site,
        assets: $this->assets,
        templates: $this->templates,
        siteName: 'Test site',
    );

    $builder->build();

    expect($this->site . '/assets/css/style.css')
        ->toBeFile()
        ->and($this->site . '/assets/images/logo.txt')
        ->toBeFile()
        ->and(
            file_get_contents(
                $this->site . '/assets/css/style.css'
            )
        )
        ->toBe('body { margin: 0; }');
});

test('cleans the destination directory before building', function () {
    mkdir(
        $this->site . '/old',
        recursive: true
    );

    file_put_contents(
        $this->site . '/old/page.html',
        '<p>Old page</p>'
    );

    file_put_contents(
        $this->pages . '/index.md',
        '# Home'
    );

    $builder = new Builder(
        source: $this->pages,
        destination: $this->site,
        assets: $this->assets,
        templates: $this->templates,
        siteName: 'Test site',
    );

    $builder->build();

    expect($this->site . '/old/page.html')
        ->not->toBeFile()
        ->and($this->site . '/old')
        ->not->toBeDirectory()
        ->and($this->site . '/index.html')
        ->toBeFile();
});

test('passes the site name to the template', function () {
    file_put_contents(
        $this->templates . '/default.php',
        <<<'PHP'
<header><?= htmlspecialchars($siteName) ?></header>
<?= $content ?>
PHP
    );

    file_put_contents(
        $this->pages . '/index.md',
        '# Home'
    );

    $builder = new Builder(
        source: $this->pages,
        destination: $this->site,
        assets: $this->assets,
        templates: $this->templates,
        siteName: 'My website',
    );

    $builder->build();

    $html = file_get_contents(
        $this->site . '/index.html'
    );

    expect($html)
        ->toContain('<header>My website</header>');
});

test('builds the site when assets directory does not exist', function () {
    file_put_contents(
        $this->pages . '/index.md',
        '# Home'
    );

    $builder = new Builder(
        source: $this->pages,
        destination: $this->site,
        assets: $this->assets,
        templates: $this->templates,
        siteName: 'Test site',
    );

    $builder->build();

    expect($this->site . '/index.html')
        ->toBeFile()
        ->and($this->site . '/assets')
        ->not->toBeDirectory();
});

test('throws an exception when template does not exist', function () {
    file_put_contents(
        $this->pages . '/index.md',
        <<<'MARKDOWN'
---
template: missing.php
---

# Hello
MARKDOWN
    );

    $builder = new Builder(
        source: $this->pages,
        destination: $this->site,
        assets: $this->assets,
        templates: $this->templates,
        siteName: 'Test site',
    );

    expect(
        fn () => $builder->build()
    )->toThrow(
        RuntimeException::class,
        'Template "missing.php" not found.'
    );
});

test('throws an exception when template property is not a string', function () {
    file_put_contents(
        $this->pages . '/index.md',
        <<<'MARKDOWN'
---
template: 42
---

# Hello
MARKDOWN
    );

    $builder = new Builder(
        source: $this->pages,
        destination: $this->site,
        assets: $this->assets,
        templates: $this->templates,
        siteName: 'Test site',
    );

    expect(
        fn () => $builder->build()
    )->toThrow(
        RuntimeException::class,
        'The "template" property must be a string.'
    );
});

test('ignores files that are not markdown', function () {
    file_put_contents(
        $this->pages . '/index.md',
        '# Home'
    );

    file_put_contents(
        $this->pages . '/notes.txt',
        'Some notes'
    );

    $builder = new Builder(
        source: $this->pages,
        destination: $this->site,
        assets: $this->assets,
        templates: $this->templates,
        siteName: 'Test site',
    );

    $builder->build();

    expect($this->site . '/index.html')
        ->toBeFile()
        ->and($this->site . '/notes.html')
        ->not->toBeFile()
        ->and($this->site . '/notes.txt')
        ->not->toBeFile();
});
