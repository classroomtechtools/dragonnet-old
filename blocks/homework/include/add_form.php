<?php

	$mode = $hwblock->mode();

	// Get all the user's classes
	$groups = $hwblock->getUsersGroups($USER->id);

	$selectedCourseID = '';
	$selectedGroupID = '';
	if (isset($editItem)) {
		$selectedGroupID = $editItem->groupid;
	} elseif ($_GET['groupid']) {
		$selectedGroupID = $_GET['groupid'];
	}
?>

<form class="form form-horizontal addHomeworkForm" role="form" method="post">

	<?php if (FORMACTION == 'edit') { ?>
	<input type="hidden" name="editid" value="<?=$editItem->id?>" />
	<?php } ?>

	<input type="hidden" name="action" value="<?=(FORMACTION == 'edit' ? 'saveedit' : 'save')?>" />

	<?php if ($mode == 'student') {

		if (FORMACTION == 'edit' && $editItem->private) {
			$private = 1;
		} else {
			$private = 0;
		}

	?>
	<div class="form-group">
		<label for="shared" class="col-md-3 control-label">Add For <i class="icon-user"></i></label>
		<div class="col-md-9">
			<div class="row addHomeworkPrivateToggle">
				<div class="col-md-6">
					<a class="btn btn-block <?=(!$private ? 'btn-primary active' : '')?>" data-value="0" href="#"><i class="icon-group"></i><br/>Everybody In The Class</a>
				</div>
				<div class="col-md-6">
					<a class="btn btn-block  <?=($private ? 'btn-primary active' : '')?>" data-value="1" href="#"><i class="icon-user"></i><br/>Just Me</a>
				</div>
			</div>
		</div>
	</div>
	<? } ?>
	<input type="hidden" name="private" value="<?=$private?>" />


	<div class="form-group">
		<label for="assigned" class="col-md-3 control-label">Class:</label>
		<div class="col-md-9">
			<select name="groupid" class="form-control" id="groupIDSelelect">
				<option value="">Please select...</option>
			<?php
			foreach ($groups as $courseID => $enrollment) {
				foreach ($enrollment['groups'] as $groupID => $group) {
					// TODO: Ability to pass courseid in the URL and select the first group in the course
					//(isset($courseid) && $course->id == $courseid ? 'selected': '')
					echo '<option value="' . $groupID . '" data-courseid="' . $courseID . '" ' . ($groupID == $selectedGroupID ? 'selected' : '') . '>';
						echo $enrollment['course']->fullname;
						if (trim($group['teacher'])) {
							echo ' (' . $group['teacher'] . '\'s Class)';
						} else {
							echo ' (' . $group['name'] . ')';
						}
						if ($groupID == $selectedGroupID) {
							$selectedCourseID = $courseID;
						}
					echo '</option>';
				}
			}
			if ($private) {
				echo '<option value="-1" ' . ($private && !$selectedGroupID ? 'selected' : '') . '>Other / Not Applicable</option>';
			}
			?>
			</select>
			<input type="hidden" name="courseid" value="<?=(FORMACTION == 'edit' ? $editItem->courseid : $selectedCourseID)?>" />

		</div>
	</div>

	<div class="form-group">
		<label for="description" class="col-md-3 control-label">Description:</i></label>
		<div class="col-md-9">
			<textarea name="description" class="form-control" placeholder="What is the homework?" rows="10"><?=(FORMACTION == 'edit' ? $editItem->description : '')?></textarea>
		</div>
	</div>

	<?php
	if ($mode == 'student') {
		// Visible date hidden for students
		$hideStartDate = true;
	} else {
		$hideStartDate = false;
	}
	?>
	<div class="form-group" <?=($hideStartDate ? 'style="display:none;"' : '')?>>
		<label for="assigned" class="col-md-3 control-label">Visible From:</label>
		<div class="col-md-9">
			<input type="text" id="startdate" name="startdate" class="form-control" value="<?=(FORMACTION == 'edit' ? $editItem->startdate : date('Y-m-d'))?>" />
			<p class="help-block">(Students won't see this on their page until this date)</p>
			<script>
			$(function(){
				$('#startdate').datepicker({
					firstDay: 1,
					minDate: 0,
					maxDate: "+1Y",
					showButtonPanel: true,
					dateFormat: 'yy-mm-dd',
					numberOfMonths: 2,
					onSelect: setPossibleDays,
					onClose: function(selectedDate) {
						$('#duedate').datepicker("option", "minDate", selectedDate);
					}
				});
				$(document).on('change', '#startdate', setPossibleDays);
			});
			</script>
		</div>
	</div>

	<div class="form-group">
		<label for="due" class="col-md-3 control-label">Due Date:</label>
		<div class="col-md-9">
			<input type="text" id="duedate" name="duedate" class="form-control" placeholder="Enter a date the assignment should be handed in by. (YYYY-MM-DD)" value="<?=(FORMACTION == 'edit' ? $editItem->duedate : '')?>" />
			<script>
			$(function(){
				$('#duedate').datepicker({
					firstDay: 1,
					minDate: 0,
					maxDate: "+1Y",
					showButtonPanel: true,
					dateFormat: 'yy-mm-dd',
					numberOfMonths: 2,
					onSelect: setPossibleDays
				});
				$(document).on('change', '#duedate', setPossibleDays);
			});
			</script>
		</div>
	</div>

	<div class="form-group" id="assignedDatesGroup" style="display:none;">
		<label for="assigned" class="col-md-3 control-label">Assigned Days:</label>
		<div class="col-md-9">
			<p class="help-block">Which days should students work on this task?</p>
			<input id="assigneddates" type="hidden" name="assigneddates" value="" />
			<ul id="possibleDays" class="row"></ul>
		</div>
	</div>

	<?php if (FORMACTION == 'edit') {
		// Show the assigned day toggle buttons on pageload if editing and existing item
		echo '<script> var homeworkFormAssignedDates = ' . json_encode($editItem->getAssignedDates()) . '; </script>';
	} ?>

	<div class="form-group">
		<label for="duration" class="col-md-3 control-label">Duration:</label>
		<div class="col-md-9">
			<input type="hidden" name="duration" class="form-control" value="" />
			<div id="duration-slider"></div>
			<span class="help-block" id="duration-help"></span>

			<script>
			$(function() {

				var durationLabels = {
					0: '0 minutes',
					15: '15 minutes',
					30: '30 minutes',
					45: '45 minutes',
					60: '1 hour',
					75: '1 hour 15 minutes',
					90: '1 hour 30 minutes',
					105: '1 hour 45 minutes',
					120: '2 hours',
					135: '2 hours 15 minutes',
					150: '2 hours 30 minutes',
					165: '2 hours 45 minutes',
					180: '3 or more hours',
				};

				function setDuration(mins) {
					//Set the label
					var dur = durationLabels[mins];
					if (mins < 180) {
						dur = 'up to ' + dur;
					}
					$('#duration-help').html('(This task should take <strong>' +  dur + '</strong> in total.)');
					$('input[name=duration]').val(mins);
				}

				<?php
					if (FORMACTION == 'edit') {
						#$duration = explode('-', $editItem->duration);
						$initialMinDuration = $duration;
						#$initialMaxDuration = $duration[1];
					} else {
						$initialMinDuration = 30;
						#$initialMaxDuration = 30;
					}
				?>

				$('#duration-slider').slider({
					//range: true,
					min: 0,
					step: 15,
					max: 180,
					values: [<?=$initialMinDuration?>],
					slide: function(event, ui) {
						setDuration(ui.values);
					}
				});

				setDuration(<?=$initialMinDuration?>); //, <?=$initialMaxDuration?>);
			});
			</script>
		</div>
	</div>

	<?php
	if ($mode == 'teacher' && FORMACTION == 'edit' && !$editItem->approved) {
		$label = 'Save and Approve';
	} elseif (FORMACTION == 'edit') {
		$label = 'Save Changes';
	} else {
		$label = 'Submit';
	}
	?>

	<div class="form-group">
		<div class="col-md-offset-3 col-md-5">
			<button type="submit" class="btn btn-lg"><?=$label?></button>
		</div>
	</div>

</form>