<?
include "../header.php";

$msgid = $mysqli->real_escape_string($_GET['messid']);

if ($msgid) {
	// ST's have a slightly different query than normal users, this hopefully reduces a bunch
	// of copy/pasting query based upon the $st bit
	if ($st == '1') {
		$mailto = " IN (SELECT id FROM players WHERE username=?) ";
		$querykey = $username;
	} else {
		$mailto = "=?";
		$querykey = $userid;
	}
	// Pull the message based upon the messageID and also the mailto for verification
//	if ($getmsg = $mysqli->prepare("SELECT DATE_ADD(date, INTERVAL 70 YEAR) as date,players.playername,enc,enckey,subject,body,mailread FROM mailmsg,players WHERE messid=? AND mailto=? AND players.id = mailmsg.mailfrom")) {
	if ($getmsg = $mysqli->prepare("SELECT DATE_ADD(date, INTERVAL 70 YEAR) AS date, plto.playername AS playerto, plfrom.playername AS playerfrom, mm.enc, mm.enckey, mm.subject, mm.body, mm.mailread FROM mailmsg AS mm INNER JOIN players AS plto ON mm.mailto = plto.id INNER JOIN players AS plfrom ON mm.mailfrom = plfrom.id WHERE messid=? AND mailto$mailto")) {
		$getmsg->bind_param('ss',$msgid,$querykey);
		$getmsg->execute();
		$getmsg->bind_result($date,$mailto,$mailfrom,$enc,$enckey,$subject,$body,$mailread);
		while ($getmsg->fetch()) {
			print "mail message id $msgid<br><br>";
			print "from: $mailfrom<br>";
			print "to: $mailto<br>";
			print "date: $date<br>";
			if ($enc == '1') {
				print "encryption: encrypted<br>";
			} else {
				print "encryption: none<br>";
			}
			if ($mailread == '0') {
				print "read status: unread<br>";
			} else {
				print "read status: read<br>";
			}
			print "<br><br>";
			print "subject: $subject<br>";
			print "<br><br>";
			if ($enc == '1' && $enckey) { // Message is encrypted
				print "!! WARNING WARNING ENCRYPTED MESSAGE !!<br><br>";
				// *sigh* i need to find a better way to do this
				$crypt = new encryption_class;
				$bodycrypt = $crypt->encrypt($enckey,$body);
				print $bodycrypt;
			} else {
				print "$body<br>";
			}
		}
		$getmsg->close();
		// If we got this far without errors, then its safe to mark the message as read
		// we have already validated the user as owning the message, so we can trust
		// messid as being correct
		if ($mailread == '0') {
			$closemsg = $mysqli->query("UPDATE mailmsg SET mailread='1' WHERE messid=\"$msgid\"");
		}
	}

}

if (!$msgid) {
	print "boing";
}



















include "../footer.php";
?>
