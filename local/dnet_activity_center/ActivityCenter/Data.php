<?php

/**
 * Class for managing Activity data: getting, updating, etc.
 */

namespace SSIS\ActivityCenter;

class Data
{
	const MANAGER_ROLE_ID = 1;
	private $activityCenter;

	public function __construct(ActivityCenter $activityCenter)
	{
		$this->activityCenter = $activityCenter;
	}

	/**
	 * Return all activities the given user ID is supervising
	 */
	public function getActivitiesManaged($courseID = false, $userID = false)
	{
		global $DB;

		$sql = "SELECT
			CONCAT(crs.id, '-', usr.id), -- unique id so moodle keeps all the results
			usr.id as userid,
			usr.username,
			usr.firstname,
			usr.lastname,
			crs.id as id,
			crs.fullname
		from {user_enrolments} ue
		join {user} usr on usr.id = ue.userid
		join {enrol} enrol on enrol.id = ue.enrolid
		join {course} crs on crs.id = enrol.courseid
		join {context} ctx on (ctx.instanceid = enrol.courseid and ctx.contextlevel = 50) --contextlevel 50 = a course
		join {role_assignments} ra on ra.contextid = ctx.id and ra.userid = usr.id
		where
			ctx.path like '/1/3/%' --contextid of activities category
			and
			ra.roleid = " . self::MANAGER_ROLE_ID . " --manager roleid
			and
			enrol.enrol = 'manual' --exclude cohort sync managers
		";

		$params = array();

		if ($courseID) {
			$sql .= ' AND crs.id = ?';
			$params[] = $courseID;
		}

		if ($userID) {
			$sql .= ' AND usr.id = ?';
			$params[] = $userID;
		}

		return $DB->get_records_sql($sql, $params);
	}

	public function getManualEnrolmentMethodForActivity($courseID)
	{
		global $DB;
		return $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $courseID));
	}

	/**
	 * Returns an array of activity courses
	 * @param search Search for activities with this string in the name
	 * @param userID Return activities the given user ID is enroled as a Manager in
	 */
	public function getActivities($search = false, $userID = false)
	{
		global $DB;

		$sql = "SELECT
			crs.fullname, crs.id
		FROM
			{course} crs
		JOIN
			{course_categories} cat ON cat.id = crs.category
		WHERE
			cat.path like ?";

		$params = array();
		$params[] = "/1/%";

		if ($search) {
			$sql .= " AND REPLACE(LOWER(fullname), ' ', '') LIKE ?";
			$params[] = '%' . $term . '%';
		}

		$activities = $DB->get_records_sql($sql, $params);

		return $activities;
	}

	/**
	 * Returns the user(s) enrolled as Manager in the given courseID
	 * (But not the cohort sync ones!)
	 */
	public function getActivityLeaders($courseID)
	{
		global $DB;

		$context = \context_course::instance($courseID);
		$users = get_enrolled_users($context, 'enrol/manual:enrol');

		print_object($users);
	}

}