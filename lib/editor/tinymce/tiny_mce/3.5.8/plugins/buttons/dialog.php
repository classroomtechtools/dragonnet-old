<!DOCTYPE html>
<html lang="en">
<head>
	<title>Add Buttons</title>
	<link rel="stylesheet" href="css/dialog.css" type="text/css" />
	<script>
		var fontawesomeIcons = <?php include '../../../../../../../font-awesome-icons/icons.json'; ?>;
	</script>
	<script type="text/javascript" src="../../tiny_mce_popup.js"></script>
	<script type="text/javascript" src="js/dialog.js"></script>
	<link rel="stylesheet" href="/font-awesome/css/font-awesome.min.css" />
</head>
<body>

<form action="#">
	<ul id="buttonList"></ul>

	<p><i class="icon-info-sign"></i> A full list of the icons available <a href="/icons.php" target="_blank">can be found here</a>.</p>

	<div class="mceActionPanel">
		<input type="button" id="insert" name="insert" value="Add Another" onclick="ButtonsDialog.addRow();" />
		<input type="button" id="insert" name="insert" value="Finished" onclick="ButtonsDialog.insert();" />
		<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="ButtonsDialog.cancel();" />
	</div>
</form>

</body>
</html>
