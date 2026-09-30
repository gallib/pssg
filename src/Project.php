<?php

declare(strict_types=1);

namespace Gallib\PSSG;

class Project
{
    /**
     * Creates a project with the specified root directory and configuration.
     *
     * @param string $root   Path to the project root directory.
     * @param Config $config Project configuration.
     *
     * @return void
     */
    public function __construct(
        private readonly string $root,
        private readonly Config $config,
    ) {
    }

    /**
     * Creates a project from the current working directory.
     *
     * @return self
     */
    public static function fromCurrentDirectory(): self
    {
        $directory = getcwd();

        if ($directory === false) {
            throw new \RuntimeException(
                'Unable to determine the current directory.'
            );
        }

        $config = new Config(
            $directory . '/pssg.yml'
        );

        $project = new self(
            root: $directory,
            config: $config,
        );

        $project->validate();

        return $project;
    }

    /**
     * Returns the project root directory.
     *
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * Returns the site name.
     *
     * @return string
     */
    public function getSiteName(): string
    {
        return $this->config->getSiteName();
    }

    /**
     * Returns the absolute path to the pages directory.
     *
     * @return string
     */
    public function getPagesDirectory(): string
    {
        return $this->root
            . '/'
            . $this->config->getPagesDirectory();
    }

    /**
     * Returns the absolute path to the templates directory.
     *
     * @return string
     */
    public function getTemplatesDirectory(): string
    {
        return $this->root
            . '/'
            . $this->config->getTemplatesDirectory();
    }

    /**
     * Returns the absolute path to the assets directory.
     *
     * @return string
     */
    public function getAssetsDirectory(): string
    {
        return $this->root
            . '/'
            . $this->config->getAssetsDirectory();
    }

    /**
     * Returns the absolute path to the generated site directory.
     *
     * @return string
     */
    public function getSiteDirectory(): string
    {
        return $this->root
            . '/'
            . $this->config->getOutputDirectory();
    }

    /**
     * Returns the development server host.
     *
     * @return string
     */
    public function getServerHost(): string
    {
        return $this->config->getServerHost();
    }

    /**
     * Returns the development server port.
     *
     * @return integer
     */
    public function getServerPort(): int
    {
        return $this->config->getServerPort();
    }

    /**
     * Validates the project directory structure.
     *
     * @return void
     */
    public function validate(): void
    {
        if (!is_dir($this->getPagesDirectory())) {
            throw new \RuntimeException(
                sprintf(
                    'Pages directory "%s" not found.',
                    $this->getPagesDirectory()
                )
            );
        }

        if (!is_dir($this->getTemplatesDirectory())) {
            throw new \RuntimeException(
                sprintf(
                    'Templates directory "%s" not found.',
                    $this->getTemplatesDirectory()
                )
            );
        }
    }
}
