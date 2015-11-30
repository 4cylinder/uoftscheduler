# This program crawls the Engineering timetable pages from U of T
# It extracts the listings and places them into .csv files. 
use strict;
use warnings;
use LWP::Simple;
use HTML::TableExtract;
use Text::CSV;

#this function would be called with either "fall" or "winter" as arguments
#It extracts the tables from the HTML pages and converts them to csv
sub apscparse {
	#semester = the only function argument
	my $sem = $_[0];
	#open fall.html or winter.html
	my $raw_html = do {
		open my $in, '<', "$sem.html"
		    or die "Can't open infile: $!\n";
		local $/ = undef;
		<$in>;
	};

	#We only want these five columns from the page. Details like location and prof
	#are less important. The main focus is timing.
	my $te = new HTML::TableExtract(headers => [qw(NAME SECTION MEET DAY START FINISH LOCATION)]);
	#this creates an array of tables, each table corresponding to one department
	#the tables are stored in memory first before being saved to csv
	$te->parse($raw_html);

	#Examine all resulting tables
	foreach my $ts ($te->tables) {
		#to avoid reference errors, copy the table into this local variable
		my @table = $ts->rows;
		#count number of rows in the table
		my $numrows = $#table+1;
		#loop through the table and analyze it
		for (my $i = 0; $i<$numrows; $i++) {
			my $name = $table[$i][0];
			my $sect = $table[$i][1];
			#strip out info like notes from the location cell
			$table[$i][6] =~ s/\ .*//g;
			$table[$i][6] =~ s/\r.*//g;
			$table[$i][6] =~ s/\n.*//g;
			$table[$i][6] =~ s/-.*//g;
			$table[$i][6] =~ s/\*.*//g;
			#if the section is blank, then we want to copy the value from above
			#would prefer to check if it's null/undef/zero
			#but those comparisons always seem to fail
			if (length($sect)<5){
				#check if the course code is identical to the row above
				if ($name eq $table[$i-1][0]){
					#if so, copy the section (LEC 01, PRA 02, etc), from the
					#above row into the current row.
					$table[$i][1] = $table[$i-1][1];
				}
			}
			#extract the department 3-letter code, e.g. ECE, CSC, MIE, etc
			my $dept = $table[0][0];
			$dept = substr $dept,0,3;
			#ignore the "course" marked PEY500Y1Y (why is it even there...)
			if ($dept ne "PEY"){
				#open (or create if not found) a csv file to store the timings
				#filenames are formatted like ECE_fall.csv, CSC_winter.csv, etc
				#they are stored in either the "fall" or "winter" subfolders
				open (FH, ">>$sem/$dept"."_$sem.csv") or die "$!";
				if ($i==0 || !(($table[$i][0] eq $table[$i-1][0]) && 
						($table[$i][1] eq $table[$i-1][1]) &&
						($table[$i][2] eq $table[$i-1][2]))){
					my @row = @{$table[$i]};
					splice @row,2,1;
					#output each row into the csv file
					print FH join( ',', @row ), "\n";
				}
			}
		}
	}
}

#call that major function from above twice
apscparse("fall");
apscparse("winter");
