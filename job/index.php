<?
include "../header.php";

if (!$config[job]) {
	print "<br><br>this is currently deactivated<br><br>";
	systemlog('job_index','deactivated');
	exit;
}

// Generate new jobs if needed

mrjohnson();

// Active Job Postings
if (!$config[lockjob] || $st == '1') {
	print "<br><br><center>";
	printdots("searching for active jobs");
	print "</center>";
	if ($joblist = $mysqli->prepare("SELECT jobid,DATE_ADD(postdate, INTERVAL 70 YEAR),DATE_ADD(expdate, INTERVAL 70 YEAR),startpay,description,meetingplace,(SELECT count(*) FROM jobvote where job.jobid = jobvote.jobid) as votes FROM job WHERE postdate <= NOW() AND expdate >= NOW() AND completed = '0' ORDER BY postdate DESC")) {
		$joblist->execute();
		$joblist->bind_result($jobid,$postdate,$expdate,$pay,$description,$meetingplace,$votes);
		print "<center><table border = '1'>\n
				<caption><em>active job postings</em></caption>\n
				<tr><th>posting date<th>expiration date<th>pay<th>description<th>meeting place<th>votes\n";
		while ($joblist->fetch()) {
			print "<tr><th><a href=\"job.php?jobid=$jobid\">$postdate</a><td>$expdate<td>$pay<th>$description<th>$meetingplace<th>$votes\n";
		}
		print "</table></center>\n";
		$joblist->close();
	}
} 

// Comitted Jobs
if ($config[lockjob] || $st == '1') {
	print "<br><br><center>";
	printdots("searching for comitted jobs");
	print "</center>";
	if ($joblist = $mysqli->prepare("SELECT jobid,DATE_ADD(postdate, INTERVAL 70 YEAR),DATE_ADD(expdate, INTERVAL 70 YEAR),startpay,description,meetingplace FROM job WHERE jobid = ?")) {
		$joblist->bind_param('i',$config[lockjob]);
		$joblist->execute();
		$joblist->bind_result($jobid,$postdate,$expdate,$pay,$description,$meetingplace);
		print "<center><table border = '1'>\n
				<caption><em>You are committed to the following job</em></caption>\n
				<tr><th>posting date<th>expiration date<th>pay<th>description<th>meeting place\n";
		while ($joblist->fetch()) {
			print "<tr><th><a href=\"job.php?jobid=$jobid\">$postdate</a><td>$expdate<td>$pay<th>$description<th>$meetingplace\n";
		}
		print "</table></center>\n";
		$joblist->close();
	}
}

print "<br><br>\n";

// Completed Job Postings
print "<center>";
printdots("searching for completed jobs");
print "<center>";
if ($joblist = $mysqli->prepare("SELECT jobid,DATE_ADD(postdate, INTERVAL 70 YEAR),startpay,description,meetingplace FROM job WHERE completed = '1' ORDER BY postdate DESC")) {
	$joblist->execute();
	$joblist->bind_result($jobid,$postdate,$pay,$description,$meetingplace);
	print "<center><table border = '1'>\n
		<caption><em>completed jobs</em></caption>\n
		<tr><th>posting date<th>pay<th>description<th>meeting place\n";
	while ($joblist->fetch()) {
		print "<tr><th><a href=\"job.php?jobid=$jobid\">$postdate</a><td>$pay<th>$description<th>$meetingplace\n";
	}
	print "</table></center>\n";
	$joblist->close();
}

print "<br><br>\n";

// Expired Job Postings
print "<center>";
printdots("searching for expired jobs");
print "<center>";
if ($joblist = $mysqli->prepare("SELECT jobid,DATE_ADD(expdate, INTERVAL 70 YEAR),startpay,description,meetingplace FROM job WHERE expdate <= NOW() and completed = '0' ORDER BY postdate DESC")) {
	$joblist->execute();
	$joblist->bind_result($jobid,$expdate,$pay,$description,$meetingplace);
		print "<center><table border = '1'>\n
			<caption><em>expired (uncompleted) jobs</em></caption>\n
			<tr><th>expiration date<th>pay<th>description<th>meeting place\n";
	while ($joblist->fetch()) {
		print "<tr><th><a href=\"job.php?jobid=$jobid\">$expdate</a><td>$pay<th>$description<th>$meetingplace\n";
	}
	print "</table></center>\n";
	$joblist->close();
}



























include "../footer.php";
?>
