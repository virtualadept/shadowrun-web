#!/usr/bin/perl
#
$|=1;

%categories;
%masterkeylist;
$tier1;
$tier2;
$tier3;
$tier4;
@categoryarray;

foreach $line (<STDIN>) {
	chomp($line);
	next if ($line =~ /^\!/);
	$line =~ s/\'//g;
	$line =~ s/\"//g;
	# Here we build the higharchy of the categories
	if ($line =~ /^1-[A-Z]/) { $tier1 = $line; $tier2 = undef; $tier3 = undef; $tier4 = undef; $tier5 = undef; $tier1 =~ s/1-//; next;}
	if ($line =~ /^2-\w/) { $tier2 = $line; $tier3 = undef; $tier4 = undef; $tier5 = undef; $tier2 =~ s/2-//; next;}
	if ($line =~ /^3-\w/) { $tier3 = $line; $tier4 = undef; $tier5 = undef; $tier3 =~ s/3-//; next;}
	if ($line =~ /^4-\w/) { $tier4 = $line; $tier5 = undef; $tier4 =~ s/4-//; next;}
	if ($line =~ /^5-\w/) { $tier5 = $line; $tier5 =~ s/5-//; next;}

	# Here we build the array for the main categories
	if ($line =~ /^0-\d/) {
		$line =~ s/\$//g;
		$line =~ s/\//_/g;
		$line =~ s/\./_/g;
		$line =~ s/\://g;
		$line =~ s/\'//g;
		# Originally we used an array, but arrays dont keep sort order
		#@categoryarray = split(/\|/,$line);
		#$categoryarray[0] =~ s/0-//g;
		#$cattitle=$categoryarray[0];
		#shift(@categoryarray);
		#shift(@categoryarray);
		#shift(@categoryarray);
		
		# Ghetoooooo
		$line =~ /^0-(\d+)/; 		# get catid
		$cattitle = $1;
		$line =~ s/^\d\-\d+\|//;	# take out 0-x
		$line =~ s/^(\s|\w)+\|//;	# take out category name
		$line =~ s/^\d+\|//;		# take out random |x| 
		$line =~ s/\|$//;		# take out ending |
		$line =~ s/\|/,/g;		# convert | to ,
		$categories{$cattitle} = "$line";
		next; 
	}
	
	# Here we build the array for the individual items
	if ($line =~ /^\d-\*/) {
		$line =~ s/\'//g;
		$line =~ s/^\w-\* //;	# take out 4-*
		$line =~ /\s(\d+)\|/;	# look for <space>#| for cat
		$itemtitle = $1;
		$line =~ s/\d+\|/\|/;	# take out <space>#
		$line =~ s/\s+\|/\|/;	# take out ending name spaces
		#$line =~ s/\s*./ /;	# take out long spaces FIXME
		$line =~ s/\d\|$/\|NULL\|NULL|/;
		$line =~ s/\?\|$/\|NULL\|NULL|/;
		$line =~ s/X\|$/\|NULL\|NULL|/;
		$line =~ s/\|$//;		# take out ending |
		$line =~ s/\,//;
		$line =~ s/\|/','/g;	# convert | to ,
		$line =~ s/\(//g;
		$line =~ s/\)//g;
		# Array hell
		#@itemarray = split(/\|/,$line);
		#$itemarray[0] =~ s/^\w-\* //;
		#$itemarray[0] =~ /\s(\d+)$/;
		#$itemtitle = $1;
		#$itemarray[0] =~ s/\s\d+$//;
		#$itemarray[0] =~ s/\s+$//;
		#print "insert into gear ($categories{$itemtitle}) values ($line)\n";
	

	$sqltitle = "INSERT INTO gear (";
	$sqlvalue = ") VALUES (";
	if ($tier1) { $sqltitle .= "index1, "; $sqlvalue .= "\'$tier1\', "; }	
	if ($tier2) { $sqltitle .= "index2, "; $sqlvalue .= "\'$tier2\', "; }	
	if ($tier3) { $sqltitle .= "index3, "; $sqlvalue .= "\'$tier3\', "; }	
	if ($tier4) { $sqltitle .= "index4, "; $sqlvalue .= "\'$tier4\', "; }	
	if ($tier4) { $sqltitle .= "index5, "; $sqlvalue .= "\'$tier5\', "; }	
	$sqltitle .= "name,";
	$sqltitle .= "$categories{$itemtitle}";
	$sqlvalue .= "'$line";

	print "$sqltitle $sqlvalue')\;\n";
}
}
if ($debugcats) {
		foreach $key (keys %categories) {
			print "$key -> " . join('|',split(/ /,$categories{$key})) . "\n";
			print "$key -> $categories{$key}\n";
			@allkeyarray = split(/ /,$categories{$key});
			foreach (@allkeyarray) {
				$masterkeylist{$_} = 1;
			}
		}
#		print "create table gear (gearid INT PRIMARY KEY AUTO_INCREMENT, index1 varchar(64), index2 varchar(64), index3 varchar(64), index4 varchar(64), index5 varchar(64), name varchar(64)";
#		foreach (keys %masterkeylist) {
#			print ", ";
#			print "$_ varchar(64)";
#		}
#		print ")";
}
