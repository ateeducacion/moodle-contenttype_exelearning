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
 * Content class for eXeLearning packages.
 *
 * @package    contenttype_exelearning
 * @copyright  2026 Área de Tecnología Educativa <ate.educacion@gobiernodecanarias.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace contenttype_exelearning;

use contenttype_exelearning\local\packager;
use core_contentbank\content as base_content;
use stored_file;

/**
 * eXeLearning content item stored in the content bank.
 *
 * The original package is kept in the standard contentbank/public file area
 * (so download and copy keep working); on import it is also extracted into the
 * plugin's own file area so its index.html can be served and rendered.
 */
class content extends base_content {
    /**
     * Import the uploaded package and extract it for rendering.
     *
     * @param stored_file $file File to store in the content file area.
     * @return stored_file|null the stored content file or null if discarded.
     */
    public function import_file(stored_file $file): ?stored_file {
        $stored = parent::import_file($file);
        if ($stored) {
            packager::extract($stored, (int) $this->get_contextid(), $this->get_id());
        }

        return $stored;
    }
}
