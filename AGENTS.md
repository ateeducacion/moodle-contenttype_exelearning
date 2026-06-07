# AGENTS.md

This file provides guidance to AI coding agents when working with code in this repository.

## Project Overview

`contenttype_exelearning` is a Moodle **content bank** content type plugin that
lets users upload eXeLearning packages (`.elpx` and HTML5 `.zip` exports), store
them in the content bank and view them rendered inline. The original package is
kept for download/copy; on import it is extracted and its `index.html` is served
through the plugin's `pluginfile` callback and embedded in a sandboxed iframe.

**Component**: `contenttype_exelearning`
**Moodle path**: `contentbank/contenttype/exelearning`
**Moodle compatibility**: 4.4 LTS+
**License**: GNU GPL v3+

## Architecture

The plugin follows the standard Moodle content bank content type pattern
(mirroring `contenttype_h5p`):

- `classes/contenttype.php` — `contenttype_exelearning\contenttype` extends
  `core_contentbank\contenttype`. Declares features (`CAN_UPLOAD`,
  `CAN_DOWNLOAD`, `CAN_COPY`) and managed extensions (`.elpx`, `.zip`),
  validates uploads, renders the package via `get_view_content()` (sandboxed
  iframe, with self-heal extraction), exposes the screenshot as the icon, and
  cleans up extracted files on `delete_content()`.
- `classes/content.php` — `contenttype_exelearning\content` extends
  `core_contentbank\content`. Overrides `import_file()` to store the original
  package (base behaviour) and then extract it for rendering.
- `classes/local/packager.php` — `contenttype_exelearning\local\packager`: the
  validation (`index.html` at archive root), extraction (to the
  `contenttype_exelearning/content/{itemid}` file area), self-heal helper and
  iframe builder. This is the reusable, unit-testable core.
- `classes/privacy/provider.php` — null privacy provider (no personal data).
- `lib.php` — `contenttype_exelearning_pluginfile()` serves the extracted
  package files (with `index.html` fallback and inline SVG), gated by the
  content bank access capability on the file context.
- `db/access.php` — `contenttype/exelearning:access` and
  `contenttype/exelearning:upload` capabilities.

## Project Structure

```
contenttype_exelearning/
  version.php                     # Plugin version metadata
  lib.php                         # pluginfile callback (serves extracted package)
  classes/
    contenttype.php              # Content type manager
    content.php                  # Content item (extract on import)
    local/
      packager.php               # Validate / extract / render helper
    privacy/
      provider.php               # Null privacy provider
  db/
    access.php                   # Capability definitions
  lang/
    en/contenttype_exelearning.php
    es/contenttype_exelearning.php
  pix/
    icon.svg                     # Plugin icon
  tests/
    phpunit/
      contenttype_test.php
      packager_test.php
    behat/
      contenttype_exelearning.feature
    fixtures/
      sample.elpx                # Real eXeLearning package (rendering + blueprint)
      multipage.elpx             # Tiny multipage package (path tests)
      invalid.zip                # Zip without index.html (rejection test)
  blueprint.json                 # Moodle Playground setup
  Makefile                       # Development commands
  docker-compose.yml             # Local dev stack
  docker-compose.test.yml        # Minimal DB for `make test`
  composer.json                  # PHP dependencies
```

## Build, Test, and Development Commands

The full development, local-testing and CI workflow lives in
[DEVELOPMENT.md](DEVELOPMENT.md). Quick reference:

```bash
make upd                       # Start Docker services in background (Moodle + MariaDB)
make shell                     # Open interactive shell in the Moodle container
make ci-deps                   # Install moodle-plugin-ci into ./ci (run once)
make lint                      # phplint + phpmd + phpcs
make phpcs                     # Moodle CodeSniffer standard only
make phpcbf                    # Auto-fix CodeSniffer violations
make test                      # PHPUnit via minimal DB stack
make behat                     # Behat scenarios tagged @contenttype_exelearning
make check                     # Full CI suite: analysis + tests
make package RELEASE=X.Y.Z     # Build distributable ZIP (honours .distignore)
```

Run `make ci-deps` before any `make lint / test / behat / check` on a fresh
checkout. For the full list of targets and knobs, read
[DEVELOPMENT.md](DEVELOPMENT.md).

## Coding Style & Naming Conventions

- **Standard**: Moodle PHP coding guidelines — 4 spaces, no tabs, Unix line
  endings.
- **Linting**: `make phpcs` (CodeSniffer, Moodle standard); `make phpcbf` to
  auto-fix.
- **Namespaces**: `contenttype_exelearning\` for the content type/content
  classes; `contenttype_exelearning\local\` for helpers;
  `contenttype_exelearning\privacy\` for the privacy provider.
- **Strings**: all UI strings in `lang/en/contenttype_exelearning.php`; use
  `get_string('key', 'contenttype_exelearning')`.
- **File serving**: extracted package files live under the
  `contenttype_exelearning` / `content` file area, served by
  `contenttype_exelearning_pluginfile()`. The original package stays in the core
  `contentbank` / `public` area (download/copy).
- **No direct `echo`**: use Moodle output functions or return HTML strings.

## Testing Guidelines

### PHPUnit

- Tests live in `tests/phpunit/*_test.php`.
- Namespaces mirror the code: `contenttype_exelearning` and
  `contenttype_exelearning\local`.
- Run all: `make test`.

### Behat

- Feature files in `tests/behat/`; scenarios tagged `@contenttype_exelearning`.
- The rendering scenario seeds a content bank item with the `contentbank
  content` generator (fixture `tests/fixtures/sample.elpx`) and asserts the
  iframe renders.
- Run: `make behat` (uses the Chromedriver container in `docker-compose.yml`).

## Commit & Pull Request Guidelines

- Commit messages: imperative mood, concise. Conventional Commits optional
  (`feat:`, `fix:`, `refactor:`, `test:`, `docs:`).
- PRs: describe intent, link related issues, list manual testing steps.
- Each PR automatically generates a preview on Moodle Playground via
  `.github/workflows/pr-playground-preview.yml`.
- `make check` must pass before merging.

## Releases

- Publish a GitHub Release (tag `vX.Y.Z`) to trigger
  `.github/workflows/release.yml`. The workflow runs `make package RELEASE=$TAG`
  and uploads the resulting ZIP to the release. It can also be triggered manually
  from the Actions tab (`workflow_dispatch`).
- To build locally: `make package RELEASE=X.Y.Z` (creates
  `contenttype_exelearning-X.Y.Z.zip`, staging the tree via
  `rsync --exclude-from=.distignore`; the ZIP root is `exelearning/` so it can be
  uploaded directly from _Site administration > Plugins > Install plugins_).

## External References

- Moodle Content Bank API: https://moodledev.io/docs/apis/plugintypes/contentbank
- eXeLearning: https://exelearning.net/
- Sibling activity plugin: https://github.com/ateeducacion/mod_exelearning
- PR Preview Action: https://github.com/ateeducacion/action-moodle-playground-pr-preview
