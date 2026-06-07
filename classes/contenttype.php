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
 * Content type definition for eXeLearning packages.
 *
 * @package    contenttype_exelearning
 * @copyright  2026 Área de Tecnología Educativa <ate.educacion@gobiernodecanarias.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace contenttype_exelearning;

use contenttype_exelearning\local\packager;
use core\event\contentbank_content_viewed;
use core_contentbank\contenttype as base_contenttype;
use core_contentbank\content as base_content;
use stdClass;
use stored_file;

/**
 * eXeLearning content type class.
 *
 * Stores uploaded eXeLearning packages (.elpx and .zip web exports) in the
 * content bank and renders them inline by extracting the package and serving
 * its index.html in a sandboxed iframe (the same technique used by mod_exelearning).
 */
class contenttype extends base_contenttype {
    /** @var string Plugin content type name. */
    public const TYPE = 'exelearning';

    /**
     * Returns the plugin name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('pluginname', 'contenttype_exelearning');
    }

    /**
     * Returns features implemented by this content type.
     *
     * eXeLearning packages are uploaded, downloaded and copied, but not edited
     * in place (there is no in-browser editor in the content bank).
     *
     * @return array
     */
    protected function get_implemented_features(): array {
        return [self::CAN_UPLOAD, self::CAN_DOWNLOAD, self::CAN_COPY];
    }

    /**
     * Allowed file extensions that can be managed.
     *
     * Both the native eXeLearning package (.elpx) and the HTML5 web export (.zip)
     * are accepted; the real marker is an index.html entry at the archive root,
     * validated on upload.
     *
     * @return array
     */
    public function get_manageable_extensions(): array {
        return ['.elpx', '.zip'];
    }

    /**
     * Returns the list of content type creation options provided by this plugin.
     *
     * This plugin only supports uploading packages (no in-browser editor), so it
     * provides no "create" options for the content bank Add dropdown.
     *
     * @return array
     */
    public function get_contenttype_types(): array {
        return [];
    }

    /**
     * Uploads an eXeLearning package into the content bank.
     *
     * @param stored_file $file uploaded file
     * @param stdClass|null $record content record data
     * @return base_content
     */
    public function upload_content(stored_file $file, ?stdClass $record = null): base_content {
        global $USER;

        // Validate the archive is a real eXeLearning package (index.html at root).
        packager::validate($file);

        if ($record === null) {
            $record = new stdClass();
        }

        $record->name = $record->name ?? $file->get_filename();
        $record->usercreated = $record->usercreated ?? $USER->id;

        // The base implementation creates the DB record and calls content::import_file(),
        // which stores the original package and extracts it for rendering.
        return parent::upload_content($file, $record);
    }

    /**
     * Returns the HTML to render the package inside the content bank visualizer.
     *
     * @param base_content $content The content to be displayed.
     * @return string HTML code to include in view.php.
     */
    public function get_view_content(base_content $content): string {
        global $OUTPUT;

        // Trigger an event for viewing this content.
        $event = contentbank_content_viewed::create_from_record($content->get_content());
        $event->trigger();

        // Self-heal: programmatic uploads (e.g. the Moodle Playground) may store the
        // package without extracting it. Extract on first view when needed.
        $contextid = (int) $content->get_contextid();
        $itemid = $content->get_id();
        if (!packager::has_extracted_index($contextid, $itemid)) {
            if ($file = $content->get_file()) {
                packager::extract($file, $contextid, $itemid);
            }
        }

        if (!packager::has_extracted_index($contextid, $itemid)) {
            return $OUTPUT->notification(
                get_string('packagenotfound', 'contenttype_exelearning'),
                \core\output\notification::NOTIFY_ERROR
            );
        }

        return packager::render_iframe($content);
    }

    /**
     * Returns the icon for the content, using the package screenshot when available.
     *
     * @param base_content $content The content to be displayed.
     * @return string Icon URL.
     */
    public function get_icon(base_content $content): string {
        global $OUTPUT;

        $fs = get_file_storage();
        $screenshot = $fs->get_file(
            (int) $content->get_contextid(),
            packager::COMPONENT,
            packager::FILEAREA,
            $content->get_id(),
            '/',
            'screenshot.png'
        );
        if ($screenshot && !$screenshot->is_directory()) {
            return \moodle_url::make_pluginfile_url(
                $screenshot->get_contextid(),
                $screenshot->get_component(),
                $screenshot->get_filearea(),
                $screenshot->get_itemid(),
                $screenshot->get_filepath(),
                $screenshot->get_filename()
            )->out(false);
        }

        return $OUTPUT->image_url('icon', 'contenttype_exelearning')->out(false);
    }

    /**
     * Deletes the content and the extracted package files.
     *
     * @param base_content $content The content to delete.
     * @return bool true if the content has been deleted; false otherwise.
     */
    public function delete_content(base_content $content): bool {
        $fs = get_file_storage();
        $fs->delete_area_files(
            (int) $content->get_contextid(),
            packager::COMPONENT,
            packager::FILEAREA,
            $content->get_id()
        );

        return parent::delete_content($content);
    }
}
