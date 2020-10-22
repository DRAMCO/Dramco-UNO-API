<?php

//----- Is included into index.php ------

// ScatterGraph
require_once 'SVGGraph/autoloader.php';

$back = "#fff";
$text = "#222";
$axis = "#333";
$grid = "#ddd";
$lege = "#aaa";
if(isset($_GET["style"]) && $_GET["style"] == "dark"){
  $back = "#212529";
  $text = "#ccc";
  $axis = "#ccc";
  $grid = "#343A40";
  $lege = "#888";
}

$settings = [
  'auto_fit' => true,
  'back_colour' => $back,
  'back_stroke_width' => 0,
  'back_stroke_colour' => '#eee',
  'stroke_colour' => $text,
  'axis_colour' => $axis,
  'axis_overlap' => 1,
  'label_colour' => $text,
  'axis_font' => 'Segoe UI',
  'axis_font_size' => '10',
  'axis_stroke_width' => 0.5,
  'axis_text_angle_h' => 45,
  'pad_right' => 20,
  'pad_left' => 20,
  'marker_type' => ['circle','circle','circle','circle'],
  'marker_size' => 2,
  'marker_colour' => [$blue.'#007BFF:0.9','#DC3545:0.9','#28A745:0.9','#FFC107:0.9'],
  'show_labels' => true,
  'label_font' => 'Segoe UI',
  'label_font_size' => '10',
  'show_label_amount' => true,
  'minimum_grid_spacing' => 40,
  'grid_stroke_width' => 0.5,
  'grid_colour' => $grid,
  'datetime_keys' => true,
  'data_label_datetime_format' => 'd-m-Y G:i',
  'structure' => [ 'key' => 0, 'value' => $format ],
  'datetime_key_format' => 'd-m-Y G:i',
  'axis_min_v' => $min - ($max-$min)/10,
  'axis_max_v' => $max,
  'label_v' => $sensorNames[$requestedKey]." [".$sensorUnits[$requestedKey]."]",
  'label_x' => "Time of day",
  'graph_title' => "Scatter plot: Last $limit ".strtolower($sensorNames[$requestedKey])." measurements of ".$app_id." / ".$dev_id.".",
  'graph_title_font_weight' => 500,
  'graph_title_colour' => $text,
  'graph_title_font' => 'Segoe UI',
  'graph_title_position' => 'bottom',
  'graph_title_font_size' => 10,
  'legend_entries' => $legend,
  'legend_back_colour' => $back,
  'legend_colour' => $text,
  'legend_font' => 'Segoe UI',
  'legend_padding' => 0,
  'legend_font_size' => 8,
  'legend_shadow_opacity' => 0,
  'legend_stroke_colour' => $lege,
  'legend_stroke_width' => 0.5,
  'legend_padding_y' => 1,
  'legend_columns' => 3,
  'legend_position' => "top right"

];

$width = 600;
$height = 400;
$type = 'MultiScatterGraph';

$graph = new Goat1000\SVGGraph\SVGGraph($width, $height, $settings);
$graph->values($graphData);

// $graph->render($type);
$output = $graph->fetch($type, $header, $defer_js =true);
// Fix wrong x axis label when negative y values
$pos = strpos($output,'">Time of day</text>');
$output = substr_replace($output, '359.35', $pos-6, 6);

$mime_header = 'Content-type: image/svg+xml; charset=UTF-8';
header($mime_header);
echo $output;

?>