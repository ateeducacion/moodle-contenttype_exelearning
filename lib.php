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
 * Library functions for the eXeLearning content type plugin.
 *
 * @package    contenttype_exelearning
 * @copyright  2026 Área de Tecnología Educativa <ate.educacion@gobiernodecanarias.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Serves the files of an extracted eXeLearning package.
 *
 * The content bank lives at different context levels (system, course category,
 * course, user), so this callback must not assume CONTEXT_MODULE. Access is
 * gated by the content bank access capability on the file's context.
 *
 * @param stdClass $course Course object (site course for non-module contexts).
 * @param stdClass $cm Course module object (null for non-module contexts).
 * @param context $context The context of the file.
 * @param string $filearea The file area.
 * @param array $args Extra arguments (itemid, path).
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting the file serving.
 * @return bool|null False if the file is not found; null after sending it.
 */
function contenttype_exelearning_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $CFG, $DB;

    if ($filearea !== \contenttype_exelearning\local\packager::FILEAREA) {
        return false;
    }

    require_login();
    require_capability('moodle/contentbank:access', $context);

    $itemid = (int) array_shift($args);

    // The stored file area itemid is the content bank content id; make sure the
    // record exists for this context and content type before serving anything.
    $record = $DB->get_record('contentbank_content', [
        'id' => $itemid,
        'contextid' => $context->id,
        'contenttype' => 'contenttype_exelearning',
    ]);
    if (!$record) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = rtrim("/{$context->id}/contenttype_exelearning/{$filearea}/{$itemid}/{$relativepath}", '/');

    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file) {
        // Fallback to index.html inside the requested folder.
        foreach (['index.html', 'index.htm', 'Default.htm'] as $candidate) {
            $file = $fs->get_file_by_hash(sha1("{$fullpath}/{$candidate}"));
            if ($file) {
                break;
            }
        }
    }
    if (!$file || $file->is_directory()) {
        return false;
    }

    // Serve SVG inline (eXeLearning embeds icons that must render, not download).
    $options['dontforcesvgdownload'] = true;

    $lifetime = $CFG->filelifetime ?? 86400;
    send_stored_file($file, $lifetime, 0, $forcedownload, $options);

    return null;
}
