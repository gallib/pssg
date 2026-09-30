<?php

declare(strict_types=1);

namespace Gallib\PSSG;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Output\RenderedContentInterface;

class Builder
{
    /**
     * Markdown converter used to render Markdown content.
     *
     * @var MarkdownConverter
     */
    private MarkdownConverter $converter;

    /**
     * Creates a builder for the specified project directories.
     *
     * @param string $source      Path to the source pages directory.
     * @param string $destination Path to the output directory.
     * @param string $assets      Path to the assets directory.
     * @param string $templates   Path to the templates directory.
     * @param string $siteName    Name of the site.
     *
     * @return void
     */
    public function __construct(
        private readonly string $source,
        private readonly string $destination,
        private readonly string $assets,
        private readonly string $templates,
        private readonly string $siteName,
    ) {
        $environment = new Environment();

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new FrontMatterExtension());

        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Builds the complete static site.
     *
     * @return void
     */
    public function build(): void
    {
        $this->clean();

        $this->buildPages();
        $this->copyAssets();
    }

    /**
     * Builds all Markdown pages from the source directory.
     *
     * @return void
     */
    private function buildPages(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->source)
        );

        foreach ($files as $file) {
            /** @var \SplFileInfo $file */

            if (!$file->isFile()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== 'md') {
                continue;
            }

            $this->buildPage($file->getPathname());
        }
    }

    /**
     * Builds a single Markdown page.
     *
     * @param string $sourceFile Path to the source Markdown file.
     *
     * @return void
     */
    private function buildPage(string $sourceFile): void
    {
        $markdown = $this->readFile($sourceFile);

        $result = $this->converter->convert($markdown);

        [$templateFile, $data] = $this->preparePage($result);

        $html = $this->render(
            $templateFile,
            $data
        );

        $destinationFile = $this->getDestinationFile(
            $sourceFile
        );

        $this->writeFile(
            $destinationFile,
            $html
        );
    }

    /**
     * Copies all assets to the destination directory.
     *
     * @return void
     */
    private function copyAssets(): void
    {
        if (!is_dir($this->assets)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->assets,
                \FilesystemIterator::SKIP_DOTS
            )
        );

        foreach ($files as $file) {
            /** @var \SplFileInfo $file */

            if (!$file->isFile()) {
                continue;
            }

            $relativePath = substr(
                $file->getPathname(),
                strlen($this->assets) + 1
            );

            $destinationFile =
                $this->destination . '/assets/' . $relativePath;

            $destinationDirectory = dirname($destinationFile);

            $this->createDirectory($destinationDirectory);

            if (!copy($file->getPathname(), $destinationFile)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to copy file "%s" to "%s".',
                        $file->getPathname(),
                        $destinationFile
                    )
                );
            }
        }
    }

    /**
     * Removes all generated files from the destination directory.
     *
     * @return void
     */
    private function clean(): void
    {
        if (!is_dir($this->destination)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->destination,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            /** @var \SplFileInfo $file */

            if ($file->isDir()) {
                if (!rmdir($file->getPathname())) {
                    throw new \RuntimeException(
                        sprintf(
                            'Unable to remove directory "%s".',
                            $file->getPathname()
                        )
                    );
                }
            } else {
                if (!unlink($file->getPathname())) {
                    throw new \RuntimeException(
                        sprintf(
                            'Unable to remove file "%s".',
                            $file->getPathname()
                        )
                    );
                }
            }
        }
    }

    /**
     * Renders a template with the provided data.
     *
     * @param string               $template Path to the template file.
     * @param array<string, mixed> $data     Data available to the template.
     *
     * @return string
     */
    private function render(string $template, array $data): string
    {
        if (!array_key_exists('content', $data)) {
            throw new \InvalidArgumentException(
                'The "content" data is required to render a template.'
            );
        }

        extract($data);

        ob_start();

        require $template;

        $content = ob_get_clean();

        if ($content === false) {
            throw new \RuntimeException(
                'Unable to retrieve the rendered template.'
            );
        }

        return $content;
    }

    /**
     * Creates a directory if it does not exist.
     *
     * @param string $directory Path to the directory.
     *
     * @return void
     */
    private function createDirectory(string $directory): void
    {
        if (
            !is_dir($directory)
            && !mkdir($directory, 0755, true)
        ) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to create directory "%s".',
                    $directory
                )
            );
        }
    }

    /**
     * Reads the contents of a file.
     *
     * @param string $filename Path to the file to read.
     *
     * @return string
     */
    private function readFile(string $filename): string
    {
        $content = file_get_contents($filename);

        if ($content === false) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to read file "%s".',
                    $filename
                )
            );
        }

        return $content;
    }

    /**
     * Prepares the template and data used to render a page.
     *
     * @param RenderedContentInterface $result Converted Markdown content.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function preparePage(
        RenderedContentInterface $result,
    ): array {
        $template = 'default.php';

        $data = [
            'siteName' => $this->siteName,
            'content' => $result->getContent(),
        ];

        if ($result instanceof RenderedContentWithFrontMatter) {
            $frontMatter = $result->getFrontMatter();

            if (!is_array($frontMatter)) {
                throw new \RuntimeException(
                    'Front matter must be an array.'
                );
            }

            foreach (array_keys($frontMatter) as $key) {
                if (!is_string($key)) {
                    throw new \RuntimeException(
                        'Front matter keys must be strings.'
                    );
                }
            }

            /** @var array<string, mixed> $frontMatter */

            if (isset($frontMatter['template'])) {
                if (!is_string($frontMatter['template'])) {
                    throw new \RuntimeException(
                        'The "template" property must be a string.'
                    );
                }

                $template = $frontMatter['template'];

                unset($frontMatter['template']);
            }

            $data = array_merge(
                $frontMatter,
                $data
            );
        }

        return [
            $this->resolveTemplate($template),
            $data,
        ];
    }

    /**
     * Resolves and validates a template file.
     *
     * @param string $template Template path relative to the templates directory.
     *
     * @return string
     */
    private function resolveTemplate(string $template): string
    {
        $templatesDirectory = realpath($this->templates);

        if ($templatesDirectory === false) {
            throw new \RuntimeException(
                'Templates directory not found.'
            );
        }

        $templateFile = realpath(
            $this->templates . '/' . $template
        );

        if ($templateFile === false || !is_file($templateFile)) {
            throw new \RuntimeException(
                sprintf(
                    'Template "%s" not found.',
                    $template
                )
            );
        }

        if (
            !str_starts_with(
                $templateFile,
                $templatesDirectory . DIRECTORY_SEPARATOR
            )
        ) {
            throw new \RuntimeException(
                sprintf(
                    'Template "%s" is outside the templates directory.',
                    $template
                )
            );
        }

        return $templateFile;
    }

    /**
     * Returns the destination path for a source file.
     *
     * @param string $sourceFile Path to the source Markdown file.
     *
     * @return string
     */
    private function getDestinationFile(string $sourceFile): string
    {
        $relativePath = substr(
            $sourceFile,
            strlen($this->source) + 1
        );

        $relativePath = preg_replace(
            '/\.md$/i',
            '.html',
            $relativePath
        );

        return $this->destination . '/' . $relativePath;
    }

    /**
     * Writes content to a file.
     *
     * @param string $filename Path to the destination file.
     * @param string $content  Content to write.
     *
     * @return void
     */
    private function writeFile(
        string $filename,
        string $content,
    ): void {
        $this->createDirectory(
            dirname($filename)
        );

        if (file_put_contents($filename, $content) === false) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to write file "%s".',
                    $filename
                )
            );
        }
    }
}
