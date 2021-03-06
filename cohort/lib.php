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

/**
 * Cohort related management functions, this file needs to be included manually.
 *
 * @package    core_cohort
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $COHORT_CACHE;
$COHORT_CACHE = cache::make_from_params(cache_store::MODE_APPLICATION, 'cohorts', 'cohorts');

//Remove all items from the cohort cache
function cohort_clear_cache()
{
	global $COHORT_CACHE;
	$COHORT_CACHE->purge();
}

/**
 * Add new cohort.
 *
 * @param  stdClass $cohort
 * @return int new cohort id
 */
function cohort_add_cohort($cohort) {
    global $DB;

    if (!isset($cohort->name)) {
        throw new coding_exception('Missing cohort name in cohort_add_cohort().');
    }
    if (!isset($cohort->idnumber)) {
        $cohort->idnumber = NULL;
    }
    if (!isset($cohort->description)) {
        $cohort->description = '';
    }
    if (!isset($cohort->descriptionformat)) {
        $cohort->descriptionformat = FORMAT_HTML;
    }
    if (empty($cohort->component)) {
        $cohort->component = '';
    }
    if (!isset($cohort->timecreated)) {
        $cohort->timecreated = time();
    }
    if (!isset($cohort->timemodified)) {
        $cohort->timemodified = $cohort->timecreated;
    }

    $cohort->id = $DB->insert_record('cohort', $cohort);
	cohort_clear_cache();

    events_trigger('cohort_added', $cohort);

    return $cohort->id;
}

/**
 * Update existing cohort.
 * @param  stdClass $cohort
 * @return void
 */
function cohort_update_cohort($cohort) {
    global $DB;
    if (property_exists($cohort, 'component') and empty($cohort->component)) {
        // prevent NULLs
        $cohort->component = '';
    }
    $cohort->timemodified = time();
    $DB->update_record('cohort', $cohort);
	cohort_clear_cache();

    events_trigger('cohort_updated', $cohort);
}

/**
 * Delete cohort.
 * @param  stdClass $cohort
 * @return void
 */
function cohort_delete_cohort($cohort) {
    global $DB;

    if ($cohort->component) {
        // TODO: add component delete callback
    }

    $DB->delete_records('cohort_members', array('cohortid'=>$cohort->id));
    $DB->delete_records('cohort', array('id'=>$cohort->id));
	cohort_clear_cache();

    events_trigger('cohort_deleted', $cohort);
}

/**
 * Somehow deal with cohorts when deleting course category,
 * we can not just delete them because they might be used in enrol
 * plugins or referenced in external systems.
 * @param  stdClass|coursecat $category
 * @return void
 */
function cohort_delete_category($category) {
    global $DB;
    // TODO: make sure that cohorts are really, really not used anywhere and delete, for now just move to parent or system context

    $oldcontext = context_coursecat::instance($category->id);

    if ($category->parent and $parent = $DB->get_record('course_categories', array('id'=>$category->parent))) {
        $parentcontext = context_coursecat::instance($parent->id);
        $sql = "UPDATE {cohort} SET contextid = :newcontext WHERE contextid = :oldcontext";
        $params = array('oldcontext'=>$oldcontext->id, 'newcontext'=>$parentcontext->id);
    } else {
        $syscontext = context_system::instance();
        $sql = "UPDATE {cohort} SET contextid = :newcontext WHERE contextid = :oldcontext";
        $params = array('oldcontext'=>$oldcontext->id, 'newcontext'=>$syscontext->id);
    }

    $DB->execute($sql, $params);

	cohort_clear_cache();
}

/**
 * Add cohort member
 * @param  int $cohortid
 * @param  int $userid
 * @return void
 */
function cohort_add_member($cohortid, $userid) {
    global $DB, $COHORT_CACHE;
    if ($DB->record_exists('cohort_members', array('cohortid'=>$cohortid, 'userid'=>$userid))) {
        // No duplicates!
        return;
    }
    $record = new stdClass();
    $record->cohortid  = $cohortid;
    $record->userid    = $userid;
    $record->timeadded = time();
    $DB->insert_record('cohort_members', $record);

	#cohort_clear_cache(); //Clear everything
	$COHORT_CACHE->delete("cohort_is_member,$cohortid,$userid"); //Just clear cohort_is_member

    events_trigger('cohort_member_added', (object)array('cohortid'=>$cohortid, 'userid'=>$userid));
}

/**
 * Remove cohort member
 * @param  int $cohortid
 * @param  int $userid
 * @return void
 */
function cohort_remove_member($cohortid, $userid) {
    global $DB, $COHORT_CACHE;
    $DB->delete_records('cohort_members', array('cohortid'=>$cohortid, 'userid'=>$userid));

	#cohort_clear_cache(); //Clear everything
	$COHORT_CACHE->delete("cohort_is_member,$cohortid,$userid"); //Just clear cohort_is_member

    events_trigger('cohort_member_removed', (object)array('cohortid'=>$cohortid, 'userid'=>$userid));
}

function cohort_count_members($cohortid) {
    return $DB->count_records('cohort_members', array('cohortid'=>$cohortid));
}


/**
 * Is this user a cohort member?
 * @param int $cohortid
 * @param int $userid
 * @return bool
 */
