<?
include "../header.php";

$gearid = $mysqli->real_escape_string($_GET['gearid']);
$funds = $mysqli->real_escape_string($_GET['funds']);
$buy = $mysqli->real_escape_string($_GET['buy']);

// Shouldn't be here if they are missing some important information
if (!$gearid || !$funds) {
	print "what are you doing here?<br>";
	die;
}

// Markup is based upon a bunch of random shit.
$markup = genmarkup(); 

// Pull the itemid information from the store gearid so we know if someone is just incrementing the
// gearid and trying to pull a fast one.
if ($getiteminfo = $mysqli->prepare("SELECT gear.name,(gear.cost * ?),store.soh FROM gear,store WHERE store.gearid=? AND store.gearid=gear.gearid")) {
	$getiteminfo->bind_param('si',$markup,$gearid);
	$getiteminfo->execute();
	$getiteminfo->bind_result($itemname,$itemcost,$itemsoh);
	$getiteminfo->fetch();
	$getiteminfo->close();
}

// Check to see if what they are trying to buy is even in the store.  If not
// log the attempt (for ST retribution) and give them an error message.
if (!$itemname && !$itemcost) {
	print "you are trying to buy something that is not in the store.  This attempt has been logged<br>";
	systemlog('gear_buy',"$username tried to buy $gearid but it was not in the store");
	die;
}

if ($funds == 'U') { $fundsource = $userid; }
if ($funds == 'P') { $fundsource = '0'; }

if ($getplayerbalance = $mysqli->prepare("SELECT SUM(transamt) AS cash FROM bank WHERE accountid=? GROUP BY accountid")) {
	$getplayerbalance->bind_param('i',$fundsource);
	$getplayerbalance->execute();
	$getplayerbalance->bind_result($cashonhand);
	$getplayerbalance->fetch();
	$getplayerbalance->close();
}

if (!$cashonhand) { $cashonhand = '0'; }

if ($itemsoh == '0') { print "i'm sorry but that item is out of stock, please try again later<br>"; die; }

if (!$buy) {
	print "<center>please confirm purchase</center><br>";

	if ($cashonhand < $itemcost) {
		print "You do not have enough money to pay for this.  You need " . ($itemcost - $cashonhand) . " more dollahs<br>";
		die;
	}
		
	print "you are trying to buy $itemname for the bargan price of $itemcost.  You have $cashonhand in your account and will have ". ($cashonhand - $itemcost) . " after this transaction.  Do you wish to continue with this purchase?<br><br><center><h1><a href=\"buy.php?gearid=$gearid&funds=$funds&buy=1\">YES</a> | NO</h1></center>";
}

if ($buy) {
	print "purchasing $itemname... Please stand by..<br>";
	// First we'll decrement the SOH so we dont get into a race condition
	$itemsoh--;
	$mysqli->query("UPDATE store SET soh=$itemsoh WHERE gearid=$gearid");
	
	// Next we'll log the transaction in their bank account
	$mysqli->query("INSERT INTO bank (transamt,transnote,date,accountid,purchaser) VALUES (\"-$itemcost\",\"$itemname\",NOW(),\"$fundsource\",\"$userid\")");

	// Now we'll add the gear into their inventory
	$mysqli->query("INSERT INTO playerinv (userid,gearid,date) VALUES (\"$userid\",\"$gearid\",NOW())");

	print "Done!<br>";

}
