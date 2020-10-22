<?php
ini_set( 'serialize_precision', -1 );

function timeElapsedString($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}


$sensorNames = array(   "accelerometer"=>"Acceleration", 
						"accelerometer_x"=>"Acceleration X", 
                        "accelerometer_y"=>"Acceleration Y", 
                        "accelerometer_z"=>"Acceleration Z",
                        "luminosity"=>"Luminosity",
                        "temperature"=>"Temperature",
                        "soil_moisture"=>"Soil Moisture"
                    );

$sensorUnits = array(   "accelerometer"=>"g", 
						"accelerometer_x"=>"g", 
                        "accelerometer_y"=>"g", 
                        "accelerometer_z"=>"g",
                        "luminosity"=>"&#37;",
                        "temperature"=>"&#176;C",
                        "soil_moisture"=>"&#37;"
                    );

$keyNames = array(   	"acceleration"=>"accelerometer", 
	                    "acceleration_x"=>"accelerometer_x", 
	                    "accelerationx"=>"accelerometer_x",
	                    "acceleration_y"=>"accelerometer_y", 
	                    "accelerationy"=>"accelerometer_y",
	                    "acceleration_z"=>"accelerometer_z", 
	                    "accelerationz"=>"accelerometer_z",
	                    "accelerometer" => "accelerometer",
	                    "accelerometer_x"=>"accelerometer_x", 
	                    "accelerometerx"=>"accelerometer_x",
	                    "accelerometer_y"=>"accelerometer_y", 
	                    "accelerometery"=>"accelerometer_y",
	                    "accelerometer_z"=>"accelerometer_z", 
	                    "accelerometerz"=>"accelerometer_z",
	                    "luminosity"=>"luminosity",
	                    "temperature"=>"temperature",
	                    "soil_moisture"=>"soil_moisture",
	                    "soilmoisture"=>"soil_moisture"
                    );
?>