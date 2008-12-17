<?
include "../header.php";

print "<center>welcome to your inbox $playername!</center><br>";

print "unread messages:<br>";

// Unconfusion:
// ST's are assumed to have more than 1 entry in the players db for all of the NPC's.  Players
// are expected to have one entry.  For players we use their id to look up messages that are
// destined for them.  Since the ST probably has a bunch of NPC's, then we need to look them up
// by their auth'd username and include all of the id's of the NPC's they play.
//
// Yeah, its ghetto.
if ($st == '1') {
	if ($getmessid = $mysqli->prepare("SELECT mm.messid,DATE_ADD(date, INTERVAL 70 YEAR),plto.playername,plfrom.playername,mm.subject FROM mailmsg AS mm INNER JOIN players AS plto ON mm.mailto = plto.id INNER JOIN players as plfrom ON mm.mailfrom = plfrom.id WHERE mm.mailto IN (SELECT id FROM players WHERE username=?) AND mm.mailread='0'")) {
		$getmessid->bind_param('s',$username);
		$getmessid->execute();
		$getmessid->bind_result($messid,$date,$mailto,$mailfrom,$subject);
		while ($getmessid->fetch()) {
			print "<a href=\"msg.php?messid=$messid\">$messid</a> - $date - From: $mailfrom | To: $mailto | $subject<br>";
		}
		$getmessid->close();
	}
} else {
	if ($getmessid = $mysqli->prepare("SELECT mailmsg.messid,DATE_ADD(mailmsg.date, INTERVAL 70 YEAR),players.playername,mailmsg.subject FROM mailmsg,players WHERE mailmsg.mailto=? AND mailmsg.mailfrom = players.id AND mailmsg.mailread='0'")) {
		$getmessid->bind_param('s',$userid);
		$getmessid->execute();
		$getmessid->bind_result($messid,$date,$mailfrom,$subject);
		while ($getmessid->fetch()) {
			print "<a href=\"msg.php?messid=$messid\">$messid</a> - $date - $mailfrom - $subject<br>";
		}
		$getmessid->close();
	}
}

print "<br><br><br>";

print "read messages:<br>";
if ($st == '1') {
		if ($getmessid = $mysqli->prepare("SELECT mm.messid,DATE_ADD(date, INTERVAL 70 YEAR),plto.playername,plfrom.playername,mm.subject FROM mailmsg AS mm INNER JOIN players AS plto ON mm.mailto = plto.id INNER JOIN players as plfrom ON mm.mailfrom = plfrom.id WHERE mm.mailto IN (SELECT id FROM players WHERE username=?) AND mm.mailread='1'")) {
		$getmessid->bind_param('s',$username);
		$getmessid->execute();
		$getmessid->bind_result($messid,$date,$mailto,$mailfrom,$subject);
		while ($getmessid->fetch()) {
			print "<a href=\"msg.php?messid=$messid\">$messid</a> - $date - From: $mailfrom | To:$mailto | $subject<br>";
		}
		$getmessid->close();
	}
} else {
	if ($getmessid = $mysqli->prepare("SELECT mailmsg.messid,DATE_ADD(mailmsg.date, INTERVAL 70 YEAR),players.playername,mailmsg.subject FROM mailmsg,players WHERE mailmsg.mailto=? AND mailmsg.mailfrom = players.id AND mailmsg.mailread='1'")) {
		$getmessid->bind_param('s',$userid);
		$getmessid->execute();
		$getmessid->bind_result($messid,$date,$mailfrom,$subject);
		while ($getmessid->fetch()) {
			print "<a href=\"msg.php?messid=$messid\">$messid</a> - $date - $mailfrom - $subject<br>";
		}
		$getmessid->close();
	}
}











include "../footer.php";
?>
