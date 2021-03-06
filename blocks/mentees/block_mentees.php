<?php

class block_mentees extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_mentees');
    }

    function applicable_formats() {
        return array('all' => true, 'tag' => false);
    }

    function specialization() {
        $this->title = isset($this->config->title) ? $this->config->title : get_string('newmenteesblock', 'block_mentees');
    }

    function instance_allow_multiple() {
        return true;
    }

    function get_content() {
        global $CFG, $USER, $DB, $SESSION;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();

        // get all the mentees, i.e. users you have a direct assignment to

        $usercontexts = get_users_children($USER->id);

        if (!empty($usercontexts)) {
            $this->content->text = '<ul>';
            foreach ($usercontexts as $usercontext) {
                $this->content->text .= '<li><a href="'.$CFG->wwwroot.'/user/view.php?id='.$usercontext->instanceid.'&amp;course='.SITEID.'">'.fullname($usercontext).'</a></li>';
            }
            $this->content->text .= '</ul>';
        }

        $this->content->footer = '';

        return $this->content;
    }

    /**
     * Returns true if the block can be docked.
     * The mentees block can only be docked if it has a non-empty title.
     * @return bool
     */
    public function instance_can_be_docked() {
        return parent::instance_can_be_docked() && isset($this->config->title) && !empty($this->config->title);
    }
}

