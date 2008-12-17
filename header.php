<?  include "include.php";
ob_implicit_flush();

print "<HTML>\n
	<HEAD>\n
	<TITLE>" .  $config[sitename] .  "</TITLE>\n
	<link rel=\"stylesheet\" href=\"" . $config[siteroot] . "default.css\" type=\"text/css\">\n
	<link rel=\"stylesheet\" href=\"" . $config[siteroot] . "shadowrun.css\" type=\"text/css\">\n
	<link rel=\"stylesheet\" href=\"" . $config[siteroot] . "ajax.css\" type=\"text/css\">\n
	<script src=\"{$config[siteroot]}ajax.js\" type=\"text/javascript\">
</script>

	</HEAD>\n
	<BODY BGCOLOR=\"#000000\" TEXT=\"#33FF33\">\n
	<TT>\n";

if (!$playername) { 
	print "<center>" . $config['sitename'] . "<br><h1>ACCESS DENIED</h1></center>"; 
	systemlog('auth_gatekeeper',"$username - ACCESS DENIED");
	exit; 
} 

if ($st != '1' && $config[sitedown] == '1') {
	print "<center>" . $config[sitename] . "<br><h1>Site Offline (please come again!) </h1></center>";
	systemlog('auth_gatekeeper',"$username - Site Offline");
	exit;
}

print "<div class=top>\n
	<p>" . $config[sitename] . "</p>
	</div>\n
	you are logged in as $playername\n<br><br>
	<div class=navmenu>\n
	<ul>\n";
// Autogenerate the meny depending on whats active
if ($config[activate]) { print "<li><a href=\"$config[siteroot]activate/\">Comlink Activation Module</a><li>\n"; }
if ($config[message]) { print "<li><a href=\"$config[siteroot]inbox/\">Message Module</a><li>\n"; }
if ($config[bank]) { print "<li><a href=\"$config[siteroot]bank/\">Bank Module</a><li>\n"; }
if ($config[shop]) { print "<li><a href=\"$config[siteroot]shop/\">Store Module</a><li>\n"; }
if ($config[job]) { print "<li><a href=\"$config[siteroot]job/\">Job Module</a><li>\n"; }
print "<li><a href=\"$config[siteroot]karma/\">Karma Module</a><li>\n"; 

print "</ul>\n
	</div>\n";
?>
