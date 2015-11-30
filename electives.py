import urllib2, os, glob, sys
from BeautifulSoup import *
import csv

def getAU(crs,username,password):
	url = "https://magellan.ece.utoronto.ca/courses_detail_popup.php?popup_acad_act_cd="+crs+"&popup_offered=2013"

	passman = urllib2.HTTPPasswordMgrWithDefaultRealm()
	passman.add_password(None, url, username, password)
	authhandler = urllib2.HTTPBasicAuthHandler(passman)
	opener = urllib2.build_opener(authhandler)
	urllib2.install_opener(opener)
	
	site=urllib2.urlopen(url)
	soup=BeautifulSoup(site.read())
	#Examine all HTML tables in the site
	for table in soup.findAll('table'):
		for row in table.findAll('tr'):
			cols = row.findAll('td',{"style":"text-align:center;"})
			try:
				val = cols[2].find(text=True)
				AU = float(val)
				if AU>0:
					print crs,
					print AU
					return
			except:
				pass

def parseelectives(sem,username,password):
	for fcsv in glob.glob(sem+"/*.csv"):
		f = open(fcsv,"r")
		reader = csv.reader(f)
		prev = ""
		for row in reader:
			course = row[0]
			course = course[:-1]
			if course!=prev:
				getAU(course,username,password)
				prev = course
		f.close()
		
username = sys.argv[1]
password = sys.argv[2]
parseelectives("fall",username,password)
parseelectives("winter",username,password)
