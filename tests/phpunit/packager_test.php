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
 * PHPUnit tests for the eXeLearning package helper.
 *
 * @package    contenttype_exelearning
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace contenttype_exelearning\local;

/**
 * Tests for {@see packager}.
 *
 * @covers \contenttype_exelearning\local\packager
 */
final class packager_test extends \advanced_testcase {
    /**
     * Creates a stored_file in the user draft area from a fixture.
     *
     * @param string $filename Fixture filename under tests/fixtures.
     * @return \stored_file
     */
    private function make_stored_file(string $filename): \stored_file {
        $fs = get_file_storage();
        $record = [
            'contextid' => \context_system::instance()->id,
            'component' => 'contenttype_exelearning',
            'filearea' => 'unittest',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ];
        return $fs->create_file_from_pathname($record, __DIR__ . '/../fixtures/' . $filename);
    }

    /**
     * A real eXeLearning package is accepted.
     */
    public function test_validate_accepts_real_package(): void {
        $this->resetAfterTest();
        $file = $this->make_stored_file('sample.elpx');
        $this->assertTrue(packager::archive_has_entrypoint($file));
        $this->assertTrue(packager::validate($file));
    }

    /**
     * A zip without index.html at the root is rejected.
     */
    public function test_validate_rejects_non_package(): void {
        $this->resetAfterTest();
        $file = $this->make_stored_file('invalid.zip');
        $this->assertFalse(packager::archive_has_entrypoint($file));
        $this->expectException(\moodle_exception::class);
        packager::validate($file);
    }

    /**
     * Extraction writes the package files and marks index.html as present.
     */
    public function test_extract_and_has_index(): void {
        $this->resetAfterTest();
        $contextid = \context_system::instance()->id;
        $itemid = 4242;
        $file = $this->make_stored_file('multipage.elpx');

        $this->assertFalse(packager::has_extracted_index($contextid, $itemid));
        packager::extract($file, $contextid, $itemid);
        $this->assertTrue(packager::has_extracted_index($contextid, $itemid));

        $fs = get_file_storage();
        // The index.html at root plus the extra page inside the html/ folder.
        $this->assertNotEmpty(
            $fs->get_file($contextid, packager::COMPONENT, packager::FILEAREA, $itemid, '/', 'index.html')
        );
        $this->assertNotEmpty(
            $fs->get_file($contextid, packager::COMPONENT, packager::FILEAREA, $itemid, '/html/', 'page-2.html')
        );
    }

    /**
     * Re-extracting clears the previous content (idempotent).
     */
    public function test_extract_is_idempotent(): void {
        $this->resetAfterTest();
        $contextid = \context_system::instance()->id;
        $itemid = 99;

        packager::extract($this->make_stored_file('multipage.elpx'), $contextid, $itemid);
        $fs = get_file_storage();
        $this->assertNotEmpty(
            $fs->get_file($contextid, packager::COMPONENT, packager::FILEAREA, $itemid, '/html/', 'page-2.html')
        );

        // Re-extract a package that does not contain html/page-2.html.
        packager::extract($this->make_stored_file('sample.elpx'), $contextid, $itemid);
        $this->assertTrue(packager::has_extracted_index($contextid, $itemid));
        $this->assertFalse(
            (bool) $fs->get_file($contextid, packager::COMPONENT, packager::FILEAREA, $itemid, '/html/', 'page-2.html')
        );
    }

    /**
     * The entry point URL targets the plugin pluginfile callback.
     */
    public function test_get_entrypoint_url(): void {
        $this->resetAfterTest();
        $url = packager::get_entrypoint_url(\context_system::instance()->id, 7);
        $this->assertStringContainsString('/contenttype_exelearning/content/7/index.html', $url->out(false));
    }
}
