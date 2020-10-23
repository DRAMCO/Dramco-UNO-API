<?php

include("util.php");

$requestType = $_SERVER['REQUEST_METHOD'];

switch ($requestType) {
    case 'POST':
        handlePostRequest();
        break;
    case 'GET':
        handleGetRequest();  
        break;
    default:
        //request type that isn't being handled.
        break;
}

        

function handlePostRequest(){
    $json_string = file_get_contents('php://input');
    $data        = json_decode($json_string, true);
    if ($data) {
        $servername = "dramco.be.mysql";
        $username   = "dramco_be_dramco_uno";
        $db_name    = "dramco_be_dramco_uno";
        $password   = "dQVvudX98NjMRsFS";
        
        $conn = mysqli_connect($servername, $username, $password, $db_name);
        // Check connection
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        
        $app_id  = "'".$data["app_id"]."'";
        $dev_id  = "'".$data["dev_id"]."'";
        
        $accelerometer_x = "NULL"; 
        if(isset($data["payload_fields"]["accelerometer_0"]["x"])){
            $accelerometer_x = mysqli_real_escape_string($conn, $data["payload_fields"]["accelerometer_0"]["x"]);
        }
        
        $accelerometer_y = "NULL"; 
        if(isset($data["payload_fields"]["accelerometer_0"]["y"])){
            $accelerometer_y = "'".mysqli_real_escape_string($conn, $data["payload_fields"]["accelerometer_0"]["y"])."'";
        }
        
        $accelerometer_z = "NULL"; 
        if(isset($data["payload_fields"]["accelerometer_0"]["z"])){
            $accelerometer_z = "'".mysqli_real_escape_string($conn, $data["payload_fields"]["accelerometer_0"]["z"])."'";
        }
        
        $luminosity = "NULL"; 
        if(isset($data["payload_fields"]["luminosity_0"])){
            $luminosity = "'".mysqli_real_escape_string($conn, $data["payload_fields"]["luminosity_0"])."'";
        }
        
        $temperature = "NULL"; 
        if(isset($data["payload_fields"]["temperature_0"])){
            $temperature = "'".mysqli_real_escape_string($conn, $data["payload_fields"]["temperature_0"])."'";
        }
        
        $soil_moisture = "NULL"; 
        if(isset($data["payload_fields"]["analog_0"])){
            $soil_moisture = "'".mysqli_real_escape_string($conn, $data["payload_fields"]["analog_0"])."'";
        }
        
        $meta = json_encode($data["metadata"]);
        $search = array("timestamp", "time", "frequency", "modulation", "data_rate", "coding_rate", "gateways", "channel", "rf_chain"); 
        $replace = array("ts", "t", "f", "mod", "dr", "cr", "gtw", "ch", "rf");
        $meta = str_replace($search, $replace, $meta);
        //$meta = gzencode($meta, 9);
        $meta = "'".mysqli_real_escape_string($conn, $meta)."'"; // TODO: If more than 1 gateway: check for length
        
        $sql = "INSERT INTO messages (`timestamp`, `app_id`, `dev_id`, `accelerometer_x`, `accelerometer_y`, `accelerometer_z`, `luminosity`, `temperature`, `soil_moisture`, `meta`) VALUES (CURRENT_TIMESTAMP(), $app_id, $dev_id, $accelerometer_x, $accelerometer_y, $accelerometer_z, $luminosity, $temperature, $soil_moisture, $meta)";
        echo $sql."\n";
        
        if (!mysqli_query($conn, $sql)) {
            echo ("Error description: " . mysqli_error($conn)."\n");
        }
                    
       
        
        mysqli_close($conn);
        
    }
}

