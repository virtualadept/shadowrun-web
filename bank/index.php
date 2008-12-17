<? 

require "../header.php";

if (!$config[bank] && !$st) {
	print "<center><br><br>you do not have an account here<br>good-bye!<br>CONNECTION CLOSED<br></center>";
	systemlog('bank_index','Deactivated');
	exit;
}

$mode = $mysqli->real_escape_string($_GET['mode']);
$fromid = $mysqli->real_escape_string($_GET['fromid']);
$toid = $mysqli->real_escape_string($_GET['toid']);
$amount = $mysqli->real_escape_string($_GET['amount']);
$note = $mysqli->real_escape_string($_GET['note']);
$mayhemid = $mysqli->real_escape_string($_GET['mayhemid']);


print "<br><br><br><center>welcome to the bank of " .  $config[sitename] . "</center><br>";
print "<br><br>";


$coh = cashonhand();
$uid2pn = userid2playername();
$npcid = getnpcid();

if ($mode == "xfer" && $amount) {
	// I have more sanity checks than bank of america! :)
	if ($mayhemid && !$st) {
		print "someone has been reading my code :)";
		systemlog('bank_xfer',"mayhem set to $mayhemid but $userid is not an st");
		exit;
	}
	if ($amount == '0') { // Retards.
		print "wtf? are you going to transfer huge and kisses next?<br>";
		systemlog('bank_xfer',"$userid tried to transfer 0 dollars");
		exit;
	}
	if ($fromid != $userid && $fromid != '0' && !$st) { // Put this force to prevent brute forcing of account balances
		print "trying to sneak from someone elses account eh?";
		systemlog('bank_xfer',"$userid tried to xfer money from $fromid account");
		exit;
	}
	if ($coh[$fromid] < 0 || $coh[$fromid] < $amount && !$st) { // Now we see if they are trying to overdraft
		print "a little overdraft are we?<br>";
		systemlog('bank_xfer',"$userid tried to overdraft their account with $amount");
		exit;
	}

	// At this point we've done some basic checks, go ahead and transfer the cash out.
	
	// Some basic formatting shit
	$uid2pn[0] = "Party Funds";  // For the sake of this on out, uid 0 is Party funds, not ST
	$toname = $uid2pn[$toid];
	$fromname = $uid2pn[$fromid];
	
	if ($mayhemid && $st) { // Log this shit to find bugs vs mayhem
		systemlog('bank_xfer',"mayhem ensures! changed $userid to $mayhemid");
		$userid = $mayhemid;	

	}
	if ($note) { $note = "<b>($note)</b>"; }

	// Suck it out of the transferer's account if they are not an ST
	if (!$st) {
		updatebank(-$amount,"transfer to: $toname $note",$fromid,$userid);
	}
	// Now dump it in the recipients account
	updatebank($amount,"transfer from: $fromname $note",$toid,$userid);
	print "$amount has been transfered from $fromname => $toname $note<br>\n
		<a href=\"index.php\">back</a>\n";
	systemlog('bank_xfer',"$fromid ($fromname) xfered $amount to $toid ($toname) $note");
	exit;
}

// At this point we have no modes, no anything.  Just a the default page-view

// ST's can see everyones totals
if ($st) {
	foreach ($coh as $uid => $cash) {
		print "<li>";
		if ($uid == '0') {
			print "Party Cash => " . $cash . "<br>";
		} else {
			print $uid2pn[$uid] . " => " . $cash . "<br>";
		}
	}
} else {
	if (!$coh[$userid]) { $coh[$userid] = "0"; }
	print "<li>The current balance for {$uid2pn[$userid]} is {$coh[$userid]} <br>";
	print "<li>The current party balance is {$coh[0]} <br>";
}

print "<br><hr><br>";

