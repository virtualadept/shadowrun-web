<?
include "../header.php";
print "<center>welcome $playername to the shopping arena!<br><br></center>";

// First we'll run our daily maintence and get our shop names.
// Its like a 2 for 1 special! :)
$shops = shopkeeper('5','2');

$shopkey; // Define it just to be safe, not sure if php does autovivication

// I love marking shit up for my players!
$markup = genmarkup();

// For Jordan but not for me.
printdots("pulling price indexes from matrix");
print "<br>";
printdots("downloading to terminal");


// Shit, meet fan
if ($itemdetail = $mysqli->prepare("SELECT gear.gearid,index1,index2,index3,name,weight,(cost * ?),soh FROM gear,store WHERE storename=? AND gear.gearid = store.gearid ORDER BY index2 ASC, index3 ASC, name ASC")) {
	$itemdetail->bind_param('ds',$markup,$shopkey);
	foreach (array_keys($shops) as $shopkey) {
		if (!$config["shop-$shopkey"]) { continue; } // Shop not activated, skip it
		$itemdetail->execute(); 
		$itemdetail->bind_result($gearid,$index1,$index2,$index3,$itemname,$weight,$cost,$soh);
		// Set up the fancy table
		print "<table border = '1'><caption><em>welcome to " . $shops[$shopkey] . "</em></caption>\n
			<tr><th>type<th>subtype<th>name<th>cost<th>weight<th>soh";
//			if ($st == '1') { print "<th>info"; } else { print "<th>buy"; }
			print "<th>buy";
		
		// Populate tables
		while ($itemdetail->fetch()) {
			print "<tr><td>$index2<td>$index3<td>$itemname<td>$cost<td>$weight<td>$soh";
//			if ($st == '1') { print "<td><a href='item.php?gearid=$gearid'>more info</a>"; }
//				else { print "<td><a href='buy.php?gearid=$gearid&funds=U'>Pers</a>|<a href='buy.php?gearid=$gearid&funds=P'>Party</a>"; }
			if ($soh == '0') {
				print "<td><i>sold out</i>";	
			} else {
				print "<td><a href='buy.php?gearid=$gearid&funds=U'>Pers</a>|<a href='buy.php?gearid=$gearid&funds=P'>Party</a>";
			}
		}
		print "</table>";  // Nested tables fucking suck! :(
		print "<br><br>";
	}
}























?>
