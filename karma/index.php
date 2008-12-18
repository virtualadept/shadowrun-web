<?
include "../header.php";

$mode = $mysqli->real_escape_string($_POST['mode']);
$spendkarma = $mysqli->real_escape_string($_POST['spendkarma']);
$karmareason = nl2br($_POST['karmareason']);



print "welcome to the karma manager!<br><br><br>\n";

if ($st) {
	print "<ul><li>Karma Totals<br>\n";
	$pcid2un = activeuserid2playername();
	foreach ($pcid2un as $userid => $username) {
		print "<ul><li>$username => " . gettotalkarma($userid) . "</ul></li><br>\n";
	}
	
	print "<li>Karma Transactions<br>\n";
	if ($karmatrans = $mysqli->prepare("SELECT karmaid,playerid,date,karma,note,state FROM karma ORDER BY karmaid DESC")) {
		$karmatrans->execute();
		$karmatrans->bind_result($karmaid,$playerid,$date,$karma,$note,$state);
		print "<table border = '1'>\n
			<tr><th>Name<th>Date<th>Karma<th>Note<th>State\n";
		while ($karmatrans->fetch()) {
			if ($state == 'P') { $state = "Pending"; }
			if ($state == 'C') { $state = "Completed"; }
			if ($state == 'X') { $state = "Deleted"; }
			print "<tr><th>{$pcid2un[$playerid]}<td>$date<td>$karma<td>$note<td>$state<td><a href=\"karmaedit.php?karmaid=$karmaid\">Edit</a>\n";
		}
		print "</table></center>";

	}
exit ; // We bail here, user stuff is below
}

// Pull karma from the db for this character
$totalkarma = gettotalkarma($userid);
$compkarma = getcompletedkarma($userid);
$pendingkarma = getpendingkarma($userid);

// Insert karma request into db.
if ($mode == 'karmarequest' && $spendkarma && $karmareason) {
	if ($spendkarma > ($totalkarma + $compkarma + $pendingkarma)) {
		print "Hey buddy, trying to spend more karma than you have arent ya?<br>
			Your total of $totalkarma + completed ($compkarma) + pending ($pendingkarma) is less than your request $spendkarma.<br>";
		systemlog('karma_spend',"$username tried to overdraft a karma request $spendkarma");
		exit;
	}
	$karmasql = $mysqli->prepare("INSERT INTO karma (playerid,date,karma,note,state) VALUES ($userid,NOW(),?,?,'P')");
	$karmadeduction = "-$spendkarma";
	$karmasql->bind_param('ss',$karmadeduction,$karmareason);
	$karmasql->execute();
	print "Your karma spendage request of \"$spendkarma\" has been logged with reason of \"$karmareason\".  <br><br><small>Please check your email, as to the response to your request will be sent there<br>";
	exit; //bail out, we're done here!
}



// Start User Area
// Print out Karma from the queries above
print "<ul><li>Your lifetime karma pool is $totalkarma<br>
	<ul><li>" . abs($pendingkarma) . " are in pending transactions
	<li>" . abs($compkarma) . " are spent in completed transactions
	<li> You have " . ($totalkarma + $compkarma + $pendingkarma) . " available to put spending requests for
	</ul></li>";


// All karma transactions which are completed
print "<br><br><br>The latest APPROVED karma transactions: ";
if ($karmapctrans = $mysqli->prepare("SELECT date,karma,note FROM karma WHERE playerid = $userid AND state = 'C' ORDER BY karmaid DESC")) {
	$karmapctrans->execute();
	$karmapctrans->bind_result($date,$karma,$note);
	print "<table border = '1'>\n
		<tr><th>Date<th>Karma<th>Note\n";
	while ($karmapctrans->fetch()) {
		print "<tr><th>$date<td>$karma<td>$note\n";
	}
	$karmapctrans->close();
	print "</table></center>";
}

// All karma transactions which are pending
if ($karmapcpending = $mysqli->prepare("SELECT date,karma,note FROM karma WHERE playerid = $userid AND state = 'P' ORDER BY karmaid DESC")) {
	$karmapcpending->execute();
	$karmapcpending->bind_result($date,$karma,$note);
	print "<br><br><br>The PENDING karma transactions: ";
	print "<table border = '1'>\n
		<tr><th>Date<th>Karma<th>Note\n";
	while ($karmapcpending->fetch()) {
		print "<tr><th>$date<td>$karma<td>$note\n";
	}
	$karmapcpending->close();
	print "</table></center>";
}

// Karma spending form
print "<br><br><br>Karma spenditure request form: ";
print "<form action=\"index.php\" method=\"POST\"><br>
	Amount you wish to spend: <input type=\"text\" name=\"spendkarma\" maxlength=\"3\" size=\"3\"><br>
	What you wish to buy (include justification/wiki downtime links)<br>
	<textarea name=\"karmareason\" cols=\"40\" rows=\"5\"> </textarea><br>
	<input type=\"hidden\" name=\"mode\" value=\"karmarequest\">
	<input type=\"submit\" value=\"Request Karma Spendage\">
	</form>";

// Karma reference form

print "<u>Karma Reference Guide</u><br><br>\n
	New Specalization -> 2<br>\n
	New Knowledge/Language skill -> 2<br>\n
	New Active Skill -> 4<br>\n
	New Active Skill Group -> 10<br>\n
	Improving Knowledge/Lang Skill by 1 -> New Rating<br>\n
	Improving Active Skill by 1 -> New Rating x 2<br>\n
	Improving an Active Skill Group by 1 -> New Rating x 5<br>\n
	Improving an Attribute by 1 -> New Rating x 3<br>\n
	New Postive Quality -> BP Cost x 2<br>\n
	Removing Negative Quality -> BP Bonus x 2<br>\n
	New Spell -> 5<br>\n
	New Complex Form -> 2<br>\n
	Improving Complex Form by 1 -> New Rating<br>\n";









include "../footer.php";
?>
