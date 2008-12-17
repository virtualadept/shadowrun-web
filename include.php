<?php
// Include the database handle
include "db.php";

// Since this area is 100% auth'd already, no point in cookie setting
$username = "{$_SERVER['PHP_AUTH_USER']}";

// Set userid and playername from auth'd username
if ($isst = $mysqli->prepare("SELECT st FROM players WHERE username=?")) {
	$isst->bind_param('s',$username);
	$isst->execute();
	$isst->bind_result($st);
	$isst->fetch();
	$isst->close();
}
if ($st == '1') {
	$playername = "storyteller (admin)";
	$partyname = "the ST brigade";
	$userid = '0';
} else {
	if ($usernameresult = $mysqli->prepare("SELECT id,playername FROM players WHERE username=?")) {
		$usernameresult->bind_param('s',$username);
		$usernameresult->execute();
		$usernameresult->bind_result($userid,$playername);
		$usernameresult->fetch();
		$usernameresult->close();
	}
}

// populate the config hash
if ($getconfig = $mysqli->prepare("SELECT k,v FROM config")) {
	$getconfig->execute();
	$getconfig->bind_result($configkey,$configvalue);
	while ($getconfig->fetch()) {
		$config[$configkey] = "$configvalue";
	}
	$getconfig->close();
} else {
	print "cannot pull config values";
	die;
}


// START FUNCTIONS!

// -=Dot Printer-Outer=-
// This prints dots with microsecond delay (to give the appearance of the system
// actually searching out the records.  Yeah, ghetto isnt it.
function printdots($msg) {
	global $st;
	$i=1;
	print "$msg";
	if ($st == '1') { return 1; }
	while ($i < rand(10,30)) {
		print ".";
		usleep(rand(200000,500000));
		$i++;
	}
	print "done!";
	usleep(900000);
}

// -=Job Generator=-
// When this is called, it generates random jobs and inserts them into the job
// table.  This sub just generates the job, it does not check to see if its needed
// to run.
function mrjohnson() {
	global $mysqli;
	
	// First we get the total amount of non-st characters
	$numplayerssql = $mysqli->query("SELECT COUNT(DISTINCT username) AS active FROM players WHERE st <> '1' AND username <> 'NULL' AND hidden = '0'"); 
	$numplayers = $numplayerssql->fetch_assoc();

	// We then find out how many unexpired/uncompleted jobs there are lined up in the database.
	$numactive = $mysqli->query("SELECT jobid FROM job WHERE expdate >= NOW() AND completed = '0'");
	$currentjobs = $numactive->num_rows;
	
	$desc = array("B&E","Courier","Hacking","Wetwork","BodyGuard","Intel","Kidnapping","TBA");
	$meeting = array("Bills Grill","The Hungry Byte","Aphex Center","The Loaded Round","TBA");
	$insertjob = $mysqli->prepare("INSERT INTO job (postdate,expdate,startpay,description,meetingplace,completed,comppay) VALUES (DATE_ADD(NOW(), INTERVAL ? DAY),DATE_ADD(NOW(), INTERVAL ? DAY),?,?,?,'0',?)");

	// We insert a number of jobs equal to the party number / 2 + 1 so that way when voting happens we dont have big ties.	
	$primer = '0'; // Forces one of the jobs generated to have today's date.
	$jobgencount = '0';
	while ($currentjobs < ceil(($numplayers[active]/ 2) + 1)) {
		$pay = rand(100,5000);
		if ($primer == '0') { 
			// Random start dates sometimes give nothing useful initally, we force a start date of now the first loop
			$starttimesql = '0';
		} else {
			$starttimesql = rand(0,5);
		}
		$primer = '1';
		$stoptimesql = rand(14,28);
		$comppay = ($pay * genmarkup());
		$insertjob->bind_param('iiissi',$starttimesql,$stoptimesql,$pay,$desc[array_rand($desc)],$meeting[array_rand($meeting)],$comppay);
		$insertjob->execute();
		$currentjobs++;
		$jobgencount++;
	}

	// Log if we actually did anything
	if ($jobgencount > 0) {
		systemlog('mrjohnson',"generated $jobgencount jobs");
	}
}

