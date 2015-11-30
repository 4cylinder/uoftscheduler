from apscparser import *
from artsciparser import *

print ("Parsing the UTSG timetable listings. This may take a few minutes")
print ("Crawling APSC fall")
apscparser("fall")
print ("Crawling APSC winter")
apscparser("winter")
print ("Crawling ArtSci (fall and winter)")
links = getlinks()
for link in list(set(links)):
	try:
		artsciparser(link)
	except:
		pass
print ("Done!")
