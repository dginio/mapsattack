#!/usr/bin/python
#coding:utf-8
import os,re,MySQLdb,urllib,urllib2,socket,struct,ConfigParser,time
from dateutil import parser

config = ConfigParser.RawConfigParser()
config.read("/opt/mapsattack/mapsattack.conf")

excluded_networks = config.get("global", "excluded_networks").split(",")
services = config.get("global", "log_services").split(",")

db = MySQLdb.connect(host=config.get("mysql", "server"), user=config.get("mysql", "superuser"), passwd=config.get("mysql", "superuser_pw"), db=config.get("mysql", "database"))
cur = db.cursor()

def ip_in_net(ip, net) :
	ipaddr = int(''.join([ '%02x' % int(x) for x in ip.split('.') ]), 16)
	netstr, bits = net.split('/')
	netaddr = int(''.join([ '%02x' % int(x) for x in netstr.split('.') ]), 16)
	mask = (0xffffffff << (32 - int(bits))) & 0xffffffff
	return (ipaddr & mask) == (netaddr & mask)

def parse(data,regex) :
	try : 
		result = re.compile(regex).findall(data)
		try :
			result = result[0]
		except :
			print regex
			pass

		if result == "-" : result = ""
	except : 
		result = ""
		pass
	return result

def get_ip_informations(ip) :
	default_timeout = 10
	request = urllib2.Request("http://www.iplocationfinder.com/"+ip)
	request.add_header("User-Agent", "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.36")
	reply = ""
	while reply == "" and default_timeout < 1000 :
		try :
			reply = urllib2.urlopen(request,timeout = default_timeout).read()
		except :
			default_timeout += default_timeout
			print "Error on request : iplocationfinder.com, timeout set to : "+str(default_timeout)
			pass

	host = ""; isp = ""; city = ""; region = ""; country = ""; longitude = ""; latitude = "";

	host = parse(reply,"\<label\>Hostname\:\<\/label\>([^\<]*)")
	isp = parse(reply,"\<label\>ISP\:\<\/label\>([^\<]*)")
	city = parse(reply,"\<label\>City\:\<\/label\>([^\<]*)")
	region = parse(reply,"\<label\>Region\:\<\/label\>([^\<]*)")
	country = parse(reply,"\<label\>Country\:\<\/label\>[^\>]*\>([^\<]*)")
	longitude = parse(reply,"\<label\>Longitude\:\<\/label\>([^\<]*)")
	latitude = parse(reply,"\<label\>Latitude\:\<\/label\>([^\<]*)")

	if host == "" or isp == "" or city == "" or region == "" or country == "" or longitude == "" or latitude == "" :
		default_timeout = 10
		request = urllib2.Request("http://www.geo-ip.fr/")
		request.add_header("User-Agent", "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17")
		request.add_header("Content-Type", "application/x-www-form-urlencoded")
		reply = ""
		while reply == "" :
			try :
				reply = urllib2.urlopen(request,"ip="+ip,timeout = default_timeout).read()
			except :
				default_timeout += default_timeout
				print "Error on request : geo-ip.fr, timeout set to : "+str(default_timeout)
				pass

		if country == "" and "Pays" in reply : country = parse(reply,"\<b\>Pays\ \:\<\/b\>\ ([^\<]*)")
		if region == "" and "Région" in reply : region = parse(reply,"\<b\>Région\ \:\<\/b\>\ ([^\<]*)")
		if city == "" and "Ville" in reply : city = parse(reply,"\<b\>Ville\ \:\<\/b\>\ ([^\<]*)")
		if latitude == "" and "Latitude" in reply : latitude = parse(reply,"\<b\>Latitude\ \:\<\/b\>\ ([^\<]*)")
		if longitude == "" and "Longitude" in reply : longitude = parse(reply,"\<b\>Longitude\ \:\<\/b\>\ ([^\<]*)")
		if host == "" and "hôte" in reply : host = parse(reply,"\<b\>Nom\ d\'hôte\ \:\ \<\/b\>([^\<]*)").replace(" ","");

	if latitude == "" : latitude = "0"
	if longitude == "" : longitude = "0"

	return (host,isp,latitude,longitude,country+" - "+region+" - "+city)

def handle(line,service,date,ip,action,info) :
	request_log = "INSERT INTO logs (line,service,date,ip,action,info) VALUES ('"+MySQLdb.escape_string(line)+"', '"+MySQLdb.escape_string(service)+"', '"+MySQLdb.escape_string(date)+"', '"+MySQLdb.escape_string(ip)+"', '"+MySQLdb.escape_string(action)+"', '"+MySQLdb.escape_string(info)+"');"
	try : 
		cur.execute(request_log)
		print date+" - "+ip+" - "+service
	except MySQLdb.Error, e :
		if e[0] != 1062 : # If not duplicate entry
			print e
			print request_log
		pass

	db.query("SELECT ip FROM ips WHERE ip = '"+MySQLdb.escape_string(ip)+"';")
	result = db.store_result().fetch_row()
	if not result :
		informations = get_ip_informations(ip)
		host = informations[0]
		isp = informations[1]
		latitude = informations[2]
		longitude = informations[3]
		location = informations[4]
		try :
			request_ip = "INSERT INTO ips (ip,host,isp,latitude,longitude,location) VALUES ('"+MySQLdb.escape_string(ip)+"','"+MySQLdb.escape_string(host)+"', '"+MySQLdb.escape_string(isp)+"', "+MySQLdb.escape_string(latitude)+", "+MySQLdb.escape_string(longitude)+", '"+MySQLdb.escape_string(location)+"');"
		except :
			print ip+" | "+host+" | "+isp+" | "+str(latitude)+" | "+str(longitude)+" | "+location
		try : 
			cur.execute(request_ip)
		except MySQLdb.Error, e :
			print e
			print request_ip
			pass

def scan(service) :
	name = config.get(service, "name")
	print "Handling "+name

	filter_selection = re.sub("^\"|\"$","",config.get(service, "detection_filter"))
	action = re.sub("^\"|\"$","",config.get(service, "detected_action"))
	re_info = re.sub("^\"|\"$","",config.get(service, "re_info"))
	re_ip = re.sub("^\"|\"$","",config.get(service, "re_ip"))
	date_regex = re.sub("^\"|\"$","",config.get(service, "date_regex"))

	base_filename = config.get(service, "log_file")

        avaible_files = []
        if os.path.isfile(base_filename) :
                avaible_files.append(base_filename)
        else :
                print "file not found : "+base_filename
	
	if config.getboolean(service, "log_rotation") : 
		i = 1
		while os.path.isfile(base_filename+"."+str(i)) :
			avaible_files.append(base_filename+"."+str(i))
			i += 1
		avaible_files.reverse()
	
	db.query("SELECT line FROM logs WHERE service = '"+service+"' ORDER BY date DESC LIMIT 1;")
	result = db.store_result().fetch_row()
	if not result : 
		passed = 1
	else :
		passed = 0
		last_line = result[0][0]

	for filename in avaible_files :
		logfile = open(filename,"r")
		print " - "+filename
		for line in logfile :
			if passed == 0 and last_line.strip() == line.strip() : passed = 1
			if passed == 1 and filter_selection in line :
				date_catch = parse(line,date_regex)
				date = str(parser.parse(date_catch))
				info = parse(line,re_info)
				ip = parse(line,re_ip)
				bad_ip = False
				for net in excluded_networks : 
					if ip_in_net(ip, net) : bad_ip = True
				if not bad_ip :	handle(line,service,date,ip,action,info)

		db.commit()
		logfile.close()

for service in services : 
	scan(service)

cur.close()
db.close()
