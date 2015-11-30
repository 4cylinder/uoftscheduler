import urllib2, os
from BeautifulSoup import *
from time import *
import re

#grab the links to the individual sites from the main site
def getlinks():
	mainsite=urllib2.urlopen("http://www.artsandscience.utoronto.ca/ofr/timetable/winter/sponsors.htm")
	soup=BeautifulSoup(mainsite.read())
	links = []
	for link in soup.findAll('a'):
		if link.has_key('href') and link['href'][0:4]!="http" and link['href'][0:4]!="mail":
			links.append("http://www.artsandscience.utoronto.ca/ofr/timetable/winter/"+link['href'])
	return links

#convert time to a readable format
def converttime(time):
	flag = 0
	if time.find("a.")>0:
		flag = 1;
	s=0
	e=0
	start = time.split('-')[0]
	try:
		end = time.split('-')[1]
	except:
		if start==start.split(':')[0]:
			end = str(int(start)+1)
		else:
			s = strptime(start,"%I:%M")
			end = str(s.tm_hour+1)+':'+str(s.tm_min)
	#get s and e as integer hours
	if start==start.split(':')[0]:
		s = int(start)
	else:
		s = strptime(start,"%I:%M").tm_hour
	if end==end.split(':')[0]:
		e = int(end)
	else:
		e = strptime(end,"%I:%M").tm_hour
	#convert to 24 hours
	if s<10 and flag==0:
		if e>s and e<10:
			s = s+12
			e = e+12
	if s<8 and flag==0:
		if e>s and e<11:
			s = s+12
			e = e+12
	if e<s:
		e = e+12
	if s>=e or e>22:
		print "error"+s+e
	#now convert back to string
	if start==start.split(':')[0]:
		start = str(s)+":00"
	else:
		start = str(s)+":"+str(strptime(start,"%I:%M").tm_min)
	if end==end.split(':')[0]:
		end = str(e)+":00"
	else:
		end = str(e)+":"+str(strptime(end,"%I:%M").tm_min)
	
	return {0:start,1:end}

#output row to csv, very important to convert the time part first
def outputtocsv(row,sem):
	#day codes
	daymapping = {'M':"Mon",'T':"Tue",'W':"Wed",'R':"Thu",'F':"Fri"}
	#some courses have an a.m. specification. Watch out for this
	flag = 0
	row[2] = row[2].split('\r')[0]
	row[2] = row[2].split('\n')[0]
	row[2] = row[2].split('(')[0]
	#no commas - easier scenario by a long shot
	if row[2]==row[2].split(',')[0]:
		row[2] = row[2].split(' ')[0]
		#strip out anything that's not M,T,W,R,or F
		days = re.sub('[^M|T|W|R|F]+', '', row[2])
		#strip out the opposite of above
		time = re.sub('[M|T|W|R|F]+', '', row[2])
		for day in list(days):
			day = daymapping[day]
			time1 = converttime(time)
			start = time1[0]
			end = time1[1]
			line = row[0]+','+row[1]+','+day+','+start+','+end+','+row[3]
			#get department code, e.g. it is CSC if the course is CSC180H1F
			dept = row[0][0:3]
			#open (or create if not found) a csv file to store the timings
			#filenames are formatted like ECE_fall.csv, CSC_winter.csv, etc
			#they are stored in either the "fall" or "winter" subfolders
			f = open(sem+"/"+dept+'_'+sem+".csv",'a')
			f.write(line+'\n')
			f.close()
	#some idiot put the cell as something like M10,T2 
	#instead of having 2 separate rows (probably to make life hard)
	else: 
		#split on the comma
		slots = row[2].split(', ')
		for slot in slots:
			days = re.sub('[^M|T|W|R|F]+', '', slot)
			time = re.sub('[M|T|W|R|F]+', '', slot)
			for day in list(days):
				day = daymapping[day]
				time1 = converttime(time)
				start = time1[0]
				end = time1[1]
				line = row[0]+','+row[1]+','+day+','+start+','+end+','+row[3]
				#get department code, e.g. it is CSC if the course is CSC180H1F
				dept = row[0][0:3]
				#open (or create if not found) a csv file to store the timings
				#filenames are formatted like ECE_fall.csv, CSC_winter.csv, etc
				#they are stored in either the "fall" or "winter" subfolders
				f = open(sem+"/"+dept+'_'+sem+".csv",'a')
				f.write(line+'\n')
				f.close()

#process each site and grab the tables
def artsciparser(link):
	#grab the URL remotely and start parsing it
	site=urllib2.urlopen(link)
	soup=BeautifulSoup(site.read())
	#Examine all HTML tables in the site
	for table in soup.findAll('table'):
		mytable = []
		rows = table.findAll('tr')
		#convert to 2D array first
		for i in range (3,len(rows)):
			cols = rows[i].findAll('td')
			try:
				check = cols[5].find(text=True)
				if check[0:3].upper()!="TBA":
					new = []
					for field in cols:						
						#replace all &nbsps with blanks - easier to check
						if field.find(text=True)=="&nbsp;":
							new.append("")
						else:
							new.append(field.find(text=True))
					new[0] = new[0]+new[1]
					del new[4]
					del new[2]
					del new[1]
					mytable.append(new)
			except:
				pass
		#clear up blank cells
		for i in range (0,len(mytable)):
			row = mytable[i]
			try:
				#strip out info like notes from the location cell
				row[3] = row[3].split('\r')[0]
				row[3] = row[3].split('\n')[0]
			except:
				pass
			#if the name/section is blank, then we want to copy the value from above
			if row[0]=="":
				row[0] = mytable[i-1][0]
				if row[1]=="":
					row[1] = mytable[i-1][1]
			#finally convert to csv
			try:
				if row[2]!="":
					if row[0][-1]=='Y' or row[0][-1]=='F':
						outputtocsv(row,"fall")
					if row[0][-1]=='Y' or row[0][-1]=='S':
						outputtocsv(row,"winter")
			except:
				print link

