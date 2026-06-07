# eXeLearning Content Type for the Moodle Content Bank

Moodle content bank plugin that lets teachers and content creators upload
[eXeLearning](https://exelearning.net/) packages (`.elpx` and HTML5 `.zip`
exports) into the content bank, store and reuse them, and **view them rendered
inline** — the package is extracted and served in a sandboxed iframe, just like
`mod_exelearning` does for activities.

## Try in Moodle Playground

Click the badge below to open the `main` branch instantly in Moodle Playground
with the plugin pre-installed and a sample eXeLearning package already loaded in
the content bank. Every pull request also generates a playground preview link so
reviewers can test changes in a live Moodle instance without any local setup.

<a href="https://moodle-playground.com/?blueprint-url=https://raw.githubusercontent.com/ateeducacion/moodle-contenttype_exelearning/refs/heads/main/blueprint.json"><img src="https://raw.githubusercontent.com/ateeducacion/action-moodle-playground-pr-preview/refs/heads/main/assets/playground-preview-button.svg" alt="Preview in Moodle Playground" width="200"></a>

## Features

- Upload eXeLearning packages to the content bank: native `.elpx` projects and
  HTML5 `.zip` web exports.
- Render the package inline in the content bank visualizer (sandboxed iframe
  serving the package `index.html` and its assets).
- Keep the original package available for **download** and **copy**.
- Use the package screenshot as the content thumbnail when the export includes
  one, falling back to the plugin icon otherwise.
- Stores no personal data (null privacy provider).

## Accepted file types

Both `.elpx` and `.zip` are accepted. The real marker is an `index.html` entry
at the archive root, which every eXeLearning export includes; a `.zip` that is
not an eXeLearning export (no `index.html` at the root) is rejected with a clear
message. This plugin is a viewer/store — it does not grade interactive iDevices
(use `mod_exelearning` for graded activities).

## Compatibility

The plugin's minimum required Moodle version is **Moodle 4.4 LTS**
(`version.php`: `$plugin->requires = 2024042200`). Every push and pull request is
verified through a CI matrix (`moodle-ci.yml`):

| Moodle branch  | PHP      | Status                     |
| -------------- | -------- | -------------------------- |
| 4.4.x (LTS)    | 8.1, 8.3 | Supported (verified in CI) |
| 4.5.x (LTS)    | 8.1, 8.3 | Supported (verified in CI) |
| 5.0.x          | 8.2, 8.4 | Supported (verified in CI) |
| 5.1.x          | 8.2, 8.4 | Supported (verified in CI) |

Each branch is tested with PostgreSQL and MariaDB (rotated across PHP versions).
If you find an incompatibility please open an issue at
<https://github.com/ateeducacion/moodle-contenttype_exelearning/issues>.

### Requirements

* **Moodle**: 4.4 or later (CI-verified on 4.4, 4.5 LTS, 5.0 and 5.1).
* **PHP**: 8.1 through 8.4 (any PHP supported by the Moodle release in use).
* **Database**: PostgreSQL or MariaDB (CI-verified); any database supported by
  Moodle should work.
* **Browser**: any modern, evergreen browser with JavaScript enabled.

## Installation

> **Recommended:** install from a
> [release ZIP](https://github.com/ateeducacion/moodle-contenttype_exelearning/releases).
> Release ZIPs are produced by `release.yml` (or `make package RELEASE=X.Y.Z`)
> and only contain the files Moodle actually needs.

### Installing via uploaded ZIP file (recommended)

1. Download the latest ZIP from the
   [Releases](https://github.com/ateeducacion/moodle-contenttype_exelearning/releases)
   page.
2. Log in to your Moodle site as an admin and go to
   _Site administration > Plugins > Install plugins_.
3. Upload the ZIP file. The plugin type should be detected automatically
   (`contenttype`).
4. Check the plugin validation report and finish the installation.

### Installing manually

1. Download and extract the latest ZIP.
2. Place the extracted contents in
   `{your/moodle/dirroot}/contentbank/contenttype/exelearning`.
3. Log in as an admin and go to _Site administration > Notifications_ (or run
   `php admin/cli/upgrade.php`) to complete the installation.

## Usage

1. Open the **Content bank** (at system, category or course level).
2. Click **Upload** and choose an `.elpx` or `.zip` eXeLearning package.
3. Open the item to view it rendered inline. Use **Download** to get the
   original package or **Copy** to reuse it.

## Capabilities

* `contenttype/exelearning:access` — access eXeLearning content in the content bank.
* `contenttype/exelearning:upload` — upload new eXeLearning content.

## Development

For local development, the Docker stack, `moodle-plugin-ci` usage, the CI matrix
and packaging, see [DEVELOPMENT.md](DEVELOPMENT.md).

## Support

For issues or suggestions, use the **Issues** section in the
[GitHub repository](https://github.com/ateeducacion/moodle-contenttype_exelearning/issues).

## License

This project is licensed under **GPL v3**.

Copyright 2025-2026 Área de Tecnología Educativa.

## Author and Contact

Developed by the **Área de Tecnología Educativa** of the Government of the
Canary Islands.

- **Email:** [ate.educacion@gobiernodecanarias.org](mailto:ate.educacion@gobiernodecanarias.org)
- **Web:** [www3.gobiernodecanarias.org/medusa/ecoescuela/ate/](https://www3.gobiernodecanarias.org/medusa/ecoescuela/ate/)
