<?php

if ($ADMIN->fulltree) {
    $user_fields = array(
        'idnumber' => get_string('idnumber'),
        'email' => get_string('email'),
        'institution' => get_string('institution'),
        'department' => get_string('department')
    );

    $defaults = array('idnumber', 'email');

    $settings->add(new admin_setting_configmultiselect('gradeexport_csv/userfields',
        get_string('userfields', 'gradeexport_csv'),
        get_string('userfields_help', 'gradeexport_csv'),
        $defaults, $user_fields)
    );
}