function cohort_is_member($cohortid, $userid) {
	if ( !$cohortid || !$userid ) { return false; }

	global $COHORT_CACHE;

    $cacheKey = "cohort_is_member,$cohortid,$userid";
    if ( $result = $COHORT_CACHE->get($cacheKey) )
    {
    	return $result == -1 ? false : true;
    }

    global $DB;

    $result = $DB->record_exists('cohort_members', array('cohortid'=>$cohortid, 'userid'=>$userid));

    $COHORT_CACHE->set($cacheKey, ($result?1:'-1') );
    return $result;
}

//Checks if a user is enrolled in a cohort (like cohort_is_member) but takes a cohort idnumber (e.g. students6) instead of 76
function cohort_is_member_by_idnumber($cohortidnumber, $userid) {

	$cohort_ids = cohorts_get_all_ids();
	if ( !$cohort_ids[$cohortidnumber] ) { return false; }

	return cohort_is_member($cohort_ids[$cohortidnumber], $userid);
}

/**
 * Returns a cohort the id for the cohort with the given idnumber
 * Note: idnumber is not the numerical ID
 * idnumber is the user friendly name (e.g. studentsALL or teachersALL)
 */
function cohort_get_id($cohortidnumber) {
    $cohort_ids = cohorts_get_all_ids();
    return $cohort_ids[$cohortidnumber];
}

/**
 * Returns list of cohorts from course parent contexts.
 *
 * Note: this function does not implement any capability checks,
 *       it means it may disclose existence of cohorts,
 *       make sure it is displayed to users with appropriate rights only.
 *
 * @param  stdClass $course
 * @param  bool $onlyenrolled true means include only cohorts with enrolled users
 * @return array of cohort names with number of enrolled users
 */
function cohort_get_visible_list($course, $onlyenrolled=true) {
    global $DB;

    $context = context_course::instance($course->id);
    list($esql, $params) = get_enrolled_sql($context);
    list($parentsql, $params2) = $DB->get_in_or_equal($context->get_parent_context_ids(), SQL_PARAMS_NAMED);
    $params = array_merge($params, $params2);

    if ($onlyenrolled) {
        $left = "";
        $having = "HAVING COUNT(u.id) > 0";
    } else {
        $left = "LEFT";
        $having = "";
    }

    $sql = "SELECT c.id, c.name, c.contextid, c.idnumber, COUNT(u.id) AS cnt
              FROM {cohort} c
        $left JOIN ({cohort_members} cm
                   JOIN ($esql) u ON u.id = cm.userid) ON cm.cohortid = c.id
             WHERE c.contextid $parentsql
          GROUP BY c.id, c.name, c.contextid, c.idnumber
           $having
          ORDER BY c.name, c.idnumber";

    $cohorts = $DB->get_records_sql($sql, $params);

    foreach ($cohorts as $cid=>$cohort) {
        $cohorts[$cid] = format_string($cohort->name, true, array('context'=>$cohort->contextid));
        if ($cohort->cnt) {
            $cohorts[$cid] .= ' (' . $cohort->cnt . ')';
        }
    }

    return $cohorts;
}

/**
 * Get all the cohorts defined in given context.
 *
 * @param int $contextid
 * @param int $page number of the current page
 * @param int $perpage items per page
 * @param string $search search string
 * @return array    Array(totalcohorts => int, cohorts => array, allcohorts => int)
 */
function cohort_get_cohorts($contextid, $page = 0, $perpage = 25, $search = '') {
    global $DB;

    // Add some additional sensible conditions
    $tests = array('contextid = ?');
    $params = array($contextid);

    if (!empty($search)) {
        $conditions = array('name', 'idnumber', 'description');
        $searchparam = '%' . $DB->sql_like_escape($search) . '%';
        foreach ($conditions as $key=>$condition) {
            $conditions[$key] = $DB->sql_like($condition, "?", false);
            $params[] = $searchparam;
        }
        $tests[] = '(' . implode(' OR ', $conditions) . ')';
    }
    $wherecondition = implode(' AND ', $tests);

    $fields = "SELECT *";
    $countfields = "SELECT COUNT(1)";
    $sql = " FROM {cohort}
             WHERE $wherecondition";
    $order = " ORDER BY name ASC, idnumber ASC";
    $allcohorts = $DB->count_records('cohort', array('contextid'=>$contextid));
    $totalcohorts = $DB->count_records_sql($countfields . $sql, $params);
    $cohorts = $DB->get_records_sql($fields . $sql . $order, $params, $page*$perpage, $perpage);

    return array('totalcohorts' => $totalcohorts, 'cohorts' => $cohorts, 'allcohorts'=>$allcohorts);
}

function cohorts_get_all_ids()
{

	global $COHORT_CACHE;
	//$COHORT_CACHE->purge();

	if ( $cohort_ids = $COHORT_CACHE->get('all_ids') )
	{
	  return $cohort_ids;
	}

	global $DB;

	$cohort_ids = array();

	//Get the id and idnumber for all cohorts
	$result = $DB->get_records('cohort',null,'id,idnumber');
	foreach( $result as $cohort )
	{
		$cohort_ids[ $cohort->idnumber ] = $cohort->id;
	}

	$COHORT_CACHE->set('all_ids',$cohort_ids);

	return $cohort_ids;
}

