<?php

/**
 * Saves changes to an activity / creates a new activity
 */

require '../../../config.php';

require '../ActivityCenter/ActivityCenter.php';
$activityCenter = new \SSIS\ActivityCenter\ActivityCenter();

$action = required_param('action', PARAM_RAW);

switch ($action) {

	case 'add':

		if ($activityCenter->getCurrentMode() != 'admin') {
			die("You need to be in student or teacher mode to add homework.");
		}

		$name = required_param('name', PARAM_RAW);
		$categoryID = required_param('categoryid', PARAM_INT);
		$summary = required_param('summary', PARAM_RAW);
		$season = required_param('season', PARAM_RAW);
		$supervisors = optional_param('supervisors', 0, PARAM_INT);
		$maxEnrolledUsers = optional_param('maxEnrolledUsers', 0, PARAM_INT);
		$parentsCanEnrol = optional_param('parentsCanEnrol', 1, PARAM_INT);

		// Create the new course

		$seasonString = 'S' . implode(',S', $season);

		 if ($seasonString === 'S1,S2,S3') {
                $seasonString = 'ALL';
            }

		$shortname = strtoupper($name);
		$shortname = str_replace(' ', '', $shortname);

		$courseData = new \stdClass();
		$courseData->fullname = '(' . $seasonString . ') ' . $name;
		$courseData->shortname = $shortname;
		$courseData->summary = $summary;
		$courseData->format = 'onetopic';
		$courseData->category = $categoryID;

		require_once $CFG->dirroot . '/course/lib.php';
		$course = create_course($courseData);

		if (!$course) {
			die("Unable to create the course.");
		}

		// Save the season and supervisors
		require_once $CFG->libdir . '/ssismetadata.php';
		$metadata = new \ssismetadata();

		$metadata->setCourseField($course->id, 'activitysupervisors', $supervisors);
		$metadata->setCourseField($course->id, 'activityseason', implode(',', $season));

		// Add the required enrolment methods...
		$activityCenter->data->addSelfEnrolmentToActivityCourse($course, $maxEnrolledUsers, $parentsCanEnrol);

		// Add manager cohort sync
		$activityCenter->data->addActivitiesHeadCohortSync($course);

		redirect($activityCenter->getPath() . 'view.php?refreshawesomebar&view=activitycreated&id=' . $course->id);

		break;
}
