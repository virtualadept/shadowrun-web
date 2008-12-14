<?
include "../header.php";

$mailtolist = ($_POST['mailtolist']);
$mailfrom = $mysqli->real_escape_string($_POST['mailfrom']);
$mode = $mysqli->real_escape_string($_POST['mode']);
$enc = $mysqli->real_escape_string($_POST['enc']);
$enckey = $mysqli->real_escape_string($_POST['enckey']);
$enckeycheck = $mysqli->real_escape_string($_POST['enckeycheck']);
$subject = $mysqli->real_escape_string($_POST['subject']);
$body = $mysqli->real_escape_string($_POST['body']);

if (!$mode) {
	print "compose a message:<br>";
	
	// Start the message form
	//
	print "<form action=\"compose.php\" method=\"post\">\n";
	if ($st == '1') {
		if ($getstplayers = $mysqli->prepare("SELECT id,playername FROM players WHERE username=?")) {
			$getstplayers->bind_param('s',$username);
			$getstplayers->execute();
			$getstplayers->bind_result($fromid,$fromplayername);
			print "from: <select name=\"mailfrom\">\n";
			while ($getstplayers->fetch()) {
				print "<option value=\"$fromid\">$fromplayername</option>\n";
			}
			print "</select><br>\n";
			$getstplayers->close();
		} 
	} else {
		print "from: $playername<br>\n";
		print "<input type=\"hidden\" name=\"mailfrom\" value=\"$userid\">\n";
	}
	print "to:  ";
	if ($getplayers = $mysqli->prepare("SELECT id,playername FROM players WHERE username<>? AND hidden<>'1'")) {
		$getplayers->bind_param('s',$username);
		$getplayers->execute();
		$getplayers->bind_result($id,$playername);
		while ($getplayers->fetch()){
			print "<input type=\"checkbox\" name=\"mailtolist[]\" value=\"$id\">  $playername<br>\n";
		}
	}
	print "encrypted: <input type=\"radio\" name=\"enc\" value=\"0\" checked> No | <input type=\"radio\" name=\"enc\" value=\"1\"> Yes <br>";
	print "encryption password: <input type=\"text\" name=\"enckey\"> | again: <input type=\"text\" name=\"enckeycheck\"><br><br><br>";
	print "subject: <input type=\"text\" name=\"subject\"><br>";
	print "message: <input type=\"textbox\" name=\"body\"><br>";
	print "<input type=\"hidden\" name=\"mode\" value=\"savemsg\">\n";
	print "<input type=\"submit\" value=\"Submit\">\n";
}

if (($mode == 'savemsg') && $mailfrom && $mailtolist && $subject && $body)  { // FIXME: check variables to see if they exist
	if ($enckey != $enckeycheck) { print "Double check your encryption key buddy<br>\n"; die; }
	if (!$enckey) { $enckey = "NULL"; }
	if (!$enc) { $enc = "0"; }
	if ($submsg = $mysqli->prepare("INSERT INTO mailmsg (date,mailfrom,mailto,enc,enckey,subject,body) VALUES (NOW(),?,?,?,?,?,?)")) {
		foreach ($mailtolist as $mailto) {
			print "mailto -> $mailto DONE!<br>";
			$submsg->bind_param('iiisss',$mailfrom,$mailto,$enc,$enckey,$subject,$body);
			$submsg->execute();
		}
	}
 
}

/*
+----------+--------------+------+-----+---------+----------------+
| Field    | Type         | Null | Key | Default | Extra          |
+----------+--------------+------+-----+---------+----------------+
| messid   | int(11)      | NO   | PRI | NULL    | auto_increment | 
| date     | datetime     | YES  |     | NULL    |                | 
| mailfrom | int(11)      | YES  |     | NULL    |                | 
| mailto   | int(11)      | YES  |     | NULL    |                | 
| enc      | char(1)      | YES  |     | NULL    |                | 
| enckey   | varchar(64)  | YES  |     | NULL    |                | 
| subject  | varchar(128) | YES  |     | NULL    |                | 
| body     | text         | YES  |     | NULL    |                | 
| mailread | int(1)       | YES  |     | 0       |                | 
+----------+--------------+------+-----+---------+----------------+
9 rows in set (0.04 sec)
*/
