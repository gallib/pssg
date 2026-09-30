<?php

declare(strict_types=1);

namespace Gallib\PSSG;

use Symfony\Component\Yaml\Yaml;

class Config
{
    private const DEFAULT_SITE_NAME = 'PSSG';

    private const DEFAULT_PAGES_DIRECTORY = 'pages';
    private const DEFAULT_TEMPLATES_DIRECTORY = 'templates';
    private const DEFAULT_ASSETS_DIRECTORY = 'assets';
    private const DEFAULT_OUTPUT_DIRECTORY = 'site';

    private const DEFAULT_SERVER_HOST = '0.0.0.0';
    private const DEFAULT_SERVER_PORT = 8000;

    /**
     * Parsed configuration data.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $data;

    /**
     * Loads the configuration from a YAML file.
     *
     * @param string $filename Path to the configuration file.
     *
     * @return void
     */
    public function __construct(string $filename)
    {
        if (!is_file($filename)) {
            throw new \RuntimeException(
                sprintf(
                    'Configuration file "%s" not found.',
                    $filename
                )
            );
        }

        $data = Yaml::parseFile($filename);

        if (!is_array($data)) {
            throw new \RuntimeException(
                'The configuration file is invalid.'
            );
        }

        foreach ($data as $section => $values) {
            if (!is_string($section) || !is_array($values)) {
                throw new \RuntimeException(
                    'The configuration file has an invalid structure.'
                );
            }
        }

        /** @var array<string, array<string, mixed>> $data */
        $this->data = $data;
    }

    /**
     * Returns the site name.
     *
     * @return string
     */
    public function getSiteName(): string
    {
        return $this->getString(
            'site',
            'name',
            self::DEFAULT_SITE_NAME
        );
    }

    /**
     * Returns the configured pages directory.
     *
     * @return string
     */
    public function getPagesDirectory(): string
    {
        return $this->getString(
            'directories',
            'pages',
            self::DEFAULT_PAGES_DIRECTORY
        );
    }

    /**
     * Returns the configured templates directory.
     *
     * @return string
     */
    public function getTemplatesDirectory(): string
    {
        return $this->getString(
            'directories',
            'templates',
            self::DEFAULT_TEMPLATES_DIRECTORY
        );
    }

    /**
     * Returns the configured assets directory.
     *
     * @return string
     */
    public function getAssetsDirectory(): string
    {
        return $this->getString(
            'directories',
            'assets',
            self::DEFAULT_ASSETS_DIRECTORY
        );
    }

    /**
     * Returns the configured output directory.
     *
     * @return string
     */
    public function getOutputDirectory(): string
    {
        return $this->getString(
            'directories',
            'output',
            self::DEFAULT_OUTPUT_DIRECTORY
        );
    }

    /**
     * Returns the configured server host.
     *
     * @return string
     */
    public function getServerHost(): string
    {
        return $this->getString(
            'server',
            'host',
            self::DEFAULT_SERVER_HOST
        );
    }

    /**
     * Returns the configured server port.
     *
     * @return integer
     */
    public function getServerPort(): int
    {
        return $this->getInt(
            'server',
            'port',
            self::DEFAULT_SERVER_PORT
        );
    }

    /**
     * Returns a string configuration value.
     *
     * @param string $section Configuration section.
     * @param string $key     Configuration key.
     * @param string $default Default value.
     *
     * @return string
     */
    private function getString(
        string $section,
        string $key,
        string $default,
    ): string {
        $value = $this->data[$section][$key] ?? $default;

        if (!is_string($value)) {
            throw new \RuntimeException(
                sprintf(
                    'The "%s.%s" property must be a string.',
                    $section,
                    $key
                )
            );
        }

        return $value;
    }

    /**
     * Returns an integer configuration value.
     *
     * @param string  $section Configuration section.
     * @param string  $key     Configuration key.
     * @param integer $default Default value.
     *
     * @return integer
     */
    private function getInt(
        string $section,
        string $key,
        int $default,
    ): int {
        $value = $this->data[$section][$key] ?? $default;

        if (!is_int($value)) {
            throw new \RuntimeException(
                sprintf(
                    'The "%s.%s" property must be an integer.',
                    $section,
                    $key
                )
            );
        }

        return $value;
    }
}
