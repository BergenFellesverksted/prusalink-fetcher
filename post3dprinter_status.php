<?php
require 'constants.php';

// connect
$mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

// read & decode JSON payload
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    http_response_code(400);
    echo "Invalid JSON";
    exit();
}

// pull out sub-objects (with defaults)
$printer = $data['printer'] ?? [];
$status  = $data['status']  ?? [];
$job      = $status['job']      ?? [];
$storage  = $status['storage']  ?? [];
$printers = $status['printer']  ?? [];

// sanitize / prepare each field
$ip           = $mysqli->real_escape_string($printer['ip'] ?? '');
$name         = $mysqli->real_escape_string($printer['name'] ?? '');

$job_id       = isset($job['id'])             ? intval($job['id'])           : 'NULL';
$job_progress = isset($job['progress'])       ? floatval($job['progress'])   : 'NULL';
$time_rem     = isset($job['time_remaining']) ? intval($job['time_remaining']): 'NULL';
$time_print   = isset($job['time_printing'])  ? intval($job['time_printing']) : 'NULL';

$spath        = isset($storage['path'])
                 ? "'".$mysqli->real_escape_string($storage['path'])."'" 
                 : 'NULL';
$sname        = isset($storage['name'])
                 ? "'".$mysqli->real_escape_string($storage['name'])."'" 
                 : 'NULL';
$sro          = isset($storage['read_only'])
                 ? ($storage['read_only'] ? 1 : 0)
                 : 'NULL';

$state        = isset($printers['state'])
                 ? "'".$mysqli->real_escape_string($printers['state'])."'" 
                 : "'UNKNOWN'";

$temp_bed     = isset($printers['temp_bed'])    ? floatval($printers['temp_bed'])    : 'NULL';
$target_bed   = isset($printers['target_bed'])  ? floatval($printers['target_bed'])  : 'NULL';
$temp_nozzle  = isset($printers['temp_nozzle']) ? floatval($printers['temp_nozzle']) : 'NULL';
$target_nozzle= isset($printers['target_nozzle'])? floatval($printers['target_nozzle']): 'NULL';

$axis_z       = isset($printers['axis_z'])      ? floatval($printers['axis_z'])      : 'NULL';
$axis_x       = isset($printers['axis_x'])      ? floatval($printers['axis_x'])      : 'NULL';
$axis_y       = isset($printers['axis_y'])      ? floatval($printers['axis_y'])      : 'NULL';

$flow         = isset($printers['flow'])        ? intval($printers['flow'])          : 'NULL';
$speed        = isset($printers['speed'])       ? intval($printers['speed'])         : 'NULL';
$fan_hotend   = isset($printers['fan_hotend'])  ? intval($printers['fan_hotend'])    : 'NULL';
$fan_print    = isset($printers['fan_print'])   ? intval($printers['fan_print'])     : 'NULL';

// build & run the INSERT ... ON DUPLICATE KEY UPDATE
$sql = "
INSERT INTO `3dprinter_status` (
  ip, printer_name,
  job_id, job_progress, time_remaining, time_printing,
  storage_path, storage_name, storage_read_only,
  state, temp_bed, target_bed, temp_nozzle, target_nozzle,
  axis_z, axis_x, axis_y, flow, speed, fan_hotend, fan_print
) VALUES (
  '$ip', '$name',
  $job_id, $job_progress, $time_rem, $time_print,
  $spath, $sname, $sro,
  $state, $temp_bed, $target_bed, $temp_nozzle, $target_nozzle,
  $axis_z, $axis_x, $axis_y, $flow, $speed, $fan_hotend, $fan_print
)
ON DUPLICATE KEY UPDATE
  printer_name      = '$name',
  job_id            = $job_id,
  job_progress      = $job_progress,
  time_remaining    = $time_rem,
  time_printing     = $time_print,
  storage_path      = $spath,
  storage_name      = $sname,
  storage_read_only = $sro,
  state             = $state,
  temp_bed          = $temp_bed,
  target_bed        = $target_bed,
  temp_nozzle       = $temp_nozzle,
  target_nozzle     = $target_nozzle,
  axis_z            = $axis_z,
  axis_x            = $axis_x,
  axis_y            = $axis_y,
  flow              = $flow,
  speed             = $speed,
  fan_hotend        = $fan_hotend,
  fan_print         = $fan_print,
  last_updatedUTC   = CURRENT_TIMESTAMP;
";

if (! $mysqli->query($sql)) {
    http_response_code(500);
    echo "DB Error: " . $mysqli->error;
    exit();
}

echo "OK";
?>