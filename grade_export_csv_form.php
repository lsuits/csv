<?php

class grade_export_csv_form extends grade_export_form {
    function definition() {
        $mform =& $this->_form;

        $fields = array(
            'idnumber' => get_string('idnumber'),
            'email' => get_string('email'),
            'institution' => get_string('institution'),
            'department' => get_string('department')
        );

        $allowed = explode(',', get_config('gradeexport_csv', 'userfields'));

        if (!empty($allowed)) {
            $mform->addElement('header', 'userfields_header',
                get_string('userfields', 'gradeexport_csv'));

            foreach ($fields as $key => $field) {
                if (in_array($key, $allowed)) {
                    $mform->addElement('checkbox', $key, $field, '');
                    $mform->setDefault($key, 'checked');
                }
            }
        }

        parent::definition();
    }
}
