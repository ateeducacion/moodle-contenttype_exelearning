<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Validation, extraction and rendering helpers for eXeLearning packages.
 *
 * @package    contenttype_exelearning
 * @copyright  2026 Área de Tecnología Educativa <ate.educacion@gobiernodecanarias.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace contenttype_exelearning\local;

use core_contentbank\content as base_content;
use html_writer;
use moodle_exception;
use moodle_url;
use stored_file;

/**
 * Helper that validates, extracts and renders eXeLearning packages.
 */
class packager {
    /** @var string Frankenstyle component owning the extracted files. */
    public const COMPONENT = 'contenttype_exelearning';

    /** @var string File area where packages are extracted for serving. */
    public const FILEAREA = 'content';

    /** @var string Entry point served inside the iframe. */
    public const ENTRYPOINT = 'index.html';

    /**
     * Validates that the given file is an eXeLearning package.
     *
     * Independent of the MIME type (.elpx is not a registered Moodle mimetype):
     * the archive is opened as a zip and must contain an index.html entry at its
     * root, the entry point every eXeLearning export includes.
     *
     * @param stored_file $file The uploaded package (.elpx or .zip).
     * @return bool Always true when valid.
     * @throws moodle_exception When the archive is not a valid eXeLearning package.
     */
    public static function validate(stored_file $file): bool {
        if (!self::archive_has_entrypoint($file)) {
            throw new moodle_exception('invalidpackage', 'contenttype_exelearning');
        }

        return true;
    }

    /**
     * Whether the archive contains index.html at its root.
     *
     * @param stored_file $file The uploaded package.
     * @return bool
     */
    public static function archive_has_entrypoint(stored_file $file): bool {
        $packer = get_file_packer('application/zip');
        $entries = $file->list_files($packer);
        if (!is_array($entries)) {
            return false;
        }
        foreach ($entries as $entry) {
            if ($entry->pathname === self::ENTRYPOINT) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extracts the package into the plugin file area so it can be served.
     *
     * Idempotent: any previously extracted content for this item is cleared first.
     *
     * @param stored_file $file The stored package to extract.
     * @param int $contextid The context where the content lives.
     * @param int $itemid The content bank content id.
     * @return void
     */
    public static function extract(stored_file $file, int $contextid, int $itemid): void {
        $fs = get_file_storage();

        // Clear previous content and re-extract.
        $fs->delete_area_files($contextid, self::COMPONENT, self::FILEAREA, $itemid);

        $packer = get_file_packer('application/zip');
        $file->extract_to_storage($packer, $contextid, self::COMPONENT, self::FILEAREA, $itemid, '/');

        // Mark index.html as the main file (for the file browser).
        $entry = $fs->get_file($contextid, self::COMPONENT, self::FILEAREA, $itemid, '/', self::ENTRYPOINT);
        if ($entry) {
            file_set_sortorder($contextid, self::COMPONENT, self::FILEAREA, $itemid, '/', self::ENTRYPOINT, 1);
        }
    }

    /**
     * Whether the extracted entry point exists for the given content.
     *
     * @param int $contextid The context where the content lives.
     * @param int $itemid The content bank content id.
     * @return bool
     */
    public static function has_extracted_index(int $contextid, int $itemid): bool {
        $fs = get_file_storage();
        $entry = $fs->get_file($contextid, self::COMPONENT, self::FILEAREA, $itemid, '/', self::ENTRYPOINT);

        return $entry && !$entry->is_directory();
    }

    /**
     * Returns the pluginfile URL of the extracted entry point.
     *
     * @param int $contextid The context where the content lives.
     * @param int $itemid The content bank content id.
     * @return moodle_url
     */
    public static function get_entrypoint_url(int $contextid, int $itemid): moodle_url {
        return moodle_url::make_pluginfile_url(
            $contextid,
            self::COMPONENT,
            self::FILEAREA,
            $itemid,
            '/',
            self::ENTRYPOINT
        );
    }

    /**
     * Builds the sandboxed iframe that renders the package.
     *
     * Sandbox policy mirrors mod_exelearning: scripts + same-origin (relative
     * paths to pluginfile.php), popups and forms for interactive iDevices; top
     * navigation and modals stay blocked.
     *
     * @param base_content $content The content to render.
     * @return string HTML for the content bank visualizer.
     */
    public static function render_iframe(base_content $content): string {
        $contextid = (int) $content->get_contextid();
        $itemid = $content->get_id();
        $url = self::get_entrypoint_url($contextid, $itemid);

        return html_writer::tag('iframe', '', [
            'src' => $url->out(false),
            'name' => 'contenttype_exelearning_' . $itemid,
            'id' => 'contenttype_exelearning_' . $itemid,
            'title' => format_string($content->get_name()),
            'width' => '100%',
            'height' => '650',
            'allow' => 'fullscreen',
            'sandbox' => 'allow-scripts allow-same-origin allow-popups allow-forms allow-popups-to-escape-sandbox',
            'class' => 'contenttype-exelearning-frame',
            'style' => 'border: 1px solid var(--bs-border-color, #dee2e6); border-radius: .5rem;',
        ]);
    }
}
