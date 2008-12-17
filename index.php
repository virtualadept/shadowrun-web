<?
include "header.php";

// Mail Module
// ST's get their own query since they are checking multiple accounts.
if ($config[mail] || $st == '1') {
if ($st == '1') {
	if ($unreadlist = $mysqli->prepare("SELECT COUNT(*) FROM mailmsg WHERE mailto IN (SELECT id FROM players WHERE username=?) AND mailread = '0'")) {
		$unreadlist->bind_param('s',$username);
		$unreadlist->execute();
		$unreadlist->bind_result($newmailcount);
		print "<div class=entry>";
		while ($unreadlist->fetch()) {	
			print "<p class=title><a href=\"inbox/index.php\">mail module:</a></p> 
				You have $newmailcount new messages!";
		}
		print "</div>";
		$unreadlist->close();
	}
} else {
	if ($config[mail]) {
	if ($unreadlist = $mysqli->prepare("SELECT COUNT(*) FROM mailmsg WHERE mailto = ? and mailread = '0'")) {
		$unreadlist->bind_param('i',$userid);
		$unreadlist->execute();
		$unreadlist->bind_result($newmailcount);
		print "<div class=entry>";
		while ($unreadlist->fetch()) {	
			print "<p class=title><a href=\"inbox/index.php\">mail module:</a></p>
				You have $newmailcount new messages!";
		}
		print "</div>";
		$unreadlist->close();
	}
	}
}
}
// Bank Module
if ($config[bank] || $st == '1') {
if ($st == '1') {
	print "<div class=entry>
		<p class=title><a href=\"bank/\">bank module:</a></p>";
	if ($acctbalance = $mysqli->query("SELECT accountid,playername,SUM(transamt) AS cash FROM bank AS b INNER JOIN players AS p ON b.accountid = p.id WHERE accountid NOT IN (SELECT id FROM players WHERE username='$username') GROUP BY accountid")) {
		while ($acctres = $acctbalance->fetch_assoc()) {
			if ($acctres['accountid'] == '0') {
				$acctres['playername'] = 'Party Loot';
			}
			print "<li>The current balance of " . $acctres['playername'] . " is " . $acctres['cash'];
		}
		$acctbalance->close();
	} else {
		print "Something went wrong";
	}
	// Gettohack for the party account (players.id='0');
	if ($acctbalance = $mysqli->query("SELECT accountid,SUM(transamt) AS cash FROM bank WHERE accountid='0' GROUP BY accountid")) {
		while ($acctres = $acctbalance->fetch_assoc()) {
			print "<li>The current party loot balance is " . $acctres['cash'];
		}
	} else {
		print "Something went wrong";
	}
	print "</ul></div>";
} else {
	print "<div class=entry>
		<p class=title><a href=\"bank/\">bank module:</a></p>";
	if ($acctbalance = $mysqli->query("SELECT SUM(transamt) AS cash FROM bank where accountid=$userid")) {
		$acctres = $acctbalance->fetch_assoc();
		if (!$acctres['cash']) { $acctres['cash'] = "0"; }
		print "<li>your current balance is <u>" . $acctres['cash'] . "</u>";
		$acctbalance->close();
	}
	if ($acctdatequery = $mysqli->query("SELECT DATE_ADD(date,INTERVAL 70 YEAR) as date FROM bank WHERE accountid=$userid ORDER BY date DESC LIMIT 1")) {
		$acctdate = $acctdatequery->fetch_assoc();
		if ($acctdate['date']) { print " as of <u>" . $acctdate['date'] . "</u>"; }
	}
		$acctdatequery->close();
	if ($partybalance = $mysqli->query("SELECT SUM(transamt) AS cash FROM bank where accountid='0'")) {
		$partyres = $partybalance->fetch_assoc();
		if (!$partyres['cash']) { $partyres['cash'] = "0"; }
		print "<li>the current party account balance is <u>" . $partyres['cash'] . "</ul></div>";
		$partybalance->close();
	}
	
	print "</ul></div>";
}
}

// Job Module
if ($config[job]) {
	if ($numactive = $mysqli->query("SELECT jobid FROM job WHERE postdate <= NOW() AND expdate >= NOW() and completed = '0'")) {
		print "<div class=entry>
			<p class=title><a href=\"job\">job module:</a></p>
			you have " . $numactive->num_rows . " job(s) available
			</div>";
		$numactive->close();
	}
}

// Karma Module
print "<div class=entry>
	<p class=title><a href=\"karma\">karma module:</a></p>";

if ($st) {
	print "<ul><li>Karma Totals<br>\n";
	$pcid2un = activeuserid2playername();
	foreach ($pcid2un as $userid => $username) {
		print "<ul><li>$username => " . gettotalkarma($userid) . "</ul></li><br>\n";
	}
	
}
if (!$st) {
	$totalkarma = gettotalkarma($userid);
	$compkarma = getcompletedkarma($userid);
	$pendingkarma = getpendingkarma($userid);
	print "<ul><li>Your lifetime karma pool is $totalkarma<br>
		<ul><li>" . abs($pendingkarma) . " are in pending transactions
		<li>" . abs($compkarma) . " are spent in completed transactions
		<li> You have " . ($totalkarma + $compkarma + $pendingkarma) . " available to put spending requests for
		</ul></li>";
}
print "</div>";

// New Shop Stuff Module


// Inventory Module


include "footer.php";
?>