// -=Store Generation=-
// When this is called, it will check over all of the stores and see if new stuff needs to be
// added or deleted. Because we are dealing with multiple stores and the stores are going to be generated
// anew every 5 days, this sub will do everything vs just adding stuff like genjob() does.
function shopkeeper($daystoregen,$streetindex) {
	global $mysqli;
	// The store will only keep things that have been purchased.  To keep the table small
	// (plus we dont really care whats generated) it will purge unbought items that are older than
	// 5 days.
	$mysqli->query("DELETE FROM store WHERE DATEDIFF(NOW(),adddate) > 5 AND persistant='0'");
	if ($mysqli->affected_rows > '0') {
		$numdeleted = $mysqli->affected_rows;
		systemlog('shopkeeper_delete',"Deleted $numdeleted expired items from the shop");
	}

	// Set the shops with abbreviations here
	$shops = array( 'THT' => 'the hare trigger',
			'SYS' => 'save your skin',
			'TPP' => 'the pixelated pixel',
			'DN' => '/dev/null',
			'SBT' => 'apollo\'s shop of useful things',
			'YDN' => 'the negro peddle',);

	// Use the abberv to map what main category of stuff each shop has.
	// This is mysql format.
	$shopcat = array ('THT' => 'index1 = \'Weapons\' OR index1 = \'Ammunition\'',
			  'SYS' => 'index1 = \'Clothing and armor\'',
			  'TPP' => 'index1 = \'Lifestyle\'',
			  'DN' => 'index1 = \'Nanotechnology\'',
			  'SBT' => 'index1 LIKE \'%gear\'',
			  'YDN' => 'index1 LIKE \'%\'',);

	// How many of each do we want to populate the boards with?
	$shopstock = array('THT' => '30',
			   'SYS' => '15',
			   'TPP' => '10',
			   'DN' => '15',
			   'SBT' => '20',
		   	   'YDN' => '5',);

	// Whats the base street index?
	$shopindex = array('THT' => "<= $streetindex",
			   'SYS' => "<= $streetindex",
			   'TPP' => "<= $streetindex",
			   'DN' => "<= $streetindex",
			   'SBT' => "<= $streetindex",
			   'YDN' => "> $streetindex AND street_index <=" . ($streetindex + 2),);
	
	// Whats the max soh they carry? 
	$shopmaxsoh = array('THT' => '4',
			   'SYS' => '3',
			   'TPP' => '3',
			   'DN' => '4',
			   'SBT' => '3',
			   'YDN' => '1',);

	// Now the mayhem begins
	$shopkey;
	foreach (array_keys($shops) as $shopkey) {
		$logcounter[$shopkey] = 0;
		$stockquery = $mysqli->query("SELECT count(*) AS invcount FROM store WHERE storename = '$shopkey'");
		$currentstock = $stockquery->fetch_assoc();
		while ($currentstock['invcount'] < $shopstock[$shopkey]) {
			// For some reason prepared statements doesnt like mysql in placeholder values (duh)
			$gensoh = rand(1,$shopmaxsoh[$shopkey]); // Gen random stock on hand
			$shopquery = "INSERT INTO store (storename,gearid,adddate,persistant,soh) VALUES ('$shopkey' , (SELECT gearid FROM gear WHERE $shopcat[$shopkey] AND street_index $shopindex[$shopkey] ORDER BY rand() LIMIT 1) , NOW(), '0', \"$gensoh\" )";
			$mysqli->query("$shopquery"); 
			$currentstock['invcount']++;
			$logcounter[$shopkey]++;

		}
	}

	// Record what we added into the system log
	foreach (array_keys($logcounter) as $logrecord) {
		if ($logcounter[$logrecord] == '0') { break; }
		$logquery = "INSERT INTO systemlog (startdate,program,msg) VALUES (NOW(),'shopkeeper_insert',\"Inserted " .  $logcounter[$logrecord] . " for $logrecord\")";
		$mysqli->query("$logquery");
	}
	
	// We'll need this later.  Export it now
	return $shops;
}

