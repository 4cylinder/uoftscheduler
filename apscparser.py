import urllib2, os
from BeautifulSoup import *

def apscparser(sem):
	#create subfolder called fall or winter
	try: 
		os.makedirs(sem)
	except:
		pass
	#grab the URL remotely and start parsing it
	url = "http://www.apsc.utoronto.ca/timetable/"+sem+".html"
	site=urllib2.urlopen(url)
	soup=BeautifulSoup(site.read())
	#Examine all HTML tables in the site
	for table in soup.findAll('table'):
		mytable = []
		rows = table.findAll('tr')
		#convert to 2D array first
		for i in range (0,len(rows)):
			cols = rows[i].findAll('td')
			try:
				new = []
				for j in range(0,7):
					#replace all &nbsps with blanks - easier to check
					if cols[j].find(text=True)=="&nbsp;":
						new.append("")
					else:
						new.append(cols[j].find(text=True))
				mytable.append(new)
			except:
				pass
		#then convert to csv
		for i in range (0,len(mytable)):
			row = mytable[i]
			#if the section is blank, then we want to copy the value from above
			if row[1]=="" and row[0]==mytable[i-1][0] and row[2]>mytable[i-1][2]:
				for j in range(i-1,-1,-1):
					#but only if the meet (row[2]) is different from the one above
					#sometimes the same section will be taking place in multiple
					#locations at the same time and we only want to grab one of
					#those rows
					if mytable[j][0]==row[0] and mytable[j][1]!="" and mytable[j][2]<row[2]:
						row[1] = mytable[j][1]
						break
			#strip out info like notes from the location cell
			row[6] = row[6].split('*')[0]
			row[6] = row[6].split(' ')[0]
			row[6] = row[6].split('-')[0]
			row[6] = row[6].split('\r')[0]
			row[6] = row[6].split('\n')[0]
			#get department code, e.g. it is CSC if the course is CSC180H1F
			dept = row[0][0:3]
			#ignore anything marked blank or PEY or with a blank section
			if len(dept)>1 and dept != "PEY" and row[1] != "":
				#open (or create if not found) a csv file to store the timings
				#filenames are formatted like ECE_fall.csv, CSC_winter.csv, etc
				#they are stored in either the "fall" or "winter" subfolders
				f = open(sem+"/"+dept+'_'+sem+".csv",'a')
				#don't need to show the Meet value
				line = ",".join(row[j] for j in [0,1,3,4,5,6])
				f.write(line+'\n')
				f.close()

