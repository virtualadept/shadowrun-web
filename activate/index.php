<?
include "../header.php";

if (!$config[activate]) {
	print "activation module not installed";
	systemlog('activate',"$username - activate module not installed");
	exit;
}

$code = $mysqli->real_escape_string($_GET['code']);

if (!$code) {
	print "please enter activation code:
		<form action=\"index.php\" method=\"get\">
		<input type=\"text\" name=\"code\"><br>
		<input type=\"submit\" value=\"submit code\">";
}

if ($code) {
	printdots("verifying code $code");
	print "<br><br>";
	if ($codeverify = $mysqli->prepare("SELECT k,v,description FROM activate WHERE md5(k) = ? AND activated IS NULL LIMIT 1")) {
		$codeverify->bind_param('s',$code);
		$codeverify->execute();
		$codeverify->bind_result($k,$v,$description);
		$codeverify->fetch();
		$codeverify->close();
		if ($k && $v) {
			printdots("installing $description into comlink.  Please wait....");
			setconfig($k,$v);
			systemlog('activate_module',"$username activated $description");
			$mysqli->query("UPDATE activate SET activated=NOW() WHERE k=\"$k\"");
	        	print "<br><br>Done!<br>";	
		} else {
			print "Invalid code: $code<br>";
		}
	}
}

print "<br><hr><br>";

print "activated modules:<br><br>";
if ($activelist = $mysqli->query("SELECT description FROM activate WHERE activated IS NOT NULL ORDER BY description")) {
	while ($activeout = $activelist->fetch_assoc()) {
		print "<li>{$activeout[description]}<br>";
	}
}





include "../footer.php";
?>
