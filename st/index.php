<?
include "../header.php";
$mode = $mysqli->real_escape_string($_GET['mode']);
$k = $mysqli->real_escape_string($_GET['k']);
$v = $mysqli->real_escape_string($_GET['v']);
$karma = $_GET['karma'];
$knote = $_GET['knote'];

if ($st != '1') {
	print "go away!";
	systemlog('st_config','access denied');
	exit;
}

$allmodes = array('config','systemlog','activate','playernotes','karma');
if (!$mode) {
	print "please select which mode you would like to see:<br>\n";
	foreach ($allmodes as $individualmode) {
		print "<li><a href=\"?mode=$individualmode\">$individualmode</a><br>";
	}
}

if ($mode == 'config') {
	$configsql = $mysqli->query("SELECT * FROM config");
	while ($configout = $configsql->fetch_assoc()) {
		print $configout[k] . " => " . $configout[v] . "<br>";
	}
	print "<br>Add config<br><br><form action=\"index.php\" method=\"get\"><br>
		Key: <input type=\"text\" name=\"k\" length=\"10\"> <br>
		Value: <input type=\"text\" name=\"v\" length=\"10\"> <br>
		<input type=\"hidden\" name=\"mode\" value=\"addconfig\">
		<input type=\"submit\" value=\"addconfig\">";
}

if ($mode == 'addconfig' && $k && $v) {
	print "Adding $k => $v<br";
	setconfig($k,$v);
}

if ($mode == 'systemlog') {
	$systemlogsql = $mysqli->query("SELECT * FROM systemlog ORDER BY startdate DESC");
	while ($systemlogout = $systemlogsql->fetch_assoc()) {
		print $systemlogout[startdate] . " | " . $systemlogout[program] . " | " 
			. $systemlogout[msg] . " | " . $systemlogout[who] . "<br>";
	}
}

if ($mode == 'activate') {
	$activatesql = $mysqli->query("SELECT md5(k) AS md5,k,v,activated,description FROM activate ORDER BY k");
	while ($activateout = $activatesql->fetch_assoc()) {
		if (!$activateout[activated]) {
			$activateout[activated] = 'INACTIVE';
		}
		print $activateout[md5] . " | " . $activateout[k] . " => " 
			. $activateout[v] . " | " . $activateout[activated] . " | " . $activateout[description] . "<br>";
	}
}

if ($mode == 'playernotes') {
	$getplayernotes = $mysqli->prepare("SELECT playerid,type,notes FROM playernotes");
	$getplayernotes->execute();
	$getplayernotes->bind_result($playernoteid,$notetype,$notenote);
	while ($getplayernotes->fetch()) {
		$playernotes[$playernoteid][$notetype] = $notenote;
	}
	$getplayernotes->close();
	$pcid2un = userid2playername(); // Get the playerid mappings
	foreach ($playernotes as $arrayid => $typenotearray){
		print "<ul><li>$pcid2un[$arrayid]<br>";
		foreach ($typenotearray as $arraytype => $arraynotes) {
			print "<ul><li>$arraytype  
				<ul><li>$arraynotes</ul></ul>";
		}
		print "</ul>";
	}
}

if ($mode == 'karma') {
	print "Please Enter Karma<br>\n";
	$pcid2un = activeuserid2playername();
	print "<br><form action=\"index.php\" method=\"get\"><br>";
	foreach ($pcid2un as $id => $playername) {
		print "<li>Name: $playername<br>
			   karma: <input type=\"text\" name=\"karma[$id]\" length=\"8\"> | 
			   note: <input type=\"text\" name=\"knote[$id]\" length=\"48\"><br><br>"; 

	}
	print "<input type=\"hidden\" name=\"mode\" value=\"addkarma\">
		<input type=\"submit\" value=\"grant thee karma\">\n";
}

if ($mode == 'addkarma' && $karma && $knote) {
	$karmasql = $mysqli->prepare("INSERT INTO karma (playerid,date,karma,note,state) VALUES (?,NOW(),?,?,'C')");
	foreach ($karma as $id => $karmapoints) {
		if (!$karmapoints) { continue; };
		$karmasql->bind_param('sss',$id,$karmapoints,$knote[$id]);
		$karmasql->execute();
		print "Entered $id -> $karmapoints ({$knote[$id]})<br>";
		systemlog('addkarma',"$id recieved $karmapoints karma for {$knote[id]}");	
	}
}

include "../footer.php";
?> 
