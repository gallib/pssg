<?php

declare(strict_types=1);

namespace Gallib\PSSG\Command;

use Gallib\PSSG\Builder;
use Gallib\PSSG\FileWatcher;
use Gallib\PSSG\Project;
use Gallib\PSSG\Server;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'serve',
    description: 'Serve the generated site',
)]
class ServeCommand extends Command
{
    /**
     * Executes the serve command.
     *
     * @param InputInterface  $input  Command input.
     * @param OutputInterface $output Command output.
     *
     * @return integer Command exit code.
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $project = Project::fromCurrentDirectory();

        $builder = new Builder(
            source: $project->getPagesDirectory(),
            destination: $project->getSiteDirectory(),
            assets: $project->getAssetsDirectory(),
            templates: $project->getTemplatesDirectory(),
            siteName: $project->getSiteName(),
        );

        $watcher = new FileWatcher([
            $project->getPagesDirectory(),
            $project->getTemplatesDirectory(),
            $project->getAssetsDirectory(),
        ]);

        $server = new Server(
            directory: $project->getSiteDirectory(),
            host: $project->getServerHost(),
            port: $project->getServerPort(),
        );

        $output->writeln('Construction du site...');

        $builder->build();

        $output->writeln('<info>Site généré.</info>');

        $server->start();

        $output->writeln(
            sprintf(
                '<info>Server started at http://%s:%d</info>',
                $project->getServerHost(),
                $project->getServerPort(),
            )
        );

        while ($server->isRunning()) {
            if ($watcher->hasChanged()) {
                $output->writeln('Change detected. Rebuilding...');

                $builder->build();

                $output->writeln('<info>Site built successfully.</info>');
            }

            usleep(500_000);
        }

        return Command::SUCCESS;
    }
}