// ST's can see the entire account history
if ($st) {
	if ($transquery = $mysqli->query("SELECT accountid,transamt,transnote,date,purchaser FROM bank ORDER BY date DESC")) {
        	print "<table border = '1'>\n 
                	        <caption><em>transaction log for $playername</em></caption>\n
                        	<tr><td>Account Name<td>posting date<td>account change<td>description<td>editor\n";
		while ($translog = $transquery->fetch_assoc()) {
			if ($translog[accountid] == '0') {
				$accountname = "Party Account";
			} else {
				$accountname = $uid2pn[$translog[accountid]];
			}

			print "<tr><td>$accountname<td>" . $translog['date'] . "<td>" . $translog['transamt'] . "<td>" 
				. $translog['transnote'] . "<td>" . $uid2pn[$translog[purchaser]];
		}
		$transquery->close();
		print "</table>\n";
		print "end of log<br>";
	}
} else {
	// Players Personal Account
	if ($transquery = $mysqli->query("SELECT transamt,transnote,date FROM bank 
					  WHERE accountid = $userid ORDER BY date DESC")) {
        	print "<table border = '1'>\n 
                	        <caption><em>transaction log for $playername (current balance " . $coh[$userid] . ")</em></caption>\n
                        	<tr><td>posting date<td>account change<td>description\n";
		while ($translog = $transquery->fetch_assoc()) {
			print "<tr><td>" . $translog['date'] . "<td>" . $translog['transamt'] . "<td>" 
				. $translog['transnote'];
		}
		$transquery->close();
		print "</table>\n";
		print "end of log<br>";
	}
	print "<br><hr><br>";

	// Party Account
	if ($grouptransquery = $mysqli->query("SELECT transamt,transnote,date,purchaser FROM bank 
						WHERE accountid='0' ORDER BY date DESC")) {
        	print "<table border = '1'>\n 
                	        <caption><em>transaction log for Party Account (current balance " . $coh[0] . ")</em></caption>\n
                        	<tr><td>posting date<td>account change<td>description<td>by whom\n";
		while ($grouptranslog = $grouptransquery->fetch_assoc()) {
			print "<tr><td>" . $grouptranslog[date] . "<td>" . $grouptranslog[transamt] . "<td>" 
				. $grouptranslog[transnote] . "<td>" . $uid2pn[$grouptranslog[purchaser]];
		}
		$grouptransquery->close();
		print "</table>\n";
		print "end of log<br>";
	}
}

print "<br><hr><br>";

// Transfer funds entry
print "Funds Transfer<br>\n
	<form action=\"index.php\" method=\"GET\">\n
	From: \n
	<select name=\"fromid\">
	<option value=\"0\">Party Funds (current balance " . $coh[0] . ")</option>\n";
	if ($st) { // ST's can see everyones shizzle
		foreach ($uid2pn as $stuid => $stname) {
			if (!$coh[$stuid]) { $coh[$stuid] = '0'; } // Non existant means no cash
			if ($stuid == '0') { continue; } // We already have party funds as an option
			print "<option value=\"$stuid\">$stname (uid $stuid) "; 
			if ($npcid[$stuid]) { print " !!NPC!! "; }
			print " (current balance " . $coh[$stuid] . ")</option>\n";
		}
	}
	if (!$st) { // Sanity check for non ST'ers
		print "<option value=\"$userid\">" . $uid2pn[$userid] . " (current balance " . $coh[$userid] . ")</option>\n";
	}
print "</select>\n
	<br>To: 
	<select name=\"toid\">\n
	<option value=\"0\">Party Funds</option><br>\n";
	// Here we need a list of all the current non-stified characters.
	// We have to pull the list from the $uid2pn (not the coh) because
	// they may/may-not have bank accounts.
	foreach ($uid2pn as $uid => $name) {
		if (!$st) {
			if ($npcid[$uid] || $uid == '0') {
				continue;
			}
		}
		print "<option value=\"$uid\">$name";
		if ($uid == $userid) {
			print " (this is you idiot)"; // Math is hard.
		}
		print "</option><br>\n";
	}
print "</select>";

// Here we can forge xfer's :)
if ($st) {
	print "<br>Mayhem: 
		<select name=\"mayhemid\">\n
		<option value=\"\">No Mayhem</option>\n";
	foreach ($uid2pn as $mayhemuid => $mayhemname) {
		print "<option value=\"$mayhemuid\">$mayhemname (uid $mayhemuid)";
		print "</option><br>\n";
	}
	print "</select>";
}

// Rest of the form
print "<br>Amount: 
	<input type=\"text\" name=\"amount\" size=\"5\"><br>
	<br>Note (optional): 
	<input type=\"text\" name=\"note\"><br>
	<input type=\"hidden\" name=\"mode\" value=\"xfer\"><br>
	<input type=\"submit\" value=\"Transfer\">";











print "<br><br>have a nice day";
require "../footer.php";
?>
