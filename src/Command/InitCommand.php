<?php

declare(strict_types=1);

namespace Gallib\PSSG\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'init',
    description: 'Initializes a new PSSG project.',
)]
class InitCommand extends Command
{
    /**
     * Initializes a new PSSG project.
     *
     * @param InputInterface  $input  Command input.
     * @param OutputInterface $output Command output.
     *
     * @return integer
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $directory = getcwd();

        if ($directory === false) {
            throw new \RuntimeException(
                'Unable to determine the current directory.'
            );
        }

        $directories = [
            'pages',
            'templates',
            'assets',
        ];

        $files = [
            'pssg.yml' => <<<'YAML'
    site:
        name: My PSSG Site

    directories:
        pages: pages
        templates: templates
        assets: assets
        output: site

    server:
        host: 0.0.0.0
        port: 8000
    YAML,
            'pages/index.md' => <<<'MARKDOWN'
    # Hello PSSG

    Your site is ready!
    MARKDOWN,
            'templates/default.php' => <<<'PHP'
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($siteName) ?></title>
    </head>
    <body>
        <main>
            <?= $content ?>
        </main>
    </body>
    </html>
    PHP,
        ];

        // Check that the project can be initialized
        foreach ($directories as $name) {
            if (file_exists($directory . DIRECTORY_SEPARATOR . $name)) {
                throw new \RuntimeException(
                    sprintf(
                        '"%s" already exists.',
                        $name
                    )
                );
            }
        }

        foreach ($files as $filename => $content) {
            if (file_exists($directory . DIRECTORY_SEPARATOR . $filename)) {
                throw new \RuntimeException(
                    sprintf(
                        '"%s" already exists.',
                        $filename
                    )
                );
            }
        }

        // Create directories
        foreach ($directories as $name) {
            $path = $directory . DIRECTORY_SEPARATOR . $name;

            if (!mkdir($path)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to create directory "%s".',
                        $path
                    )
                );
            }
        }

        // Create files
        foreach ($files as $filename => $content) {
            $path = $directory . DIRECTORY_SEPARATOR . $filename;

            if (file_put_contents($path, $content . PHP_EOL) === false) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to create file "%s".',
                        $path
                    )
                );
            }
        }

        $output->writeln('PSSG project initialized successfully.');

        return Command::SUCCESS;
    }
}
