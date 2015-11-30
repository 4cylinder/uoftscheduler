# This program crawls the Arts and Science timetable pages from U of T
# It extracts the listings and places them into .csv files.
# It is considerably more tedious than crawling the Engineering timetable pages
use strict;
#use warnings;
use LWP::Simple;
use HTML::TableExtract;
use Text::CSV;
use Date::Parse;

#This function will parse the ArtSci time cells, which are formatted like 
#MW10 (mon, wed from 10-11), or MTF9-12 (mon, tue, fri from 9-12), etc
#It will also do the actual output-to-csv for each row in the table.
sub parsetime {
	#the second argument is a table row
	my @row = @{$_[1]};
	#extract the 3-letter department code (e.g. CSC, HPS, GGR, etc)
	my $dept = $row[0];
	$dept = substr $dept,0,3;
	#check first argument to determine semester (fall or winter)
	my $sem = $_[0];
	#filenames are formatted like ECE_fall.csv, CSC_winter.csv, etc
	#they are stored in either the "fall" or "winter" subfolders
	open (FH, ">>$sem/$dept"."_$sem.csv") or die "$!";
	
	#some courses have an a.m. specification. Watch out for this
	my $flag = 0;
	if (index($row[3],"a.")>0){
		$flag = 1;
	}
	
	#some cells have extra notes like (t) or (A) in them
	#they aren't needed for the purposes of this program and get in the way
	#so just strip those substrings out to make life easier
	$row[3] =~ s/\(.*//g;
	$row[3] =~ s/\ \(.*//g;
	$row[3] =~ s/<br \/>.*//g;
	$row[3] =~ s/\n.*//g;
	$row[3] =~ s/\r.*//g;
	$row[3] =~ s/a\..*//g;
	$row[3] =~ s/p.*//g;

	#make sure the row is valid to parse (course isn't TBA or canceled)
	if (length($row[3])>1 && uc($row[3]) ne "TBA" && index($row[3],',')<0){
		#extract the MTWRF alphabets (the days)
		my $dayz = $row[3];
		#strip all non-alpha characters
		$dayz =~ s/[\d-:]//g;
		#extract the hours, e.g. pull out "10-12" from MWF10-12
		my $time = $row[3];
		#strip all the leading alphabets
		$time =~ s/[M|T|W|R|F]//g;
		#convert the days into an array of letters that can be looped through
		my @days = split(undef,$dayz);
		#convert the hours into an array of numbers that can be looped through
		my @hours = split('-',$time);
		#extract the start and end times
		my $start = $hours[0];
		my $end = "";
		#integer number of hours
		if (index($row[3],':')<0){
			#course section lasts only one hour, so $end = $start+1
			$end = $start+1;
			#but if there's more than one number, $end = whatever the 2nd number is
			if ($#hours){
				$end = $hours[1];
			}
			#convert to 24-hour time for both $start and $end. 
			#Then the PHP can do proper comparisons
			if ($start<10 && $flag==0){
				if($end>$start && $end<10){
					$start+=12;
					$end+=12;
				}
			}
			if ($start<8 && $flag==0){
				if($end>$start && $end<11){
					$start+=12;
					$end+=12;
				}
			}
			if ($end<$start){
				$end+=12;
			}

			if ($start>=$end || $end>22){
				print "error ${row[0]} $start $end\n";
			}
			$start = "$start:00";
			$end = "$end:00";
		}
		#hours like x:30, x:15, etc
		else {
			my @s = strptime($start);
			#sometimes start is an integer hour, but end isn't
			if (index($hours[0],':')<0){
				$s[2] = $hours[0];
			}
			my @e = @s;
			#course section lasts only one hour, so $e[2] = $s[2]+1
			$e[2]+=1;
			#but if there's more than one number, $end = whatever the 2nd number is
			if ($#hours){
				if (index($hours[1],':')>0){
					@e = strptime($hours[1]);
				}
				#sometimes start isn't an integer hour, but end is
				else{
					$e[2] = $hours[1];
				}
			}
			#convert to 24-hour time for both $s[2] and $e[2]. 
			#Then the PHP can do proper comparisons
			if ($s[2]<10 && $flag==0){
				if($e[2]>$s[2] && $e[2]<10){
					$s[2]+=12;
					$e[2]+=12;
				}
			}
			if ($s[2]<8 && $flag==0){
				if($e[2]>$s[2] && $e[2]<11){
					$s[2]+=12;
					$e[2]+=12;
				}
			}
			if ($e[2]<$s[2]){
				$e[2]+=12;
			}
			if ($s[2]>=$e[2] || $e[2]>22){
				print "error ${row[0]} ${s[2]} ${e[2]}\n";
			}
			if (!$e[1]){
				$e[1] = "00";
			}
			if (!$s[1]){
				$s[1] = "00";
			}
			$start = "${s[2]}:${s[1]}";
			$end = "${e[2]}:${e[1]}";
		}
		#now scan each capital alphabet to determine the day of the week
		foreach my $day (@days){
			#first output the course code (e.g. ECO100Y1Y), followed by the 
			#meeting section (e.g. LEC 01), to the csv file
			print FH $row[0].$row[1],',',$row[2],',';
			#now output the day, start time, and end time to the csv file
			if ($day eq 'M'){
				print FH "Mon",',',"$start",',',"$end";
			}
			elsif ($day eq 'T'){
				print FH "Tue",',',"$start",',',"$end";
			}
			elsif ($day eq 'W'){
				print FH "Wed",',',"$start",',',"$end";
			}
			elsif ($day eq 'R'){
				print FH "Thu",',',"$start",',',"$end";
			}
			elsif ($day eq 'F'){
				print FH "Thu",',',"$start",',',"$end";
			}
			print FH ',',$row[4],"\n";
		}
	}
}

sub artsciparse {
	my $file = $_[0];
	#Extract the tables from the HTML page to copy into memory
	my $raw_html = do {
		open my $in, '<', "$file"
		    or die "Can't open infile: $!\n";
		local $/ = undef;
		<$in>;
	};

	#We only want these four columns from the page. Details like location and prof
	#are less important. The main focus is timing.
	my $te = new HTML::TableExtract(headers => [qw(Course SC Meeting\nSection Time Location)]);
	#this creates a single table, unlike the multiple tables created 
	#when parsing engineering pages, because there's only ONE unique
	#header on the page this time.
	#the tables are stored in memory first before being saved to csv
	$te->parse($raw_html);

	# Examine the resulting table
	my $ts = ($te->tables)[0];
	#to avoid reference errors, copy the table into this local variable
	my @table = $ts;
	if ($ts){
		@table = $ts->rows;
	}
	#count number of rows in the table
	my $numrows = $#table+1;
	
	#loop through the table and analyze it
	for (my $i = 0; $i<$numrows; $i++) {
		my $name = $table[$i][0];
		my $sem = $table[$i][1];
		$table[$i][2]= substr $table[$i][2],0,5;
		#strip out info like notes from the location cell
		$table[$i][4] =~ s/\r.*//g;
		$table[$i][4] =~ s/\n.*//g;
		my $sect = $table[$i][2];
		#if the name/section is blank, then we want to copy the value from above
		#would prefer to check if it's null/undef/zero 
		#but those comparisons always seem to fail
		if (length($name)<7){
			#copy the code and semester (in artsci, the F/S/Y part is separate)
			$table[$i][0] = $table[$i-1][0];
			$table[$i][1] = $table[$i-1][1];
			#if blank, copy the section (LEC 01, PRA 02, etc), from the
			#above row into the current row.
			if (length($sect)<4) {
				$table[$i][2] = $table[$i-1][2];
			}
		}
		
	}
	#finally, loop through the reformatted table and output each row to CSV
	foreach my $row (@table) {
		if (@$row[1] eq 'F' || @$row[1] eq 'Y'){
			#got to account for some stupid comma cases (one cell had the value "T3, R12")
			if(index(@$row[3],',')>0){
				my @arr = split(/, /, @$row[3]);
				foreach my $x (@arr){
					$x =~ s/\n//g;
					@$row[3] = $x;
					parsetime("fall",\@$row);
				}
			}
			else{
				#strip out trailing whitespace in case some idiot put it there
				@$row[3] =~ s/\ //g;
				parsetime("fall",\@$row);
			}
		}
		if (@$row[1] eq 'S' || @$row[1] eq 'Y'){
			#got to account for some stupid comma cases (one cell had the value "T3, R12")
			if(index(@$row[3],',')>0){
				my @arr = split(/, /, @$row[3]);
				foreach my $x (@arr){
					$x =~ s/\n//g;
					@$row[3] = $x;
					parsetime("winter",\@$row)
				}
			}
			else {
				#strip out trailing whitespace in case some idiot put it there
				@$row[3] =~ s/\ //g;
				parsetime("winter",\@$row);
			}
		}
	}
}

#parse all the downloaded files from the artsci page
my @files = <artsci/*.html>;
foreach my $file (@files) {
	artsciparse($file);
}

