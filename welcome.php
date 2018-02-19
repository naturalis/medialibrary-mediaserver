<?php
 
	$resultSet = $_REQUEST['RESULT_SET'];
	$resultSetSize = $_REQUEST['RESULT_SET_SIZE'];
	$producerArray = $_REQUEST['PRODUCER_ARRAY'];
	
	$today = date('Y-m-d');
	$yesterday = date('Y-m-d', strtotime("-1 day"));
	$lastWeek = date('Y-m-d', strtotime("-1 week"));
	$lastMonth = date('Y-m-d', strtotime("first day of this month"));
	
	
	$page = 0;
	$regno = '';
	$producer = '';
	$fromDate = '';
	$toDate = '';
	$hasMore = false;
	
	if(isset($_POST) && is_array($_POST) && count($_POST) !== 0) {
		$page = (int) $_POST['page'];
		$regno = $_POST['regno']; 
		$producer = $_POST['producer'];
		$fromDate = $_POST['fromDate'];
		$toDate = $_POST['toDate'];
		if($resultSetSize > (($page+1) * 20)) {
			$hasMore = true;
		}
	}
	
	
?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>NBC Media Library | Welcome</title>
<style type="text/css">
	.main {
		width:960px;
		margin:auto;
	}
	h1, p, th, td, input, a, option, button {
		font-family: Arial, "Sans-Serif";
	}
	h1 {
		text-align: center;
	}
	th {
		text-align: left;
	}
	th, td {
		border: 1px solid #ddd;
		padding: 2px;
	}	
	table.form {
		margin: auto;
	}
	table.data {
		background-color: #eee;
		margin: auto;
		margin-top: 20px;
	}
	table.form td:first-child {
		text-align: right;
		padding-right: 4px;
	}
	table.data td {
		vertical-align: top;
	}
	input.text {
		width: 300px;
	}
	select {
		width: 305px;
	}
	p.message {
		text-align: center;
	}
	a,a:link,a:visited,a:active,a:hover {
		color: #06f;
		text-decoration: underline;
	}
</style>

<script type="text/javascript">
function query() {
	document.forms[0].elements.page.value = 0;
	document.forms[0].submit();
};
function prevPage() {
	document.forms[0].elements.page.value--;
	document.forms[0].submit();
};
function nextPage() {
	document.forms[0].elements.page.value++;
	document.forms[0].submit();
};
function setDate(field,date) {
	if(date == "today")
		document.forms[0].elements[field].value = "<?php echo $today; ?>";
	else if(date == "yesterday")
		document.forms[0].elements[field].value = "<?php echo $yesterday; ?>";
	else if(date == "lastWeek")
		document.forms[0].elements[field].value = "<?php echo $lastWeek; ?>";
	else if(date == "lastMonth")
		document.forms[0].elements[field].value = "<?php echo $lastMonth; ?>";
};
</script>

</head>
<body>
<div class="main">

	<h1>Welkom bij de NBC Media Library</h1>
	
<?php 
	$checked='';
	if(!isset($_POST['page']) || isset($_POST['regnoExact'])) {
 		$checked = 'checked="checked"';
	}
