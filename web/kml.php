<?php
	if ( ! empty($_GET['f']) ) {
		if ( preg_match("/[0-9]+\.kml$/",$_GET['f'] ) ) $file = $_GET['f'];
		else $file = time().".kml";
    }
	else $file = time().".kml";
	$cmd = "/opt/mapsattack/kml_gen.py -f ".$file;

    $data = file_get_contents("/opt/mapsattack/mapsattack.conf");
    $lines = explode("\n", $data);
    for ( $i = 0; $i < sizeof($lines); $i++ )
        if ( preg_match("/^log_services.*/", $lines[$i] ) ) {
            $services = explode("=",str_replace(" ","",$lines[$i]));
            $services = explode(",",$services[1]);
            break;
        }
    if ( sizeof($services) > 0 ) {
        $cmd_servc = " -s ";
        foreach ($services as $service) if ( $_GET[$service] == 1 ) $cmd_servc .= $service.",";
        $cmd .= substr($cmd_servc, 0, -1);
    }
    else exit();

	if ( preg_match("/[0-9]{1,4}\-[0-9]{1,2}\-[0-9]{1,2}/",$_GET['d1'] ) and ( preg_match("/[0-9]{1,4}\-[0-9]{1,2}\-[0-9]{1,2}/",$_GET['d2']) ) ) {
		$date1 = strtotime($_GET['d1']);
		$date2 = strtotime($_GET['d2']);

		if ( empty($date1) || empty($date2) ) exit();

		if ( strtotime($_GET['d1']) > strtotime($_GET['d2']) ) exit();

		$cmd .= " -d1 ".$_GET["d1"]." -d2 ".$_GET["d2"];
	}
	else exit();

	exec($cmd);
	
	if ( empty($_GET['a']) ) exit();
	if ( ( $_GET['a'] == "download" ) && (file_exists("./".$file) ) ) {
		$size = filesize("./" . basename($file)); 
		header("Content-Type: application/force-download; name=\"" . basename($file) . "\""); 
		header("Content-Transfer-Encoding: binary"); 
		header("Content-Length: $size"); 
		header("Content-Disposition: attachment; filename=\"" . basename($file) . "\""); 
		header("Expires: 0"); 
		header("Cache-Control: no-cache, must-revalidate"); 
		header("Pragma: no-cache"); 
		readfile("./" . basename($file)); 
		exit(); 
	}
	else if ( $_GET['a'] == "print" ) echo file_get_contents($file);
?>
