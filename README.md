### mapsattack is a tool to localize malicious connections on a server

[Check out the demo here](https://dginio.fr/mapsattack)
![alt text](https://dginio.fr/mapsattack/preview.jpg "Demo")

#### it works in 3 steps :

* collect information in your log files and write it in mapsattack's database
* use the information contained in the database to create a kml file
* read the kml file with google maps or google earth

##### installation ( as root ) :

###### install packages :
> apt-get update && apt-get install git unzip apache2 php5 mysql-server python2.7 python-mysqldb python-dateutil -fy

###### download mapsattack :
> cd /opt
> git clone https://github.com/dginio/mapsattack.git

###### configure rights :
> cd mapsattack
> chown root:www-data * -Rf
> chmod 750 * -Rf
> chmod 770 web/
	
###### install simplekml for python : 
> wget https://simplekml.googlecode.com/files/simplekml-1.2.2.zip
> unzip simplekml-1.2.2.zip
> cd simplekml-1.2.2
> python setup.py install
> cd ..
> rm simplekml* -Rf

###### setup the database :
> mysql -u root -p
> create database mapsattack;
> use mapsattack;
		
> CREATE TABLE logs (line char(255) NOT NULL, service char(255) NOT NULL, date datetime NOT NULL, ip char(15) NOT NULL, action char(255) NOT NULL, info char(255) NOT NULL, PRIMARY KEY (line) ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
		
> CREATE TABLE ips (ip char(15) NOT NULL, host char(255) NOT NULL, isp char(255) NOT NULL, latitude float NOT NULL, longitude float NOT NULL, location char(255) NOT NULL, PRIMARY KEY (ip) ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
		
> CREATE USER 'mapsattack_guest'@'localhost' IDENTIFIED BY '`<guest_pwd>`';
GRANT SELECT ON mapsattack.* TO 'mapsattack_guest'@'localhost' IDENTIFIED BY ‘`<guest_pwd>`’;
		
> CREATE USER 'mapsattack'@'localhost' IDENTIFIED BY '`<superuser_pwd>`';
GRANT SELECT,INSERT,DELETE ON mapsattack.* TO 'mapsattack'@'localhost' IDENTIFIED BY '`<superuser_pwd>`';

> FLUSH PRIVILEGES;

> exit

###### configure mapsattack :
edit the file /opt/mapsattack/mapsattack.conf

set the service you want enable in the global part, for example :
> log_services = sshd,vsftpd

set the location of the server, you can get it on http://www.iplocationfinder.com/
> latitude = xx.xxxxxx

> longitude = xx.xxxxxx

set the passwords you used for the database accounts in the mysql part

> [mysql]

> ...

> superuser_pw = `<superuser_pwd>`

> guest_pw = `<guest_pwd>`

###### start mapsattack :
enable the www link for apache (your apache must be open on the internet to permit google to read your .kml)
> ln -s /opt/mapsattack/web/ /var/www/mapsattack

start the first parsing :
> ./log_scan.py

automatically scan log with cron : 
> crontab -e

add this line to scan log files every 3 minutes :
> */3 * * * * python /opt/mapsattack/log_scan.py


##### Check who is attacking you on http://your_server/mapsattack
