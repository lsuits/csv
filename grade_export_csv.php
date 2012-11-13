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

public function csv_display_preview($require_user_idnumber=false) {
    global $OUTPUT;
    echo $OUTPUT->heading(get_string('previewrows', 'grades'));

    echo '<table>';
    echo '<tr>';
    echo '<th>'.get_string("firstname")."</th>".
          '<th>'.get_string("lastname")."</th>".
          '<th>'.get_string("idnumber")."</th>".
          '<th>'.get_string("department")."</th>".
          '<th>'.get_string("email")."</th>";
    foreach ($this->columns as $grade_item) {
        echo '<th>'.$this->format_column_name($grade_item).'</th>';

        /// add a column_feedback column
        if ($this->export_feedback) {
            echo '<th>'.$this->format_column_name($grade_item, true).'</th>';
        }
    }
    echo '</tr>';
    /// Print all the lines of data.

    $i = 0;
    $gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
    $gui->require_active_enrolment($this->onlyactive);
    $gui->init();
    while ($userdata = $gui->next_user()) {
        // number of preview rows
        if ($this->previewrows and $this->previewrows <= $i) {
            break;
        }
        $user = $userdata->user;
        if ($require_user_idnumber and empty($user->idnumber)) {
            // some exports require user idnumber so we can match up students when importing the data
            continue;
        }

        $gradeupdated = false; // if no grade is update at all for this user, do not display this row
        $rowstr = '';
        foreach ($this->columns as $itemid=>$unused) {
            $gradetxt = $this->format_grade($userdata->grades[$itemid]);

            // get the status of this grade, and put it through track to get the status
            $g = new grade_export_update_buffer();
            $grade_grade = new grade_grade(array('itemid'=>$itemid, 'userid'=>$user->id));
            $status = $g->track($grade_grade);

            if ($this->updatedgradesonly && ($status == 'nochange' || $status == 'unknown')) {
                $rowstr .= '<td>'.get_string('unchangedgrade', 'grades').'</td>';
            } else {
                $rowstr .= "<td>$gradetxt</td>";
                $gradeupdated = true;
            }

            if ($this->export_feedback) {
                $rowstr .=  '<td>'.$this->format_feedback($userdata->feedbacks[$itemid]).'</td>';
            }
        }

        // if we are requesting updated grades only, we are not interested in this user at all
        if (!$gradeupdated && $this->updatedgradesonly) {
            continue;
        }

        echo '<tr>';
        echo "<td>$user->firstname</td><td>$user->lastname</td><td>$user->idnumber</td><td>$user->department</td><td>$user->email</td>";
        echo $rowstr;
        echo "</tr>";

        $i++; // increment the counter
    }
    echo '</table>';
    $gui->close();
}

    public $plugin = 'csv';

    /**
     * To be implemented by child classes
     */
    public function print_grades() {
        global $CFG;
        $this->userfields = array('firstname','lastname','idnumber', 'department','email');

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


        $fields = $this->userfields;

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
