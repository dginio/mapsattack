<?php
	$months = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sept','Oct','Nov','Dec');
    
    $data = file_get_contents("/opt/mapsattack/mapsattack.conf");
    $lines = explode("\n", $data);

    for ( $i = 0; $i < sizeof($lines); $i++ ) 
        if ( preg_match("/^log_services\ \=\ .*/", $lines[$i] ) ) {
            $services = explode("=",str_replace(" ","",$lines[$i]));
            $services = explode(",",$services[1]);
        }
        else if ( preg_match("/^server\ \=\ .*/", $lines[$i] ) ) {
            $server = explode("=",str_replace(" ","",$lines[$i]));
            $server = $server[1];
        }
        else if ( preg_match("/^database\ \=\ .*/", $lines[$i] ) ) {
            $database = explode("=",str_replace(" ","",$lines[$i]));
            $database = $database[1];
        }
        else if ( preg_match("/^guest\ \=\ .*/", $lines[$i] ) ) {
            $user = explode("=",str_replace(" ","",$lines[$i]));
            $user = $user[1];
        }
        else if ( preg_match("/^guest_pw\ \=\ .*/", $lines[$i] ) ) {
            $pwd = explode("=",str_replace(" ","",$lines[$i]));
            $pwd = $pwd[1];
        }

	mysql_connect($server,$user,$pwd);
	mysql_select_db($database);
	$rdate1 = mysql_query("SELECT date FROM logs ORDER BY date ASC  LIMIT 1;" ) or die ("Error");
	while ( $res = mysql_fetch_array( $rdate1 ) ) $date1 = $res['date'];
	$rdate2 = mysql_query("SELECT date FROM logs ORDER BY date DESC LIMIT 1;" ) or die ("Error");
    while ( $res = mysql_fetch_array( $rdate2 ) ) $date2 = $res['date'];
	$dsd = intval(substr($date1,8,2));
	$dsm = intval(substr($date1,5,2));
	$dsy = intval(substr($date1,0,4));
	$ded = intval(substr($date2,8,2));
	$dem = intval(substr($date2,5,2));
	$dey = intval(substr($date2,0,4));
?>
<!DOCTYPE html>
<html>
	<head>
		<title>MapsAttack</title>
		<style type="text/css">
			body {
				height: 100%;
				margin: 0;
				padding: 0;
				background-color:#424242;
				font-family:'Courier New', Courier, monospace;
				color:white;
			}
			#panel {
				width : 20%;
				position : absolute;
			}
			input[type="button"] {
				background-color:#5F5F5F;
				color:white;
                height:20px;
				border:none;
				min-width:200px;
				font-family:"Courier New", Courier, monospace;
			}
			a {
				color:white;
			}
		</style>
        <script type="text/javascript">
            function apply() {
                if ( document.getElementById("maps").checked ) url = "maps.php";
                else url = "earth.php";
				get = "?"
                <?php foreach ($services as $service) echo "if ( document.getElementById('".$service."').checked ) get += '".$service."=1&';\nelse get += '".$service."=0&';\n"; ?>

				if ( ( document.getElementById("dsd").value < 32   && document.getElementById("dsd").value > 0 ) 
				&&   ( document.getElementById("dsm").value < 13   && document.getElementById("dsm").value > 0 )
				&&   ( document.getElementById("dsy").value < 3000 && document.getElementById("dsy").value > 0 )
				&&   ( document.getElementById("ded").value < 32   && document.getElementById("ded").value > 0 ) 
				&&   ( document.getElementById("dem").value < 13   && document.getElementById("dem").value > 0 )
				&&   ( document.getElementById("dey").value < 3000 && document.getElementById("dey").value > 0 ) ) {
					date1 = new Date(document.getElementById("dsy").value,document.getElementById("dsm").value-1,document.getElementById("dsd").value);
					date2 = new Date(document.getElementById("dey").value,document.getElementById("dem").value-1,document.getElementById("ded").value);
					if ( date1 <= date2 ) {
						get += "d1="+document.getElementById("dsy").value+"-"+document.getElementById("dsm").value+"-"+document.getElementById("dsd").value;
						get += "&d2="+document.getElementById("dey").value+"-"+document.getElementById("dem").value+"-"+document.getElementById("ded").value;
						document.getElementById('show').src = url+get;
						document.getElementById('kml').href = "kml.php"+get+"&a=download";
					}
					else alert("Dates : it should be 'Start' <= 'End'");
				}
			}        
        </script>
	</head>
	<body onload="apply();">
		<div id="panel">
			<center>
				<h3>MapsAttack</h3>
			</center>
			<table align="center">
				<tr>
					<td>
						<input id="maps" name="presentation" type="radio" onclick="apply();" checked="checked" />
					</td>
					<td>
						<label for="maps">Maps</label>
					</td>
				</tr>
				<tr>
					<td>
						<input id="earth" name="presentation" type="radio" onclick="apply();"/>
					</td>
					<td>
						<label for="earth">Earth</label>
					</td>
				</tr>
				<tr>
					<td colspan=2>
                        <br/><br/>
						<h4>Services :</h4>
					</td>
				</tr>
                <?php 
                    foreach ($services as $service) 
                        echo '<tr>
    				<td>
						<input type="checkbox" id="'.$service.'" checked="checked" />
					</td>
					<td>
						<label for="'.$service.'">'.$service.'</label>
					</td>
				</tr>'; ?>
				<tr>
					<td colspan=2>
						<br/>
						<h4>Dates :</h4>
					</td>
				</tr>
				<tr>
					<td>
						Start
					</td>
					<td>
						<select id="dsd">
							<?php for ($i = 1; $i < 32; $i++) { echo "<option "; if ( $i==$dsd ) echo "selected"; echo " value='".$i."'>".$i."</option>"; }	?>
						</select>
						<select id="dsm">
							<?php for ($i = 1; $i < 13; $i++) { echo "<option "; if ( $i==$dsm ) echo "selected"; echo " value='".$i."'>".$months[$i-1]."</option>"; } ?>
						</select>
						<select id="dsy">
							<?php for ($i = 2010; $i < 2020; $i++) { echo "<option "; if ( $i==$dsy ) echo "selected"; echo " value='".$i."'>".$i."</option>"; } ?>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						End
					</td>
					<td>
						<select id="ded">
							<?php for ($i = 1; $i < 32; $i++) { echo "<option "; if ( $i==$ded ) echo "selected"; echo " value='".$i."'>".$i."</option>"; }	?>
						</select>
						<select id="dem">
							<?php for ($i = 1; $i < 13; $i++) { echo "<option "; if ( $i==$dem ) echo "selected"; echo " value='".$i."'>".$months[$i-1]."</option>"; } ?>
						</select>
						<select id="dey">
							<?php for ($i = 2010; $i < 2020; $i++) { echo "<option "; if ( $i==$dey ) echo "selected"; echo " value='".$i."'>".$i."</option>"; } ?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan=2 align="center">
						<br/>
						<input type="button" onclick="apply();" value="Apply" />
					</td>
				</tr>       
				<tr>
					<td colspan=2 align="center">
						<br/><br/>
						<a id="kml" href="" />Download the current kml</a>
					</td>
				</tr>
			</table>
		</div>
		<iframe id="show" src="" style="border: 0; width: 80%; height: 100%" align="right" scrolling="no"></iframe>
	</body>
</html>