// -=System Logger=-
// We use this to log actions that we want to keep for archival purposes (item purchases, etc).
// This is not really meant to parse for webpages, but to keep as an archive
//
function systemlog($program,$msg) {
	global $mysqli;
	global $username;
	if (!$username) { $username = 'AUTOBOT'; }
	if ($logaction = $mysqli->prepare("INSERT INTO systemlog (startdate,program,msg,who) VALUES (NOW(),?,?,?)")) {
		$logaction->bind_param('sss',$program,$msg,$username);
		$logaction->execute();
	}
}
	
// -=Generate Markup=-
function genmarkup() {
	$markup = '1.50'; // Everything starts at 150% of base price
	if ((date("m") % '2') == '0') {
		        $markup += '.225';
	} else {
		        $markup += '.35';
	}
	if ((date("d") % '5') == '0') {
		        $markup -= '.20';
	} else {
		        $markup -= '.10';
	}
	if ((date("d") % '7') == '0') {
		        $markup -= '.35';
	} else {
		        $markup -= '.20';
	}
	return $markup;
}

// -=Config Management=-
function setconfig($k,$v) {
	global $mysqli;
	if ($setconfig = $mysqli->prepare("INSERT INTO config (k,v) VALUES (?,?)")) {
		$setconfig->bind_param('ss',$k,$v);
		$setconfig->execute();
	}
}

function updateconfig($k,$v) {
	global $mysqli;
	if ($updateconfig = $mysqli->prepare("UPDATE config SET v=? WHERE k=?")){
		$updateconfig->bind_param('ss',$v,$k);
		$updateconfig->execute();
	}
}

function delconfig($k) {
	global $mysqli;
	if ($delconfig = $mysqli->prepare("DELETE FROM config WHERE k=?")) {
		$delconfig->bind_param('s',$k);
		$delconfig->execute();
	}
}

