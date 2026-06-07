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
 * PHPUnit tests for the eXeLearning content type.
 *
 * @package    contenttype_exelearning
 * @category   test
 * @copyright  2026 Área de Tecnología Educativa
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace contenttype_exelearning;

use contenttype_exelearning\local\packager;

/**
 * Tests for {@see contenttype} and {@see content}.
 *
 * @covers \contenttype_exelearning\contenttype
 * @covers \contenttype_exelearning\content
 */
final class contenttype_test extends \advanced_testcase {
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
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => file_get_unused_draft_itemid(),
            'filepath' => '/',
            'filename' => $filename,
        ];
        return $fs->create_file_from_pathname($record, __DIR__ . '/../fixtures/' . $filename);
    }

    /**
     * The plugin advertises the eXeLearning extensions.
     */
    public function test_manageable_extensions(): void {
        $contenttype = new contenttype(\context_system::instance());
        $extensions = $contenttype->get_manageable_extensions();
        $this->assertContains('.elpx', $extensions);
        $this->assertContains('.zip', $extensions);
    }

    /**
     * Upload, download and copy are supported; the in-browser editor is not.
     */
    public function test_implemented_features(): void {
        $contenttype = new contenttype(\context_system::instance());
        $this->assertTrue($contenttype->is_feature_supported(contenttype::CAN_UPLOAD));
        $this->assertTrue($contenttype->is_feature_supported(contenttype::CAN_DOWNLOAD));
        $this->assertTrue($contenttype->is_feature_supported(contenttype::CAN_COPY));
        $this->assertFalse($contenttype->is_feature_supported(contenttype::CAN_EDIT));
    }

    /**
     * Upload-only content type offers no editor creation options.
     */
    public function test_no_creation_types(): void {
        $contenttype = new contenttype(\context_system::instance());
        $this->assertSame([], $contenttype->get_contenttype_types());
    }

    /**
     * Uploading a real package creates content and extracts its entry point.
     */
    public function test_upload_extracts_package(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $context = \context_system::instance();
        $contenttype = new contenttype($context);

        $content = $contenttype->upload_content($this->make_stored_file('sample.elpx'));

        $this->assertInstanceOf(content::class, $content);
        // The original package remains available for download/copy.
        $this->assertNotEmpty($content->get_file());
        // And the package was extracted for rendering.
        $this->assertTrue(packager::has_extracted_index((int) $context->id, $content->get_id()));
    }

    /**
     * Uploading a non-eXeLearning zip is rejected.
     */
    public function test_upload_rejects_invalid_package(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $contenttype = new contenttype(\context_system::instance());

        $this->expectException(\moodle_exception::class);
        $contenttype->upload_content($this->make_stored_file('invalid.zip'));
    }

    /**
     * The visualizer returns a sandboxed iframe pointing at the package.
     */
    public function test_view_content_renders_iframe(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $context = \context_system::instance();
        $contenttype = new contenttype($context);
        $content = $contenttype->upload_content($this->make_stored_file('sample.elpx'));

        $html = $contenttype->get_view_content($content);
        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('sandbox=', $html);
        $this->assertStringContainsString(
            '/contenttype_exelearning/content/' . $content->get_id() . '/index.html',
            $html
        );
    }

    /**
     * Deleting content removes the extracted files.
     */
    public function test_delete_content_cleans_extracted_area(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $context = \context_system::instance();
        $contenttype = new contenttype($context);
        $content = $contenttype->upload_content($this->make_stored_file('sample.elpx'));
        $contentid = $content->get_id();

        $this->assertTrue(packager::has_extracted_index((int) $context->id, $contentid));
        $this->assertTrue($contenttype->delete_content($content));
        $this->assertFalse(packager::has_extracted_index((int) $context->id, $contentid));
    }
}
