<?
include "../header.php";

$karmaid = $mysqli->real_escape_string($_GET['karmaid']);
$updatekarmaid = $mysqli->real_escape_string($_POST['updatekarmaid']);
$spendkarma = $mysqli->real_escape_string($_POST['spendkarma']);
$karmastate = $mysqli->real_escape_string($_POST['karmastate']);
$karmareason = nl2br($_POST['karmareason']);
$mode = $mysqli->real_escape_string($_POST['mode']);


print "welcome to the karma editor!<br><br><br>\n";

if (!$st) {
	print "This area is not open to players (yet)\n";
	exit;
}

if (!$karmaid && !$updatekarmaid) {
	print "What the hell are we supposed to edit if you dont tell me what id to use!<br>";
	exit;
}

$pcid2un = userid2playername();  // We are going to need this no matter what

if ($st) {
	print "<ul><li>Editing Karma ID $karmaid";
	if ($getkarmainfo = $mysqli->prepare("SELECT playerid,date,karma,note,state FROM karma WHERE karmaid = $karmaid")) {
		$getkarmainfo->execute();
		$getkarmainfo->bind_result($playerid,$date,$karma,$note,$state);
		$getkarmainfo->fetch();
		print "<form action=\"karmaedit.php\" method=\"post\">
			<ul><li>Player: {$pcid2un[$playerid]}($playerid)\n
			<li>Date: $date\n
			<li>Karma: <input type=\"text\" name=\"spendkarma\" value=\"$karma\" maxlength=\"3\" size=\"3\"> \n
			<li>Note:<br>\n
			<textarea name=\"karmareason\" cols=\"40\" rows=\"5\">$note</textarea><br>\n
			<li>State:<br> ";
		print "<input type=\"radio\" name=\"karmastate\" value=\"C\"" ; if ($state == 'C') { print "checked"; } print ">Completed<br>\n"; 
		print "<input type=\"radio\" name=\"karmastate\" value=\"P\"" ; if ($state == 'P') { print "checked"; } print ">Pending<br>\n"; 
		print "<input type=\"radio\" name=\"karmastate\" value=\"X\"" ; if ($state == 'X') { print "checked"; } print ">Deleted<br>\n"; 
		print "</ul></ul></li>\n
			<input type=\"hidden\" name=\"updatekarmaid\" value=\"$karmaid\">\n
			<input type=\"hidden\" name=\"mode\" value=\"updatekarma\">\n
			<input type=\"submit\" value=\"Update Karma Request\">\n
			</form>";
		
	}


}

if ($mode == 'updatekarma' && $updatekarmaid && $spendkarma && $karmareason && $karmastate) {
	$karmasql = $mysqli->prepare("UPDATE karma SET karma=?, note=?, state=? WHERE karmaid=?");
	$karmasql->bind_param('ssss',$spendkarma,$karmareason,$karmastate,$updatekarmaid);
	$karmasql->execute();
	print "Update $updatekarmaid to state $karmastate spending $spendkarma for reason $karmareason";
	systemlog('karma_update',"$updatekarmaid state $karmastate value $spendkarma updated");
}


include "../footer.php";
?>
