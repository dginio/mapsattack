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
	$url = current_url()."/kml.php".$get;
?>
<!DOCTYPE html>
<html>
    <head>
        <title>MapsAttack - Maps</title>
		<style type="text/css">
			html, body {
				height: 100%;
				margin: 0;
				padding: 0;
			}
			#map-canvas, #map_canvas {
				height: 100%;
			}
			@media print {
				html, body {
					height: auto;
				}
				#map-canvas, #map_canvas {
					height: 650px;
				}
			}
			#panel {
				position: absolute;
				top: 5px;
				left: 50%;
				margin-left: -180px;
	 			z-index: 5;
				background-color: #fff;
				padding: 5px;
				border: 1px solid #999;
			}
		</style>
		<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>
		<script type="text/javascript">
			function initialize() {
				var mapOptions = {
					zoom: 1,
					center: new google.maps.LatLng(0,0),
					mapTypeId: google.maps.MapTypeId.HYBRID
				}
				var map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
				var ctaLayer = new google.maps.KmlLayer({
					<?php echo "url: '".$url."'\n"; ?>
				});
				ctaLayer.setMap(map);
			}
			google.maps.event.addDomListener(window, 'load', initialize);
		</script>
	</head>
    <body>
        <div id="map-canvas"></div>
    </body>
</html>
