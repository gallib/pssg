# PSSG

PSSG (PHP Static Site Generator) is a lightweight static site generator written in PHP.

It converts Markdown files into static HTML pages using PHP templates.

## Requirements

* PHP 8.5 or later
* Composer

## Installation

Create a new directory for your website and initialize a Composer project:

```bash
mkdir my-site
cd my-site
composer init
```

Install PSSG:

```bash
composer require gallib/pssg
```

Initialize the site:

```bash
vendor/bin/pssg init
```

PSSG creates the following structure:

```text
my-site/
├── assets/
├── pages/
│   └── index.md
├── templates/
│   └── default.php
├── pssg.yml
└── vendor/
```

## Build

Generate the static website with:

```bash
vendor/bin/pssg build
```

The generated files are written to the `site/` directory by default.

## Development server

Start the development server with:

```bash
vendor/bin/pssg serve
```

PSSG watches the pages, templates and assets directories and automatically rebuilds the website when a file changes.

By default, the development server is available on port `8000`.

## Pages

Pages are Markdown files stored in the `pages/` directory.

For example:

```markdown
# Hello PSSG

Welcome to my website.
```

The directory structure is preserved when the website is generated.

For example:

```text
pages/
├── index.md
└── about/
    └── index.md
```

generates:

```text
site/
├── index.html
└── about/
    └── index.html
```

## Front matter

Pages can define additional data using YAML front matter:

```markdown
---
title: About
author: John Doe
---

# About

This is the about page.
```

Front matter values are available as variables inside the PHP template.

## Templates

Templates are PHP files stored in the `templates/` directory.

The default template is:

```text
templates/default.php
```

A page can use another template with its front matter:

```markdown
---
template: article.php
title: My article
---
```

Templates can access the generated page content with:

```php
<?= $content ?>
```

The configured site name is available with:

```php
<?= $siteName ?>
```

## Assets

Files stored in `assets/` are copied to:

```text
site/assets/
```

The directory structure is preserved.

## Configuration

PSSG is configured using `pssg.yml`:

```yaml
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
```

## Commands

```bash
vendor/bin/pssg init
vendor/bin/pssg build
vendor/bin/pssg serve
```

## License

PSSG is released under the MIT License.
