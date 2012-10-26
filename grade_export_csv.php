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

require_once($CFG->dirroot.'/grade/export/lib.php');

class grade_export_csv extends grade_export {

    public $plugin = 'csv';

    public $separator; // default separator

    public function grade_export_csv($course, $groupid=0, $itemlist='', $export_feedback=false, $updatedgradesonly = false, $displaytype = GRADE_DISPLAY_TYPE_REAL, $decimalpoints = 2, $separator='comma') {
        $this->grade_export($course, $groupid, $itemlist, $export_feedback, $updatedgradesonly, $displaytype, $decimalpoints);
        $this->separator = $separator;
    }


    public function get_export_params() {
        $params = parent::get_export_params();
        $params['separator'] = $this->separator;
        foreach($this->userfields as $key => $field) {
            $params[$key] = 1;
        }
        return $params;
    }

    public function process_form($data) {
        $this->userfields = array(
            'firstname' => get_string('firstname'),
            'lastname' => get_string('lastname')
        );

        $fields = array(
            'idnumber' => get_string('idnumber'),
            'email' => get_string('email'),
            'institution' => get_string('institution'),
            'department' => get_string('department')
        );

        foreach ($fields as $key => $field) {
            if (isset($data->$key)) {
                $this->userfields[$key] = $field;
            }
        }
        parent::process_form($data);
        print_r($data);
    }

    public function inject_js() {
        global $PAGE;

        $module = array(
            'name' => 'gradeexport_csv',
            'fullpath' => '/grade/export/csv/module.js',
            'requires' => array('base', 'dom')
        );

        $table_indexes = array(
            'firstname',
            'lastname',
            'idnumber',
            'institution',
            'department',
            'email'
        );

        $cancel_indexes = array();
        foreach ($table_indexes as $index => $key) {
            if (isset($this->userfields[$key])) {
                continue;
            }
            $cancel_indexes[$index] = 1;
        }

        $args = array('indexes' => $cancel_indexes);

        $PAGE->requires->js_init_call('M.gradeexport_csv.init', $args, false, $module);
    }

    public function print_grades() {
        global $CFG;

        $export_tracking = $this->track_exports();

        $strgrades = get_string('grades');

        $separator = ",";

        /// Print header to force download
        if (strpos($CFG->wwwroot, 'https://') === 0) { //https sites - watch out for IE! KB812935 and KB316431
            @header('Cache-Control: max-age=10');
            @header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
            @header('Pragma: ');
        } else { //normal http - prevent caching at all cost
            @header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
            @header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
            @header('Pragma: no-cache');
        }
        header("Content-Type: application/download\n");
        $downloadfilename = clean_filename("{$this->course->shortname} $strgrades");
        header("Content-Disposition: attachment; filename=\"$downloadfilename.csv\"");

/// Print names of all the fields
        echo implode($separator, array_values($this->userfields));

        foreach ($this->columns as $grade_item) {
            echo $separator.$this->format_column_name($grade_item);

            /// add a feedback column
            if ($this->export_feedback) {
                echo $separator.$this->format_column_name($grade_item, true);
            }
        }
        echo "\n";

/// Print all the lines of data.
        $geub = new grade_export_update_buffer();
        $gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
        $gui->init();

        $fields = array_keys($this->userfields);

        while ($userdata = $gui->next_user()) {

            $user = $userdata->user;

            $mapper = function($field) use ($user) { return $user->$field; };
            echo implode($separator, array_map($mapper, $fields));

            foreach ($userdata->grades as $itemid => $grade) {
                if ($export_tracking) {
                    $status = $geub->track($grade);
                }

                echo $separator.$this->format_grade($grade);

                if ($this->export_feedback) {
                    echo $separator.$this->format_feedback($userdata->feedbacks[$itemid]);
                }
            }
            echo "\n";
        }
        $gui->close();
        $geub->close();

        exit;
    }
}


