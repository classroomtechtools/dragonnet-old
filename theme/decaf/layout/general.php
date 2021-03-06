<?php
if (!empty($CFG->themedir) and file_exists("$CFG->themedir/decaf")) {
	require_once ($CFG->themedir."/decaf/lib.php");
} else {
	require_once ($CFG->dirroot."/theme/decaf/lib.php");
}

$hasheading = ($PAGE->heading);
$hasnavbar = ( $PAGE->bodyid != 'page-site-index' && empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar());
$hasfooter = (empty($PAGE->layout_options['nofooter']));

// $PAGE->blocks->region_has_content('region_name') doesn't work as we do some sneaky stuff
// to hide nav and/or settings blocks if requested
$blocks_side_pre = trim($OUTPUT->blocks_for_region('side-pre'));
$hassidepre = strlen($blocks_side_pre);
$blocks_side_post = trim($OUTPUT->blocks_for_region('side-post'));
$hassidepost = strlen($blocks_side_post);

if (empty($PAGE->layout_options['noawesomebar'])) {
	$topsettings = $this->page->get_renderer('theme_decaf','topsettings');
	decaf_initialise_awesomebar($PAGE);
	$awesome_nav = $topsettings->navigation_tree($this->page->navigation);
	$awesome_settings = $topsettings->settings_tree($this->page->settingsnav);
}

$custommenu = $OUTPUT->render_awesomebar(); //comes from decaf/renderers.php render_custom_menu()

$hascustommenu = (empty($PAGE->layout_options['nocustommenu']) && !empty($custommenu));

$courseheader = $coursecontentheader = $coursecontentfooter = $coursefooter = '';
if (method_exists($OUTPUT, 'course_header') && empty($PAGE->layout_options['nocourseheaderfooter'])) {
	$courseheader = $OUTPUT->course_header();
	$coursecontentheader = $OUTPUT->course_content_header();
	if (empty($PAGE->layout_options['nocoursefooter'])) {
		$coursecontentfooter = $OUTPUT->course_content_footer();
		$coursefooter = $OUTPUT->course_footer();
	}
}

$bodyclasses = array();

if(!empty($PAGE->theme->settings->useeditbuttons) && $PAGE->user_allowed_editing()) {
	decaf_initialise_editbuttons($PAGE);
	$bodyclasses[] = 'decaf_with_edit_buttons';
}

if ($hassidepre && !$hassidepost) {
	$bodyclasses[] = 'side-pre-only';
} else if ($hassidepost && !$hassidepre) {
	$bodyclasses[] = 'side-post-only';
} else if (!$hassidepost && !$hassidepre) {
	$bodyclasses[] = 'content-only';
}

if(!empty($PAGE->theme->settings->persistentedit) && $PAGE->user_allowed_editing()) {
	if(property_exists($USER, 'editing') && $USER->editing) {
		$OUTPUT->set_really_editing(true);
	}
	$USER->editing = 1;
	$bodyclasses[] = 'decaf_persistent_edit';
}

if (!empty($PAGE->theme->settings->footnote)) {
	$footnote = $PAGE->theme->settings->footnote;
} else {
	$footnote = '<!-- There was no custom footnote set -->';
}

if (check_browser_version("MSIE", "0")) {
	header('X-UA-Compatible: IE=edge');
}
echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes() ?>>
<head>
    <title><?php echo ltrim($PAGE->title,': ') ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->pix_url('favicon', 'theme')?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
</head>

<body id="<?php p($PAGE->bodyid) ?>" class="<?php p($PAGE->bodyclasses.' '.join(' ', $bodyclasses)) ?>">
<?php echo $OUTPUT->standard_top_of_body_html();
if (empty($PAGE->layout_options['noawesomebar'])) { ?>
	<div id="awesomebar" class="decaf-awesome-bar">
		<?php
			if (
				$this->page->pagelayout != 'maintenance' // Don't show awesomebar if site is being upgraded
				&&
				!(get_user_preferences('auth_forcepasswordchange') && !session_is_loggedinas()) // Don't show it when forcibly changing password either
			  )
			  {
				if ($hascustommenu && !empty($PAGE->theme->settings->custommenuinawesomebar) && empty($PAGE->theme->settings->custommenuafterawesomebar))
				{
					echo $custommenu;
				}

				//Course administration menu
				echo $awesome_settings;

				if ($hascustommenu && !empty($PAGE->theme->settings->custommenuinawesomebar) && !empty($PAGE->theme->settings->custommenuafterawesomebar))
				{
					echo $custommenu;
				}

				echo $topsettings->settings_search_box();
			}
		?>
	</div>
<?php } ?>

<div id="page">