// -=Bank Account Management=- 
function updatebank($amt,$note,$account,$purchaser) {
	global $mysqli;
	if ($updatebank = $mysqli->prepare("INSERT INTO bank (transamt,transnote,date,accountid,purchaser) 
		VALUES (?,?,NOW(),?,?)")) {
		$updatebank->bind_param('isii',$amt,$note,$account,$purchaser);
		$updatebank->execute();
	}
}


function cashonhand() {
	global $mysqli;
	if ($accounttotalsql = $mysqli->query("SELECT accountid,SUM(transamt) AS cash FROM bank GROUP BY accountid")) {
		while ($accounttotalout = $accounttotalsql->fetch_assoc()) {
               		$coh[$accounttotalout[accountid]] = $accounttotalout[cash];
        	}
		return $coh;
	}
}

// -=Player Table Management=-
function userid2playername() {
	global $mysqli;
	if ($getplayername = $mysqli->prepare("SELECT id,playername FROM players")) {
        	$getplayername->execute();
        	$getplayername->bind_result($userid,$plrname);
        	$uid2pn['0'] = "Storyteller"; // Hard code this one
        	while ($getplayername->fetch()) {                                                                              
                	$uid2pn[$userid] = "$plrname";                                                                         
       		}                                                                                                              
        	$getplayername->close();
	}                                                                               
	return $uid2pn;
}                                                                                                                      
         
function getnpcid() {
	global $mysqli;
	if ($getnpcid = $mysqli->prepare("SELECT id,playername FROM players WHERE st = '1'")) {
		$getnpcid->execute();
		$getnpcid->bind_result($npcid,$npcname);
		while ($getnpcid->fetch()) {
			$npc[$npcid] = $npcname;
		}
	}
	return $npc;
}


// -=-=-=-=-=-=-=- END OF MY CODE -=-=-=-=-=-=-=-=-=-

// Begin Crypto Class
// This is put in here so we wouldn't have to rely on an external
// library.

// ******************************************************************************
// A reversible password encryption routine by:
// Copyright 2003-2007 by A J Marston <http://www.tonymarston.net>
// Distributed under the GNU General Public Licence
// Modification: May 2007, M. Kolar <http://mkolar.org>:
// No need for repeating the first character of scramble strings at the end;
// instead using the exact inverse function transforming $num2 to $num1.
// ******************************************************************************

class encryption_class {

    var $scramble1;     // 1st string of ASCII characters
    var $scramble2;     // 2nd string of ASCII characters

    var $errors;        // array of error messages
    var $adj;           // 1st adjustment value (optional)
    var $mod;           // 2nd adjustment value (optional)

    // ****************************************************************************
    // class constructor
    // ****************************************************************************
    function encryption_class ()
    {
        $this->errors = array();

        // Each of these two strings must contain the same characters, but in a different order.
        // Use only printable characters from the ASCII table.
        // Do not use single quote, double quote or backslash as these have special meanings in PHP.
        // Each character can only appear once in each string.
        $this->scramble1 = '! #$%&()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[]^_`abcdefghijklmnopqrstuvwxyz{|}~';
        $this->scramble2 = 'f^jAE]okIOzU[2&q1{3`h5w_794p@6s8?BgP>dFV=m D<TcS%Ze|r:lGK/uCy.Jx)HiQ!#$~(;Lt-R}Ma,NvW+Ynb*0X';

        if (strlen($this->scramble1) <> strlen($this->scramble2)) {
            trigger_error('** SCRAMBLE1 is not same length as SCRAMBLE2 **', E_USER_ERROR);
        } // if

        $this->adj = 1.75;  // this value is added to the rolling fudgefactors
        $this->mod = 3;     // if divisible by this the adjustment is made negative

    } // constructor

    // ****************************************************************************
    function decrypt ($key, $source)
    // decrypt string into its original form
    {
        $this->errors = array();

        // convert $key into a sequence of numbers
        $fudgefactor = $this->_convertKey($key);
        if ($this->errors) return;

        if (empty($source)) {
            $this->errors[] = 'No value has been supplied for decryption';
            return;
        } // if

        $target = null;
        $factor2 = 0;

        for ($i = 0; $i < strlen($source); $i++) {
            // extract a character from $source
            $char2 = substr($source, $i, 1);

            // identify its position in $scramble2
            $num2 = strpos($this->scramble2, $char2);
            if ($num2 === false) {
                $this->errors[] = "Source string contains an invalid character ($char2)";
                return;
            } // if

            // get an adjustment value using $fudgefactor
            $adj     = $this->_applyFudgeFactor($fudgefactor);

            $factor1 = $factor2 + $adj;                 // accumulate in $factor1
            $num1    = $num2 - round($factor1);         // generate offset for $scramble1
            $num1    = $this->_checkRange($num1);       // check range
            $factor2 = $factor1 + $num2;                // accumulate in $factor2

            // extract character from $scramble1
            $char1 = substr($this->scramble1, $num1, 1);

            // append to $target string
            $target .= $char1;

            //echo "char1=$char1, num1=$num1, adj= $adj, factor1= $factor1, num2=$num2, char2=$char2, factor2= $factor2<br />\n";

        } // for

        return rtrim($target);

    } // decrypt

    // ****************************************************************************
    function encrypt ($key, $source, $sourcelen = 0)
    // encrypt string into a garbled form
    {
        $this->errors = array();

        // convert $key into a sequence of numbers
        $fudgefactor = $this->_convertKey($key);
        if ($this->errors) return;

        if (empty($source)) {
            $this->errors[] = 'No value has been supplied for encryption';
            return;
        } // if

        // pad $source with spaces up to $sourcelen
        while (strlen($source) < $sourcelen) {
            $source .= ' ';
        } // while

        $target = null;
        $factor2 = 0;

        for ($i = 0; $i < strlen($source); $i++) {
            // extract a character from $source
            $char1 = substr($source, $i, 1);

            // identify its position in $scramble1
            $num1 = strpos($this->scramble1, $char1);
            if ($num1 === false) {
                $this->errors[] = "Source string contains an invalid character ($char1)";
                return;
            } // if

            // get an adjustment value using $fudgefactor
            $adj     = $this->_applyFudgeFactor($fudgefactor);

            $factor1 = $factor2 + $adj;             // accumulate in $factor1
            $num2    = round($factor1) + $num1;     // generate offset for $scramble2
            $num2    = $this->_checkRange($num2);   // check range
            $factor2 = $factor1 + $num2;            // accumulate in $factor2

            // extract character from $scramble2
            $char2 = substr($this->scramble2, $num2, 1);

            // append to $target string
            $target .= $char2;

            //echo "char1=$char1, num1=$num1, adj= $adj, factor1= $factor1, num2=$num2, char2=$char2, factor2= $factor2<br />\n";

        } // for

        return $target;

    } // encrypt

    // ****************************************************************************
    function getAdjustment ()
    // return the adjustment value
    {
        return $this->adj;

    } // setAdjustment

    // ****************************************************************************
    function getModulus ()
    // return the modulus value
    {
        return $this->mod;

    } // setModulus

    // ****************************************************************************
    function setAdjustment ($adj)
    // set the adjustment value
    {
        $this->adj = (float)$adj;

    } // setAdjustment

    // ****************************************************************************
    function setModulus ($mod)
    // set the modulus value
    {
        $this->mod = (int)abs($mod);    // must be a positive whole number

    } // setModulus

    // ****************************************************************************
    // private methods
    // ****************************************************************************
    function _applyFudgeFactor (&$fudgefactor)
    // return an adjustment value  based on the contents of $fudgefactor
    // NOTE: $fudgefactor is passed by reference so that it can be modified
    {
        $fudge = array_shift($fudgefactor);     // extract 1st number from array
        $fudge = $fudge + $this->adj;           // add in adjustment value
        $fudgefactor[] = $fudge;                // put it back at end of array

        if (!empty($this->mod)) {               // if modifier has been supplied
            if ($fudge % $this->mod == 0) {     // if it is divisible by modifier
                $fudge = $fudge * -1;           // make it negative
            } // if
        } // if

        return $fudge;

    } // _applyFudgeFactor

    // ****************************************************************************
    function _checkRange ($num)
    // check that $num points to an entry in $this->scramble1
    {
        $num = round($num);         // round up to nearest whole number

        $limit = strlen($this->scramble1);

        while ($num >= $limit) {
            $num = $num - $limit;   // value too high, so reduce it
        } // while
        while ($num < 0) {
            $num = $num + $limit;   // value too low, so increase it
        } // while

        return $num;

    } // _checkRange

    // ****************************************************************************
    function _convertKey ($key)
    // convert $key into an array of numbers
    {
        if (empty($key)) {
            $this->errors[] = 'No value has been supplied for the encryption key';
            return;
        } // if

        $array[] = strlen($key);    // first entry in array is length of $key

        $tot = 0;
        for ($i = 0; $i < strlen($key); $i++) {
            // extract a character from $key
            $char = substr($key, $i, 1);

            // identify its position in $scramble1
            $num = strpos($this->scramble1, $char);
            if ($num === false) {
                $this->errors[] = "Key contains an invalid character ($char)";
                return;
            } // if

            $array[] = $num;        // store in output array
            $tot = $tot + $num;     // accumulate total for later
        } // for

        $array[] = $tot;            // insert total as last entry in array

        return $array;

    } // _convertKey

// ****************************************************************************
} // end encryption_class
// ****************************************************************************

?>
