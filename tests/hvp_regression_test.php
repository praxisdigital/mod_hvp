<?php
// This file is part of Moodle - https://moodle.org/
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

namespace mod_hvp;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Regression tests for editor icons and missing activity content.
 *
 * @package    mod_hvp
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(editor_ajax::class)]
#[CoversClass(view_assets::class)]
final class hvp_regression_test extends \advanced_testcase {
    /** Tests that the editor uses the installed library's major.minor folder for its icon. */
    public function test_editor_lists_local_library_icon(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $DB->insert_record('hvp_libraries', (object) [
            'machine_name' => 'H5P.RegressionTest',
            'title' => 'HVP regression test',
            'major_version' => 1,
            'minor_version' => 2,
            'patch_version' => 3,
            'runnable' => 1,
            'embed_types' => 'div',
            'semantics' => '[]',
            'has_icon' => 1,
        ]);

        $previousget = $_GET;
        try {
            $_GET['contextId'] = (string) \context_course::instance($course->id)->id;
            $libraries = framework::instance('editor')->getLatestGlobalLibrariesData();
        } finally {
            $_GET = $previousget;
        }
        $expected = framework::instance('interface')->getLibraryFileUrl('H5P.RegressionTest-1.2', 'icon.svg');
        $icons = array_column($libraries, 'icon', 'machineName');

        $this->assertArrayHasKey('H5P.RegressionTest', $icons);
        $this->assertSame($expected, $icons['H5P.RegressionTest']);
    }

    /** Tests that a missing activity is rejected before assets are loaded. */
    public function test_view_assets_rejects_missing_activity(): void {
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('invalidhvp', 'mod_hvp'));

        new view_assets((object) ['instance' => 0], (object) []);
    }

    /** Tests that an activity with a missing main library is rejected before assets are loaded. */
    public function test_view_assets_rejects_missing_main_library(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $instance = $DB->insert_record('hvp', (object) [
            'course' => $course->id,
            'name' => 'Missing H5P library',
            'json_content' => '{}',
            'embed_type' => 'div',
            'main_library_id' => 0,
            'slug' => 'missing-h5p-library',
        ]);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('invalidhvp', 'mod_hvp'));

        new view_assets((object) ['instance' => $instance], $course);
    }
}