function handleGetRequest(){
    global $sensorNames, $sensorUnits, $keyNames; 

    if(!(isset($_GET["dev_id"]) && isset($_GET["app_id"]))){
        echo("Please provide both Application ID and Device ID.");
    }else{
        $servername = "dramco.be.mysql";
        $username   = "dramco_be_dramco_uno";
        $db_name    = "dramco_be_dramco_uno";
        $password   = "dQVvudX98NjMRsFS";
        
        $mysqli = new mysqli($servername, $username, $password, $db_name);
        // Check connection
        if ($mysqli -> connect_errno) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        
        $app_id = $mysqli->real_escape_string($_GET["app_id"]);
        $dev_id = $mysqli->real_escape_string($_GET["dev_id"]);
        
        if(isset($_GET["graph"]) && isset($_GET["key"]))
            $limit = 30;
        else
            $limit = 1;
        if(isset($_GET["limit"])){
            $limit = intval($mysqli->real_escape_string($_GET["limit"]));
        }
        
        
        $sql = "SELECT `id`, `timestamp`, `app_id`, `dev_id`, `accelerometer_x`, `accelerometer_y`, `accelerometer_z`, `luminosity`, `temperature`, `soil_moisture` FROM `messages` WHERE `app_id`='$app_id' AND `dev_id`='$dev_id' ORDER BY `id` DESC LIMIT $limit";
        if (!($result = $mysqli->query($sql))) {
            echo ("Error description: " . $mysqli->error($conn)."\n");
        }
        
        $messages = array();
        $i = 0;
        
        while($row = $result->fetch_assoc()){
            unset($row["id"]);
            foreach ($row as $key => $value) {
                if($value == NULL){ // Find NULL values
                    unset($row[$key]);
                }
            }
            foreach ($row as $key => $value) { // Re-iterate for float conversion
                if($key == "accelerometer_x" || $key == "accelerometer_y" || $key == "accelerometer_z" || $key == "luminosity" || $key == "temperature" || $key == "soil_moisture"){
                    $row[$key] = (float)$value;
                }
                if($key == "timestamp"){
                    $row[$key] = strtotime($value)*1000;
                    $row["elapsed"] = timeElapsedString($value);
                }
            }
            array_push($messages, $row);
            $i++;
        }

        date_default_timezone_set("Europe/Brussels");

        if(!isset($_GET["graph"])){
            if(isset($_GET["format"]) && $_GET["format"] == "json"){
                if($i == 1){
                    echo(json_encode($messages[0]));
                }
                else{
                    echo(json_encode($messages));
                }
            }else if (!isset($_GET["format"]) || $_GET["format"] == "html"){
                if(isset($_GET["style"]) && $_GET["style"] == "plain"){
                    foreach($messages as $message) {
                        $time = date("d-m-Y G:i", $message["timestamp"]/1000); 

                        echo("<div class=\"message\">");
                        echo("<h5 class=\"ids\"><span class=\"app_id\">$app_id</span> / <span class=\"dev_id\">$dev_id</span></h5><p class=\"time\">$time</p>");
                        echo("<table class=\"sensors\">");
                        foreach ($message as $key => $value) {
                            if(isset($_GET["key"])  && $_GET["key"] != "" && $keyNames[$_GET["key"]] != "" ){
                                $keyRequested = $keyNames[$_GET["key"]];
                                if($key == $keyRequested || ($keyRequested == "accelerometer" && $key == "accelerometer_x") || ($keyRequested == "accelerometer" && $key == "accelerometer_y") || ($keyRequested == "accelerometer" && $key == "accelerometer_z")){
                                    $name = $sensorNames[$key];
                                    $unit = $sensorUnits[$key]; 
                                    echo("<tr class=\"sensor\"><td class=\"sensor_name\">$name</td> <td class=\"sensor_value\">$value&hairsp;<span class=\"sensor_unit\">$unit</span></td></tr>");
                                }
                            }else if($key == "accelerometer_x" || $key == "accelerometer_y" || $key == "accelerometer_z" || $key == "luminosity" || $key == "temperature" || $key == "soil_moisture"){
                                $name = $sensorNames[$key];
                                $unit = $sensorUnits[$key]; 
                                echo("<tr class=\"sensor\"><td class=\"sensor_name\">$name</td> <td class=\"sensor_value\">$value&hairsp;<span class=\"sensor_unit\">$unit</span></td></tr>");
                            }
                        }
                        echo("</table></div>");
                    }
                }else if(!isset($_GET["style"]) || isset($_GET["style"])  && $_GET["style"] == "light" || isset($_GET["style"])  && $_GET["style"] == "dark"){
                    $cardStyleClasses = "bg-light";
                    $tableStyleClasses = "";
                    $bodyStyleClasses = "";
                    if($_GET["style"] == "dark"){
                        $cardStyleClasses = "bg-dark text-white";
                        $tableStyleClasses = "table-dark";
                        $bodyStyle = "background: #212529;";
                    }
                    $refresh = "";
                    if(isset($_GET["refresh"]) && intval($_GET["refresh"]) > 0){
                        $r = intval($_GET["refresh"]);
                        $refresh = "<meta http-equiv=\"refresh\" content=\"$r\">";
                    }

                    echo("<html><header><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">$refresh<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css\" integrity=\"sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2\" crossorigin=\"anonymous\"></header><body style=\"$bodyStyle\">");

                    foreach($messages as $message) {
                        $time = date("d-m-Y G:i", $message["timestamp"]/1000); 

                        echo("<div class=\"message card $cardStyleClasses\" style=\"width: 18rem; margin: 10px; float: left;\">");
                        echo("<div class=\"card-body\"><h5 class=\"ids\"><span class=\"app_id\">$app_id</span> / <span class=\"dev_id\">$dev_id</span></h5><p class=\"h5\"><small class=\"text-muted\">Updated: <span class=\"time\">$time</span></small></p></div>");
                        echo("<table class=\"sensors table $tableStyleClasses\">");
                        foreach ($message as $key => $value) {
                            if(isset($_GET["key"])  && $_GET["key"] != "" && $keyNames[$_GET["key"]] != "" ){
                                $keyRequested = $keyNames[$_GET["key"]];
                                if($key == $keyRequested || ($keyRequested == "accelerometer" && $key == "accelerometer_x") || ($keyRequested == "accelerometer" && $key == "accelerometer_y") || ($keyRequested == "accelerometer" && $key == "accelerometer_z")){
                                    $name = $sensorNames[$key];
                                    $unit = $sensorUnits[$key]; 
                                    echo("<tr class=\"sensor\"><td class=\"sensor_name\">$name</td> <td class=\"sensor_value\">$value&hairsp;<span class=\"sensor_unit\">$unit</span></td></tr>");
                                }
                            }else if($key == "accelerometer_x" || $key == "accelerometer_y" || $key == "accelerometer_z" || $key == "luminosity" || $key == "temperature" || $key == "soil_moisture"){
                                $name = $sensorNames[$key];
                                $unit = $sensorUnits[$key]; 
                                echo("<tr class=\"sensor\"><td class=\"sensor_name\">$name</td> <td class=\"sensor_value\">$value&hairsp;<span class=\"sensor_unit\">$unit</span></td></tr>");
                            }
                        }
                        echo("</table></div></body></html>");
                    }
                }
            }
        }else if(isset($_GET["key"])){
            $requestedKey = $keyNames[$_GET["key"]];
            
            $graphData = array();
            $min = PHP_FLOAT_MAX ; 
            $max = PHP_FLOAT_MIN ;
            foreach($messages as $message) {
                $time =  date("d-m-Y G:i", $message["timestamp"]/1000); 
                if($requestedKey == "accelerometer"){
                    $value = array_push($graphData, array($time, $message["accelerometer_x"], $message["accelerometer_y"], $message["accelerometer_z"]));
                    $min = min($min, $message["accelerometer_x"], $message["accelerometer_y"], $message["accelerometer_z"]);
                    $max = max($max, $message["accelerometer_x"], $message["accelerometer_y"], $message["accelerometer_z"]);
                    $format = [1, 2, 3];
                    $legend = [$sensorNames["accelerometer_x"], $sensorNames["accelerometer_y"], $sensorNames["accelerometer_z"]];
                }else{
                    array_push($graphData, array($time, $message[$requestedKey], NULL));
                    $max = max($max, $message[$requestedKey]);
                    $min = min($min, $message[$requestedKey]);
                    $format = 1;
                    $legend = NULL;
                }
            }   
            include("graph.php");
        }

    }
}  
?>