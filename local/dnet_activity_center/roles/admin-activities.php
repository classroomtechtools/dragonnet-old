<?php
require_once($CFG->libdir.'/enrollib.php');


function add_activity_to_sesssion($activity_id) {
    global $SESSION;
    if (empty($SESSION->dnet_activity_center_activities)) {
        $SESSION->dnet_activity_center_activities = array();
    }

    if (!in_array($activity_id, $SESSION->dnet_activity_center_activities)) {
        $SESSION->dnet_activity_center_activities[] = $activity_id;
    }
}

switch ($mode) {
    case BROWSE:
        echo 'look up their enrollments';
        break;

    case CLEAR:
        unset($SESSION->dnet_activity_center_activities);
        sign("thumbs-up", "List cleared.", "Go to ".SELECT." to start building a new list.");
        break;

    case UNENROLLALL:
        include 'admin-activities-unenrollall.php';
        break;

    case SELECT:
        include 'admin-activities-select.php';
        break;
}