<?php if ($hasheading || $hasnavbar) { ?>

   <?php
	   	$headerPhotos = array(
	   		'header-0.jpg',
	   		'header-1.jpg',
	   		'header-2.jpg',
	   		'header-3.jpg',
	   		'header-4.jpg',
	   	);
	   	global $SESSION;
	   	if ( isset($_GET['header']) ) { $headerPhoto = $_GET['header']; }
	   	else if ( isset($SESSION) && isset($SESSION->headerPhoto) ) { $headerPhoto = $SESSION->headerPhoto; }
	   	else { $headerPhoto = rand(0,4); }
		$headerBg = '/theme/decaf/pix/'.$headerPhotos[$headerPhoto];
   ?>

    <div id="page-header" style="background-image:url(<?php echo $headerBg; ?>);">
    	<div id="page-header-gradient"></div>
		<div id="page-header-wrapper">

	        <?php if ($hasheading) { ?>
		    	<h1 class="headermain"><?php echo $PAGE->heading ?></h1>
    		    <div class="headermenu">
        			<?php
        			if (!empty($PAGE->theme->settings->showuserpicture)) {
        				if (isloggedin())
        				{
        					echo ''.$OUTPUT->user_picture($USER, array('size'=>55)).'';
        				}
        				else {
        					?>
						<img class="userpicture" src="<?php echo $OUTPUT->pix_url('image', 'theme'); ?>" />
						<?php
        				}
        			}
			 //echo $OUTPUT->login_info();
    	        echo $OUTPUT->lang_menu();
	        	echo $PAGE->headingmenu;

				if ($PAGE->course->id != 1 && isloggedin()) {   # if not frontpage

					//Search URL
					$searchURL = new moodle_url('/blocks/search/');

					//Begin form
					echo html_writer::start_tag('form', array(
						'id' => 'headerSearchForm',
						'action' => $searchURL,
						'method' => 'get'
					));

						if (empty($PAGE->course->id) || $PAGE->course->id == 1395) {

							//Frontpage or "Frontpage" course
							$placeholder = 'Search All of DragonNet';

						} else {

							//Search within a course
							$placeholder = 'Search Within This Course';

							echo html_writer::empty_tag('input', array(
								'type' => 'hidden',
								'name' => 'courseID',
								'value' => $PAGE->course->id
							));

						}

						//Input box
						echo html_writer::empty_tag('input', array(
							'type' => 'text',
							'size' => $inputsize,
							'name' => 'q',
							'placeholder' => s($placeholder)
						));

						//Search button
						echo html_writer::tag('button', 'Search');

					//End form
					echo html_writer::end_tag('form');
				}
        			?>
	        	</div>
	        <?php } ?>



	    </div>
    </div>

    <?php if ($hascustommenu && empty($PAGE->theme->settings->custommenuinawesomebar)) { ?>
      <div id="custommenu" class="decaf-awesome-bar"><?php echo $custommenu; ?></div>
 	<?php } ?>

    <?php if (!empty($courseheader)) { ?>
        <div id="course-header"><?php echo $courseheader; ?></div>
    <?php } ?>

    <?php if ($hasnavbar) { ?>
	    <div class="navbar clearfix">
    	    <div class="breadcrumb"><?php echo $OUTPUT->navbar(); ?></div>
            <div class="navbutton"> <?php echo $PAGE->button; ?></div>
        </div>
    <?php } ?>

<?php } ?>
<!-- END OF HEADER -->
<div id="page-content-wrapper" class="clearfix">
	<div id="page-content">

		<div id="centerCol" class="<?php if (!$hassidepost&&!$hassidepre) { echo 'fullWidth'; } ?>">
			<div class="region-content">
				<?php
					echo $coursecontentheader;
					echo method_exists($OUTPUT, "main_content")?$OUTPUT->main_content():core_renderer::MAIN_CONTENT_TOKEN;
					echo $coursecontentfooter;
				?>
			</div>
		</div>

		<?php if ( $hassidepre || $hassidepost ) { ?>
		<div id="side-post" class="block-region">
			<div class="region-content">
				 <?php
					echo $blocks_side_pre;
					echo $blocks_side_post;
				?>
			</div>
		</div>
		<?php } ?>

	</div>
</div>

<!-- START OF FOOTER -->
    <?php if (!empty($coursefooter)) { ?>
        <div id="course-footer"><?php echo $coursefooter; ?></div>
    <?php } ?>
    <?php if ($hasfooter) { ?>
    <div id="page-footer" class="clearfix">
		<?php /*<p><a href="/togglesnow.php"><i class="icon-cloud"></i> Click here to turn the snow <?=(empty($_COOKIE['nosnow'])?'off':'on')?></a></p>*/ ?>
    	<p><a href="#"><i class="icon-arrow-up"></i> Go back to the top of the page</a></p>
		<p class="footnote"><?php echo $footnote; ?></p>
		<p><?php echo page_doc_link(get_string('moodledocslink')) ?></p>
		<?php
	   //echo $OUTPUT->login_info();
	   //echo $OUTPUT->home_link();
		echo $OUTPUT->standard_footer_html();
		?>
	</div>
	<?php } ?>
</div>
<?php echo $OUTPUT->standard_end_of_body_html() ?>
<div id="back-to-top">
	<a class="arrow" href="#">▲</a>
	<a class="text" href="#">Back to Top</a>
</div>
<script type="text/javascript">
YUI().use('node', function(Y) {
	window.thisisy = Y;
	Y.one(window).on('scroll', function(e) {
		var node = Y.one('#back-to-top');

		if (Y.one('window').get('docScrollY') > Y.one('#page-content-wrapper').getY()) {
			node.setStyle('display', 'block');
		} else {
			node.setStyle('display', 'none');
		}
	});

});
</script>
</body>
</html>
