<?php

declare(strict_types=1);

namespace Gallib\PSSG\Command;

use Gallib\PSSG\Builder;
use Gallib\PSSG\Project;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'build',
    description: 'Build the static site',
)]
class BuildCommand extends Command
{
    /**
     * Executes the build command.
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
        $output->writeln('Building site...');

        $project = Project::fromCurrentDirectory();

        $builder = new Builder(
            source: $project->getPagesDirectory(),
            destination: $project->getSiteDirectory(),
            assets: $project->getAssetsDirectory(),
            templates: $project->getTemplatesDirectory(),
            siteName: $project->getSiteName(),
        );

        $builder->build();

        $output->writeln('<info>Site built successfully.</info>');

        return Command::SUCCESS;
    }
}
