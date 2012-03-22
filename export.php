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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'grade_export_csv.php';

$id                = required_param('id', PARAM_INT); // course id
$groupid           = optional_param('groupid', 0, PARAM_INT);
$itemids           = required_param('itemids', PARAM_RAW);
$export_feedback   = optional_param('export_feedback', 0, PARAM_BOOL);
$separator         = optional_param('separator', 'comma', PARAM_ALPHA);
$updatedgradesonly = optional_param('updatedgradesonly', false, PARAM_BOOL);
$displaytype       = optional_param('displaytype', $CFG->grade_export_displaytype, PARAM_INT);
$decimalpoints     = optional_param('decimalpoints', $CFG->grade_export_decimalpoints, PARAM_INT);

$fields = array('idnumber', 'email', 'institution', 'department');
$allowed = explode(',',get_config('gradeexport_csv', 'userfields'));

$data = new stdClass;
foreach ($fields as $field) {
    if (in_array($field, $allowed) and optional_param($field, null, PARAM_INT)) {
        $data->$field = 1;
    }
}

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/csv:view', $context);

if (groups_get_course_groupmode($COURSE) == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
    if (!groups_is_member($groupid, $USER->id)) {
        print_error('cannotaccessgroup', 'grades');
    }
}

// print all the exported data here
$export = new grade_export_csv($course, $groupid, $itemids, $export_feedback, $updatedgradesonly, $displaytype, $decimalpoints, $separator);

$export->process_form($data);

$export->print_grades();
