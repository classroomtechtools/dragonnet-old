<?php
require_once '../../config.php';
require_once 'portables.php';
require_once '../../local/dnet_common/sharedlib.php';

require_login();

function as_teacher() {
    redirect(derive_plugin_path_from('roles/teachers.php') . '?' . http_build_query($_GET));
}

if (isloggedin()) {
    if (is_admin()) {
        if (isset($SESSION->dnet_activity_center_submode) && $SESSION->dnet_activity_center_submode == "becometeacher") {
            as_teacher();
        } else {
            redirect(derive_plugin_path_from('roles/admin.php')  . '?' . http_build_query($_GET));
        }
    } else if (is_teacher()) {
        as_teacher();
    }  else if (is_student()) {
        redirect(derive_plugin_path_from('roles/students.php') . '?' . http_build_query($_GET));
    }  else if (is_parent()) {
        redirect(derive_plugin_path_from('roles/parents.php') . '?' . http_build_query($_GET));
    }
} else {
    // death("You need to be logged in.");

    die("You need to be logged in"); // change this later, un-comment above and remove this line
}