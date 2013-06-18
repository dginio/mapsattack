#!/usr/bin/python
#coding:utf-8
import sys,re,MySQLdb,simplekml,time,os,unicodedata,ConfigParser
from datetime import timedelta
from datetime import datetime


config = ConfigParser.RawConfigParser()
config.read("/opt/mapsattack/mapsattack.conf")

filename = str(int(time.time()))+".kml"
services = config.get("global", "log_services").split(",")
kmlpath = config.get("global", "webdir")
latitude = config.get("global", "center_latitude")
longitude = config.get("global", "center_longitude")

db = MySQLdb.connect(host=config.get("mysql", "server"), user=config.get("mysql", "guest"), passwd=config.get("mysql", "guest_pw"), db=config.get("mysql", "database"))
db.set_character_set('utf8')
cur = db.cursor()
cur.execute('SET NAMES utf8;')
cur.execute('SET CHARACTER SET utf8;')
cur.execute('SET character_set_connection=utf8;')
cur.execute("SELECT date FROM logs ORDER BY date ASC LIMIT 1;")
date1 = re.compile("^[^\ ]*").findall(str(cur.fetchall()[0][0]))[0]

cur.execute("SELECT date FROM logs ORDER BY date DESC LIMIT 1;")
date2 = re.compile("^[^\ ]*").findall(str(cur.fetchall()[0][0]))[0]

date_format = "%Y-%m-%d"

i = 1
for old in os.listdir(kmlpath):
    if ".kml" in old :
	if i > 10 :
		os.system("rm "+kmlpath+old)
	i += 1

i = 1
while i < len(sys.argv) :
	arg = sys.argv[i]
	if arg[0] == "-" :
		if arg == "-p" :
			try :
				path = sys.argv[i+1]
				if not os.path.exists(path) : exit(0)
				i += 1
			except :
				print "invalid path specified"
				exit(0)
				pass
		elif arg == "-f" :
			try :
				filename = sys.argv[i+1]
				i += 1
			except :
				print "no filename specified"
				exit(0)
				pass
		elif arg == "-s" :
			try :
				services = sys.argv[i+1].split(',')
				i += 1
			except :
				print "no services specified"
				exit(0)
				pass
		elif arg == "-d1" :
			try :
				date1 = str(datetime.strptime(re.compile("[0-9]{1,4}\-[0-9]{1,2}\-[0-9]{1,2}").findall(sys.argv[i+1])[0],date_format))
				i += 1
			except :
				print "invalid date specified"
				exit(0)
				pass
		elif arg == "-d2" :
			try :
				date2 = str(datetime.strptime(re.compile("[0-9]{1,4}\-[0-9]{1,2}\-[0-9]{1,2}").findall(sys.argv[i+1])[0],date_format) + timedelta(days=1))
				i += 1
			except :
				print "invalid date specified"
				exit(0)
				pass
		else :
			print "invalid option : "+arg
			exit(0)

	i += 1

def check_string(s): return unicodedata.normalize("NFKD", unicode(s,'utf-8') ).encode("ascii", "ignore" )

base_request = "SELECT logs.ip,count(logs.ip) as n,ips.location,ips.host,ips.isp,longitude,latitude FROM logs,ips WHERE logs.ip = ips.ip AND date >= '"+MySQLdb.escape_string(date1)+"' AND date <= '"+MySQLdb.escape_string(date2)+"' AND ips.latitude != 0 AND ips.longitude != 0 AND logs.service in ("

for service in services :
	base_request += "'"+MySQLdb.escape_string(service)+"',"

base_request = base_request[:-1]+") "

end_request = "GROUP BY ip ORDER by n DESC;"

request = base_request+end_request
print request
cur.execute(request)

results = cur.fetchall()

kml = simplekml.Kml()

i = 0
for result in results :
	description = check_string("<![CDATA["+str(result[1])+" connections.<br/>Location : "+result[2]+"<br/>Host : <a target='_blank' href='http://"+result[3]+"'>"+result[3]+"</a><br/>ISP : "+result[4]+"<br>]]>")
	lin = kml.newlinestring(name=result[0], description=description,coords=[(result[5],result[6]),(longitude,latitude)])
        lin.tessellate = 1
        lin.extrude = 1
        lin.Altitudemode = simplekml.AltitudeMode.clamptoground
	if i < 5 :
		lin.style.linestyle.width = 3
		opacity = "B"
	else : 
		lin.style.linestyle.width = 2
		opacity = "6"
	connections = int(result[1])
	if connections > 1000 : lin.style.linestyle.color = opacity+"0"+"1400FF"
	elif connections > 100 : lin.style.linestyle.color = opacity+"0"+"1473FF"
	else : lin.style.linestyle.color = opacity+"0"+"14E7FF"

	i += 1

kml.save(kmlpath+filename)

cur.close()
db.close()
