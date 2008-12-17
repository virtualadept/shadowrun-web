<? 
include "../header.php";

if (!$config[job]) {
	print "<br><br>ACCESS DENIED<br><br>";
	systemlog('job_job','Deactivated');
	exit;
}

$jobid = $mysqli->real_escape_string($_GET['jobid']);
$mode = $mysqli->real_escape_string($_GET['mode']);
$newstartpay = $mysqli->real_escape_string($_GET['newstartpay']);
$newcomppay = $mysqli->real_escape_string($_GET['newcomppay']);

// Here we verify if we can actually look at the job, or if someones just incrementing the jobid to see
// future jobs.  While we're at it we can pull all the job info we need in one fell swoop
if ($jobinfo = $mysqli->prepare("SELECT DATE_ADD(postdate, INTERVAL 70 YEAR),DATE_ADD(expdate, INTERVAL 70 YEAR),startpay,comppay,description,meetingplace FROM job WHERE postdate <= NOW() AND expdate >= NOW() AND jobid=?")) {
	$jobinfo->bind_param('i',$jobid);
	$jobinfo->execute();
	$jobinfo->bind_result($startdate,$enddate,$startpay,$comppay,$desc,$meeting);
	$jobinfo->fetch();
	$jobinfo->close();
} 


// Sorta need a jobid to continue
if (!$jobid) {
	print "Sorta mising something here buddy";
	systemlog('job_view',"tried to view page without jobid!");
	exit;
}

// Bail out and log if someone is trying to pull a fast one here
// Obviously if these are empty, the jobid didn't match up.
if (!$startdate && !$enddate && !$desc) {
	print "your unauthorized access has been logged";
	systemlog('job_view', "tried to view $jobid that isn't active");
	exit;
}


// Here we assume that the jobid is legit, and the person looking at it
// is authorized to do so.

// Cast the vote only if the player is not in the middle of a job AND if they have not voted already.
if ($mode == 'vote' && $jobid) {
	if ($config[lockjob]) {
		print "You are comitted to a job already, you cannot vote for another one";
		exit;
	}
	if ($config["voted-$userid"]) {
		print "You have already casted your vote.  You need to wait until next downtime to vote again";
		exit;
	} else {
		print "You have casted your vote for $jobid<br><br>";
		if ($placevote = $mysqli->prepare("INSERT INTO jobvote (userid,jobid,date) VALUES (?,?,NOW())")) {
			$placevote->bind_param('ss',$userid,$jobid);
			$placevote->execute();
			$placevote->close();
			setconfig("voted-$userid","$jobid");
			systemlog('job_vote',"$userid voted for $jobid");
			printdots("Processing..........");
			print "<br>done!";
		}
		exit;
	}
}


// By comitting to the job we give the players the cash, then lock from voting
if ($st && $mode == 'start' && $jobid && !$config[lockjob]) {
	print "starting $jobid<br>";
	setconfig('lockjob',"$jobid");
	print "Locked the job<br>";
	updatebank($startpay,"Advance pay for job <a href=\"../job/job.php?jobid=$jobid\">$jobid</a>",'0','0'); 
	print "Added Funds<br>";
	systemlog('job_start',"players comitted to $jobid");
	exit;
}

// Start the ST area where we can mark jobs as completed and give them $$$
if ($st && $mode == 'comp' && $jobid && $config[lockjob]) {
	print "comitting $jobid to bank account";
	delconfig('lockjob');
	print "Unlocked the job page<br>";
	updatebank($comppay,"Completed pay for job <a href=\"../job/job.php?jobid=$jobid\">$jobid</a>",'0','0'); 
	print "Added Funds<br>";
	$updatejob = $mysqli->query("UPDATE job SET completed='1' WHERE jobid=$jobid");
	systemlog('job_comp',"players completed $jobid");
	exit;
}

if ($st && $mode == 'updatepay' && $newstartpay && $newcomppay) {
	print "Updating pay for $jobid<br>\n";
	if ($updatepay = $mysqli->prepare("UPDATE job SET startpay=?,comppay=? WHERE jobid=?")) {
		$updatepay->bind_param('iii',$newstartpay,$newcomppay,$jobid);
		$updatepay->execute();
		$updatepay->close();
		systemlog('job_updatepay',"job $jobid altered start:$newstartpay comp:$newcomppay");
		print "Done!<br>\n";
	}
	exit;
}

if ($st) {
	if (!$config[lockjob]) {
		print "<a href=\"job.php?jobid=$jobid&mode=start\">START</a> <br><br>";
	}
	if ($config[lockjob] && $config[lockjob] == $jobid) {
		print "<a href=\"job.php?jobid=$jobid&mode=comp\"> COMPLETED </a> <br><br>";
	}
}

// This needs a ton of work.  Sloppy Sloppy Sloppy
print "jobid: $jobid<br>
	postdate: $startdate<br>
	enddate: $enddate<br>";

if ($st) {
	print "<form action=\"job.php\" method=\"get\">
		startpay: <input type=\"text\" name=\"newstartpay\" value=\"$startpay\"><br>
		endpay : <input type=\"text\" name=\"newcomppay\" value=\"$comppay\"><br>
		<input type=\"hidden\" name=\"mode\" value=\"updatepay\">
		<input type=\"hidden\" name=\"jobid\" value=\"$jobid\">
		<input type=\"submit\" value=\"update pay\"><br><br>";
} else {
	print "pay: $startpay<br>";
}
print 	"description: $desc<br>
	meeting place: $meeting<br>";
if ($config["voted-$userid"] == "$jobid") { // Let them know they voted for this job.
	print "***you voted for this job***";
}
print 	"<br><br><br><a href='index.php'>go back</a><br>";

if (!$config["voted-$userid"]) {
	print "<a href='job.php?jobid=$jobid&mode=vote'>vote</a>";
}























include "../footer.php";
?>
