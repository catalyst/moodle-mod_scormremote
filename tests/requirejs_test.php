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

namespace mod_scormremote;

/**
 * Tests to ensure layers aren't included in requirejs.
 *
 * @package     mod_scormremote
 * @author      Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright   2024 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class requirejs_test extends \advanced_testcase {
    /** @var array Build files that should never exist. */
    const INVALID_BUILD_FILES = [
        'layer2.min.js',
        'layer3.min.js',
    ];

    /**
     * Test that invalid build files aren't included in requirejs.
     * Layers aren't proper AMD files, but they're hardcoded into packages so they can't be moved.
     * Build files created based on these will be invalid and can break requirejs MDL-79465.
     */
    public function test_build_files(): void {
        global $CFG;

        foreach (self::INVALID_BUILD_FILES as $file) {
            $path = "$CFG->dirroot/mod/scormremote/amd/build/$file";

            // Backwards compatibility with older PHPUnit versions.
            if (method_exists($this, 'assertFileDoesNotExist')) {
                $this->assertFileDoesNotExist($path);
                $this->assertFileDoesNotExist($path . '.map');
            } else {
                $this->assertFileNotExists($path);
                $this->assertFileNotExists($path . '.map');
            }

        }
    }
}
