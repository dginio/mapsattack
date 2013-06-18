<?php 
	function current_url() {
		$url = 'http';
		if ($_SERVER["HTTPS"] == "on") $url .= "s";
		$url .= "://";
		if ( ($_SERVER["SERVER_PORT"] != "80") and ($_SERVER["SERVER_PORT"] != "443") ) {
			$url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].dirname($_SERVER['PHP_SELF']);
		}
		else $url .= $_SERVER["SERVER_NAME"].dirname($_SERVER['PHP_SELF']);
		return $url;
	}
    if ( ! preg_match("/[0-9]{1,4}\-[0-9]{1,2}\-[0-9]{1,2}/",$_GET['d1']) ) exit();
    if ( ! preg_match("/[0-9]{1,4}\-[0-9]{1,2}\-[0-9]{1,2}/",$_GET['d2']) ) exit();
    $data = file_get_contents("/opt/mapsattack/mapsattack.conf");
    $lines = explode("\n", $data);
    for ( $i = 0; $i < sizeof($lines); $i++ )
        if ( preg_match("/^log_services.*/", $lines[$i] ) ) {
            $services = explode("=",str_replace(" ","",$lines[$i]));
            $services = explode(",",$services[1]);
            break;
        }
    $check = 0;
    
    foreach ($services as $service) if ( $_GET[$service] < 0 or $_GET[$service] > 2 ) $check = 1;
    if ( $check == 1 ) exit();
    
    $get = "?a=print&d1=".$_GET["d1"]."&d2=".$_GET["d2"];
    foreach ($services as $service) $get .= "&".$service."=".$_GET[$service];
    $get .= "&f=".time().".kml";
	$url = urlencode(current_url()."/kml.php".$get);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>MapsAttack - Earth</title>
        <style type="text/css">
    		html, body {
				height: 100%;
				margin: 0;
				padding: 0;
			}
        </style>
    </head>
    <body>
        <script type="text/javascript">
            var winW = 630, winH = 460;
            if (document.body && document.body.offsetWidth) {
                winW = document.body.offsetWidth;
                winH = document.body.offsetHeight;
            }
            if (document.compatMode=='CSS1Compat' && document.documentElement && document.documentElement.offsetWidth ) {
                winW = document.documentElement.offsetWidth;
                winH = document.documentElement.offsetHeight;
            }
            if (window.innerWidth && window.innerHeight) {
                winW = window.innerWidth;
                winH = window.innerHeight;
            }
			<?php echo 'var src = "http://www.gmodules.com/ig/ifr?url=http://dl.google.com/developers/maps/embedkmlgadget.xml&amp;up_kml_url='.$url.'&amp;up_view_mode=earth&amp;up_earth_2d_fallback=0&amp;up_earth_fly_from_space=1&amp;up_earth_show_nav_controls=1&amp;up_earth_show_buildings=1&amp;up_earth_show_terrain=1&amp;up_earth_show_roads=1&amp;up_earth_show_borders=1&amp;up_earth_sphere=earth&amp;up_maps_zoom_out=0&amp;up_maps_default_type=map&amp;synd=open&amp;w="+winW+"&amp;h="+winH+"&amp;output=js";'; ?>
            document.write('<script type="text/javascript" src='+src+'><\/script>');
        </script>
    </body>
</html>