?>
	
	<form method="post">
		<input type="hidden" name="page" value="<?php echo isset($_POST['page'])? $_POST['page'] : 0; ?>" />
		<input type="hidden" name="producerArray" value="<?php echo implode(';', $producerArray); ?>" />
			<table class="form">
			<tr>
				<td>ID</td><td><input class="text" name="regno" value="<?php echo $regno; ?>" />&nbsp;<input
					type="checkbox" name="regnoExact" id="regnoExact" <?php echo $checked; ?> >&nbsp;<label for="regnoExact">Exact</label></td>
			</tr>
			<tr>
				<td>Producent</td><td>
				<select name="producer">
				<?php
					$selected = $producer === "" ? "selected" : "";
					echo "<option value='' $selected></option>";
					foreach($producerArray as $p) {
						$selected = $p === $producer? "selected" : "";
						echo "<option value='$p' $selected>$p</option>";
					}
				?>
				</select>
			</td>
			</tr>
			<tr>
				<td>Sinds (yyyy-mm-dd)</td><td><input style="width:80px;" name="fromDate" value="<?php echo $fromDate; ?>" />
				<button type="button" onclick="setDate('fromDate','today');">Vandaag</button>
				<button type="button" onclick="setDate('fromDate','yesterday');">Gisteren</button>
				<button type="button" onclick="setDate('fromDate','lastWeek');">Vorige week</button>
				<button type="button" onclick="setDate('fromDate','lastMonth');">Deze maand</button>
			</td>
			</tr>
			<tr>
				<td>Tot (yyyy-mm-dd)</td><td><input style="width:80px;" name="toDate" value="<?php echo $toDate; ?>" />
				<button type="button" onclick="setDate('toDate','today');">Vandaag</button>
				<button type="button" onclick="setDate('toDate','yesterday');">Gisteren</button>
				<button type="button" onclick="setDate('toDate','lastWeek');">Vorige week</button>
				<button type="button" onclick="setDate('toDate','lastMonth');">Deze maand</button>
			</td>
			</tr>
			<tr>
				<td style="vertical-align: top;">Status</td>
				<td>
					<input type="checkbox" id="backupOk" name="backupOk" <?php echo isset($_POST['backupOk'])  ? "checked" : ""; ?> />&nbsp;<label for="backupOk">Amazon backup onvoltooid of mislukt</label><br/>
					<input type="checkbox" id="wwwOk" name="wwwOk" <?php echo isset($_POST['wwwOk'])  ? "checked" : ""; ?> />&nbsp;<label for="wwwOk">Afgeleiden maken onvoltooid of mislukt</label>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="text-align: center;">
					<button type="button" style="width:20%;" <?php echo $page === 0? "disabled" : ""; ?> onclick="prevPage();">Vorige</button>
					<button type="button" style="width:50%;"  onclick="query();">Zoeken</button>
					<button type="button" style="width:20%;" <?php echo $hasMore? "" : "disabled"; ?> onclick="nextPage();">Volgende</button>
				</td>
			</tr>
		</table>
	</form>

	<?php if($resultSetSize == 0) { ?>
	<p class="message">Geen zoekresultaten</p>
	<?php } ?>
	
	
	<?php if($resultSetSize > 0) { ?>
	<p class="message">Aantal zoekresultaten: <?php echo $resultSetSize; ?></p>
	<table class="data">
		<thead>
			<tr>
				<th>ID</th>
				<th>Thumbnail</th>
				<th>Producent</th>
				<th>Verwerkingsdatum</th>
				<th>Naar Amazon</th>
				<th>Naar MediaLib</th>
			</tr>
		</thead>
		<tbody>
			<?php
				foreach ($resultSet as $media) {
			?>
			<tr>
				<td><?php
					if($media->www_ok) {
						echo "<a href='{$_REQUEST['BASE_URL']}/file/id/{$media->regno}/format/medium'>";
					}
					echo $media->regno;
					if($media->www_ok) {
						echo '</a>';
					}
				?></td>
				<td><?php 
					if($media->www_ok) {
						echo "<img src='{$_REQUEST['BASE_URL']}/file/id/{$media->regno}/format/small'/>";
					}
					else {
						echo 'Niet beschikbaar';
					}
				?></td>
				<td><?php echo $media->producer; ?></td>
				<td><?php echo $media->source_file_created; ?></td>
				<td><?php echo $media->backup_ok == 1? 'Ja' : 'Nee'; ?></td>
				<td><?php echo $media->www_ok == 1? 'Ja' : 'Nee'; ?></td>
			</tr>
			<?php
				}
			?>
		</tbody>
	</table>
	<?php } ?>
	
</div>
</body>
</html>