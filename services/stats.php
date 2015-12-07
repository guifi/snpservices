<?php
/*
 * Created on 24/09/2008
 *
 * stats CNML service
 */

// info hook
// provides a message with information about the service and how to use it
function stats_info() {
	echo "stats";
  echo "\n\n";
	echo "outputs statistics from the devices\n";
	echo "optional parameters:\n";
	echo "  devices=<device_id>[,<device_id>]\n";
	echo "    comma separated list of devices to gather information from\n";
	echo "    if no list is given, will output all the statistics available\n";
	echo "  traffic_start=-<time>\n";
	echo "    <time> = negative number, in seconds, from when the graph starts\n";
	echo "    default value = -31536000 (1 year)\n";
	echo "  traffic_end=-<time>\n";
	echo "    <time> = negative number, in seconds, from when the graph ends\n";
	echo "    default value = -300 (5 mins)\n";
	echo "\n";
}

// main hook
// provides the service
function stats_main() {
	(isset($_GET['traffic_start']))  ? $traffic_start   = $_GET['traffic_start'] : $traffic_start  = -(60*60*24*365);
	(isset($_GET['traffic_end']))    ? $traffic_end     = $_GET['traffic_end']   : $traffic_end    = -300;
	if ($traffic_start == 0)
		$traffic_start = -(60*60*24*365);
	if (isset($_GET['devices']))
	  stats_view($traffic_start,$traffic_end,explode(',',$_GET['devices']));
	else
	  stats_view($traffic_start,$traffic_end);
}

function stats_view($traffic_start,$traffic_end,$devices = array()) {
	global $rrddb_path;

	if (!count($devices)) {
		// No devices given, so going to output all devices with information at
		// the file system
		$files = glob(sprintf("%s/*_ping.rrd",$rrddb_path));
		if (!count($files)) {
  		  print sprintf('There is no statstics to dump at this server (%s)\n',
  		    $rrddb_path);
		  exit;
		}
		foreach ($files as $filename) {
	      // only gathers statistics from devices updated in the last 12 hours
	      $ctime = filemtime($filename);
	      if ($ctime > (time()-(60*60*12))) {
	  	    list($did) = sscanf(basename($filename,'.rrd'),"%d_ping");
		    $devices[] = $did;
	      } 
//	        else {
//	      	// if not updated for a year, delete (must have permission)
//	      	if ($ctime < (time()-(60*60*24*365)))
//	      	  unlink($filename);
//	      }
		}
	}
	sort($devices);

  header("Content-Type: text/plain");
	foreach ($devices as $did) {
		$now = time();
		$lastday = $now - (60*60*24);
		$lastyear = $now - (60*60*24*365);
		$pyt = guifi_get_pings($did,$lastday);
		$py = guifi_get_pings($did,$lastyear);
		// now providing the availability stats
		print $did;
        print sprintf("|%d,%d,%.2f,%s,%s,%s,%d",
          $pyt['max_latency'],
          $pyt['avg_latency'],
          ($py['succeed']==0)?$pyt['succeed']:$py['succeed'],
          $py['last_online'] > $pyt['last_online'] ? $py['last_online'] : $pyt['last_online'],
          $pyt['last_sample_date'],
          $pyt['last_sample'],
          $py['last_succeed']);
		// now, getting the traffic, looking into the files <device_id>-<index>_traf.rrd
		$files = glob(sprintf("%s/%d-*_traf.rrd",$rrddb_path,$did));
		if (count($files)) foreach ($files as $filename) {
	      list($id,$snmp_key) = sscanf(basename($filename,'.rrd'),"%d-%d_traf");
	      $traf = guifi_get_traffic($filename,$traffic_start,$traffic_end);
		  print sprintf('|%s,%d,%d',$snmp_key,($traf['in']/(1000000)),($traf['out']/(1000000)));
		}
		print "\n";
	}
}

?>
