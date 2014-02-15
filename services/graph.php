<?php
/*
 * Created on 24/09/2008
 *
 * availability CNML service
 */
 
// info hook
// provides a message with information about the service and how to use it
function graph_info() {
	echo "graph\n\n";

	echo "provides a PNG image with a graph\n";	
	echo "parameters:\n";
	echo "  type=pings|clients|supernode|device\n";
	echo "    graph type\nmandatory (no default)\n";
	echo "    if type=device|pings\n";
	echo "     parameters:\n";
	echo "      device=<device_id>\n";
	echo "        <device_id> device id to get the graph>\n";
	echo "    if type=supernode|clients\n";
	echo "     parameters:\n";
	echo "      direction=in|out\n";
	echo "        traffic direction of the graph (in or out)>\n";
	echo "  node=<node_id>\n";
	echo "    <node_id> node id to get the graph>\n";
	echo "  start=-<time>\n";
	echo "    <time> = negative number, in seconds, from when the graph starts\n";
	echo "    default value = -86400 (1 day)\n";	
	echo "  end=-<time>\n";
	echo "    <time> = negative number, in seconds, from when the graph ends\n";
	echo "    default value = -300 (1/2 hour)\n";	
	echo "  width=<pixels>\n";
	echo "    <pixels> = graph width, in pixels\n";
	echo "    default value = 600\n";	
	echo "  height=<pixels>\n";
	echo "    <pixels> = graph height, in pixels\n";
	echo "    default value = 120\n";	
	echo "  cached\n";
	echo "    if someone else could already query the CNML source, so just need to wait for the result\n";
	echo "    default not cached\n";	
	echo "  debug\n";
	echo "    outputs the rrdtool command line instead of the graph\n";
	echo "    default not debug\n";
	echo "\n";
}

// main hook
// provides the service
function graph_main() {
	global $rrddb_path;
	global $rrdtool_path;
	global $rrdtool_version;
	
	// reading parameters
	if (isset($_GET['type']))
  	$type    = $_GET['type'];
  else {
    header("Content-Type: text/plain");
    echo "Error: missing graph type\n";
    graph_info();
  	return;
  }
    
	(isset($_GET['start']))  ? $start   = $_GET['start'] : $start  = -86400;
	(isset($_GET['end']))    ? $end     = $_GET['end']   : $end    = -300;
	(isset($_GET['width']))  ? $width   = $_GET['width'] : $width  = 600;
	(isset($_GET['height'])) ? $height  = $_GET['height']: $height = 120;
	(isset($_GET['thumb']))  ? $thumb   = "-j"           : $thumb  = "";
	(isset($_GET['cached'])) ? $cached  = true           : $cached = false;
	
  if (isset($_GET['node'])) {		    
 		$gxml = simplexml_node_file($_GET['node'],$cached);
 		$node = $_GET['node'];
  } else {
    header("Content-Type: text/plain");
    echo "Error: missing node\n";
    graph_info();
	  return;	   			
  }
  
  if (isset($_GET['radio']))
   $device = $_GET['radio'];
  if (isset($_GET['device']))
   $device = $_GET['device'];
   
	switch ($type) {
		case 'pings':
		case 'device':
		case 'radio':
		  if (empty($device)) {
        header("Content-Type: text/plain");
        echo "Error: missing graph type\n";
        graph_info();
  	    return;	
		  }
		  break;
	}
	
	$radios = array();
	$key = 0;
	
	if ($start == 0) 
		$start = -86400;
	if ($end == 0) 
		$start = -300;
	
	$color = array(
		'#0000FF','#FF0000','#FFCC00','#66CCFF','#000000','#00CC00','#990000','#FFFF00','#800000','#C0FFC0','#FFDCA8','#008000','#A0A0A0',
		'#0000FF','#FF0000','#FFCC00','#66CCFF','#000000','#00CC00','#990000','#FFFF00','#800000','#C0FFC0','#FFDCA8','#008000','#A0A0A0',
		'#0000FF','#FF0000','#FFCC00','#66CCFF','#000000','#00CC00','#990000','#FFFF00','#800000','#C0FFC0','#FFDCA8','#008000','#A0A0A0',
		'#0000FF','#FF0000','#FFCC00','#66CCFF','#000000','#00CC00','#990000','#FFFF00','#800000','#C0FFC0','#FFDCA8','#008000','#A0A0A0'
	);

	$cmd = '';
	
	if (isset($device))	{
		//----------  XML Start Xpath Query-----------------------------------
		$radio_xml=$gxml->xpath('//device[@id='.$device.']');
		$radio_attr=$radio_xml[0]->attributes();
		//----------  XML End Xpath Query -----------------------------------      
	}

	if (isset($_GET['direction'])) {
		$direction = strtolower($_GET['direction']);
	}	else {
		$direction='in';
	}
	switch ($direction)	{
		case 'in':  $ds = 'ds0'; $otherdir = 'out'; $otherds = 'ds1'; break;
		case 'out': $ds = 'ds1'; $otherdir = 'in';  $otherds = 'ds0'; break;
	}
	
	
	// Parse variables finalized, going to build the graph
	switch ($type) {
		case 'supernode': 
			//----------  XML Start Xpath Query-----------------------------------
			$nodestr=array('nick' => '', 'title' => '');
			$nodestr['title']=$gxml->xpath('//node[@id='.$node.']/@title');
			$nodestr['nick']=$gxml->xpath('//node[@id='.$node.']/@title');
			//----------  XML End Xpath Query -----------------------------------      
			$title = sprintf('Supernode: %s - wLANs %s',$nodestr['nick'][0],$direction);
			$vscale = 'bits/sec';

		case 'clients':
			$cmd = sprintf(' COMMENT:"%32s%11s%13s%12s%16s\n"<br />','     ','Now','Avg','Max','Total');
			if ($type == 'clients')
			{
				$radios_dev = $radio_xml[0]->xpath('radio');
				$traffic = array('in'=> 0,'out' => 0, 'max' => 0);
				foreach ($radios_dev as $radio_dev) {
					$radio_dev_attr = $radio_dev->attributes();
					//          print_r($radio_dev_attr);
					//          print "\n<br>";
					
					$filename = guifi_get_traf_filename($radio_dev_attr['device_id'],$radio_dev_attr['snmp_index'],$radio_dev_attr['snmp_name'],$radio_dev_attr['id']);
					
					$traffic_radio = guifi_get_traffic($filename,$start,$end);
					$traffic['in'] =$traffic['in']  + $traffic_radio['in'];
					$traffic['out']=$traffic['out'] + $traffic_radio['out'];
					if ($traffic_radio['max'] > $traffic['max'])
						$traffic['max']=$traffic_radio['max'];
					$radios[] = array('title' => $radio_dev_attr['ssid'], 'change_direction' => true, 'filename' => $filename, 'max' => $traffic['max'],'traffic'=>$traffic_radio['out']);
				}
				$title = sprintf('wLAN: %s (%s) - links (%s)',$radio_attr['title'],$otherdir,$direction);
				$vscale = 'bits/sec';
			}
			
			$result = array();
			//----------  XML Start Xpath Query-----------------------------------
			if ($type == 'supernode') {
				$result=$gxml->xpath('//node/device/radio');      
			} else {
				$row = simplexml_load_string($radio_xml[0]->asXML());
				$linked_radios=$row->xpath('//radio/interface/link');
				$remote_clients = array();
				foreach ($linked_radios as $linked_radio) {
					$linked_radio_attr=$linked_radio->attributes();
					$remote_clients[] = (int)$linked_radio_attr['linked_node_id']; 
				}
				$rxml = simplexml_node_file(implode(',',$remote_clients),$cached);
				reset($linked_radios);
				foreach ($linked_radios as $linked_radio) {
					$linked_radio_attr=$linked_radio->attributes();
					$result_client = $rxml->xpath('//device[@id='.$linked_radio_attr['linked_device_id'].']/radio');
					if (is_array($result_client)) $result = array_merge($result,$result_client);
				}
			}
			//----------  XML End Xpath Query -----------------------------------      
			
			$rdone = array();
			if (!empty($result))
				foreach ($result as $k=>$radiodev) {
				$radio_attr = $radiodev->attributes();
				
				$dstr = $radio_attr['device_id'].'-'.$radio_attr['id'];
				
				if (isset($rdone[$dstr]))
					continue;
				
				$rdone[$dstr] = true;
				
				$radiofetch['title'] = $radio_attr['ssid'];
				
				$filename = guifi_get_traf_filename($radio_attr['device_id'],$radio_attr['snmp_index'],$radio_attr['snmp_name'],$radio_attr['id']);
				
				if (file_exists($filename))
				{
					$traffic = guifi_get_traffic($filename,$start,$end);
					$radiofetch['change_direction'] = false;
					$radiofetch['filename'] = $filename;
					$radiofetch['max'] =  $traffic['max'];
					$radiofetch['traffic'] = $traffic[$direction];
					$radios[] = $radiofetch;
					$key ++;
					
				}	  
			}
			
			usort($radios,"cmp_traffic");
			
			$total = array();
			foreach ($radios as $r) {
				$total['total'] += $r['traffic'];
				$total['max'] += $r['max'];
			}
			
			//        print_r($radios);
			$col = 0;
			
			if (isset($_GET['numcli'])) {	 
				if ($_GET['numcli']=='max') {   
					$numcli=count($totals);
				} else {    
					$numcli=$_GET['numcli']; 
				}
			} else { 
				$numcli = 10;
			}
			
			foreach ($radios as $key => $item) {
				$totalstr = _guifi_tostrunits($item['traffic']);	  
				if (($type == 'clients') && ($item['change_direction'])) {
					$dir_str = $otherdir;
					$datasource = $otherds;
				}	else {
					$datasource = $ds;
					$dir_str = $direction;
				}
				$cmd .= sprintf(' DEF:val%d="%s":%s:AVERAGE',$key,$item['filename'],$datasource);
				$cmd .= sprintf(' CDEF:val%da=val%d,8,* ',$key,$key);
				$cmd .= sprintf(' LINE1:val%da%s:"%30s %3s"',$key,$color[$col],$item['title'],$dir_str);
				$cmd .= sprintf(' <br />GPRINT:val%da:LAST:"%%8.2lf %%s"',$key);
				$cmd .= sprintf(' GPRINT:val%da:AVERAGE:"%%8.2lf %%s"',$key);
				$cmd .= sprintf(' GPRINT:val%da:MAX:"%%8.2lf %%s"',$key);
				$cmd .= sprintf(' COMMENT:"%15s\n" ',$totalstr);
				$cmd .= "<br />";
				$col++;
				if (($type == 'clients') && ($col > $numcli)) break; 
			}
			$cmd .= sprintf(' <br />COMMENT:"TOTAL\: %83s\n" ',
				_guifi_tostrunits($total['total']));
			break;
		case 'radio': 
		case 'device': 
			$cmd = sprintf(' COMMENT:"%32s%11s%13s%12s%16s\n"<br />','     ','Now','Avg','Max','Total');
			$vscale = 'bits/sec';
			$row = simplexml_load_string($radio_xml[0]->asXML());
			$w = $row->xpath('//radio');
			$w_attr = $w[0];
			$title = sprintf('radio: %s - wLAN In & Out',$radio_attr['title']);
			if (isset($radio_attr->snmp_index))
				$filename = guifi_get_traf_filename($radio_attr['id'],$radio_attr['snmp_index'],null,$radio_attr['snmp_index']);
			else 
				$filename = guifi_get_traf_filename($w_attr['device_id'],$w_attr['snmp_index'],$w_attr['snmp_name'],$w_attr['id']);
			
			$traffic = guifi_get_traffic($filename,$start,$end);
			
			$cmd .= sprintf(' DEF:val0="%s":ds0:AVERAGE',$filename);
			$cmd .=         ' CDEF:val0a=val0,8,* ';
			$cmd .= sprintf(' AREA:val0a#0000FF:"%30s In "',$radio_attr['title']);
			$cmd .=         ' <br />GPRINT:val0a:LAST:"%8.2lf %s"';
			$cmd .=         ' GPRINT:val0a:AVERAGE:"%8.2lf %s"';
			$cmd .=         ' GPRINT:val0a:MAX:"%8.2lf %s"';
			$cmd .= sprintf(' COMMENT:"%15s\n"',_guifi_tostrunits($traffic['in']));
			$cmd .= sprintf(' DEF:val1="%s":ds1:AVERAGE',$filename);
			$cmd .=         ' CDEF:val1a=val1,8,* ';
			$cmd .= sprintf(' LINE2:val1a#00FF00:"%30s Out"',$radio_attr['title']);
			$cmd .=         ' <br />GPRINT:val1a:LAST:"%8.2lf %s"';
			$cmd .=         ' GPRINT:val1a:AVERAGE:"%8.2lf %s"';
			$cmd .=         ' GPRINT:val1a:MAX:"%8.2lf %s"';
			$cmd .= sprintf(' COMMENT:"%15s\n"',_guifi_tostrunits($traffic['out']));
			break;
		case 'pings': 
			$cmd = sprintf(' COMMENT:"%14s%8s%16s%17s\n"<br />','     ','Now','Avg','Max');
			$pings = guifi_get_pings($radio_attr['id'],$start,$end);
			$vscale = 'latency (secs/1000)';
			$title = sprintf('device: %s -  ping latency - online (%.2f %%)',$radio_attr['title'],$pings['succeed']);
			$filename = $rrddb_path.$radio_attr['id'].'_ping.rrd';
			$cmd .= sprintf(' DEF:val0="%s":ds0:AVERAGE',$filename);
			$cmd .= sprintf(' AREA:val0#FFFF00:"%12s "','offline');
			$cmd .=         ' GPRINT:val0:LAST:"%8.2lf %%    "';
			$cmd .=         ' GPRINT:val0:AVERAGE:"%8.2lf %%    "';
			$cmd .=         ' GPRINT:val0:MAX:"%8.2lf %%    "';
			$cmd .= sprintf(' COMMENT:"Last offline\: %s\n"',addcslashes($pings['last_offline'],':'));
			$cmd .= sprintf(' <br />DEF:val1="%s":ds1:AVERAGE',$filename);
			$cmd .= sprintf(' LINE2:val1#00FF00:"%12s "','latency');
			$cmd .=         ' GPRINT:val1:LAST:"%8.2lf msec."';
			$cmd .=         ' GPRINT:val1:AVERAGE:"%8.2lf msec."';
			$cmd .=         ' GPRINT:val1:MAX:"%8.2lf msec."';
			$cmd .= sprintf(' COMMENT:" Last online\: %s\n"',addcslashes($pings['last_online'],':'));
			break;
	} //end switch $type:
	
	if ($width < 600) {
		$DEFAULT = 7;
		$vscale='';
		$LEGEND = 5;
		$AXIS = 6;
	} else {
		$DEFAULT = 10;
		$LEGEND = 8;
		$AXIS = 8;	
	}
	
	(isset($rrdtool_version) and ($rrdtool_version >= '1.3')) ?
			$fonts = sprintf('--font DEFAULT:%d:Arial --font LEGEND:%d:Courier --font AXIS:%d:Arial',$DEFAULT,$LEGEND,$AXIS) :
				$fonts = sprintf('--font DEFAULT:%d: --font LEGEND:%d: --font AXIS:%d:',$DEFAULT,$LEGEND,$AXIS);
			
	$cmd = sprintf("%s graph - %s <br />" .
		"--title=\"%s\" --imgformat=PNG --width=%d  --height=%d %s <br />" .
		"--vertical-label=\"%s\" --start=%d --end=%d --base=1000 -E <br /> %s ",
		$rrdtool_path,$fonts,
		$title,$width,$height,
		$thumb,$vscale,$start,$end,$cmd);
			
	if (isset($_GET['debug'])) {
		header("Content-Type: text/plain");
		$shell = explode('<br />',$cmd);
		foreach ($shell as $line)
	  	echo $line.'\\'."\n";
	}
			
	$cmd = str_replace('<br />','',$cmd);
			
	$fp = popen($cmd, "rb");
	if (isset($fp)) {
		if (!isset($_GET['debug']))  {
			header("Content-Type: image/png");
			print fpassthru($fp);
		}
	}
	pclose($fp);
}


function cmp_traffic($a, $b) {
	if ($a['traffic'] == $b['traffic']) {
		return 0;
	}
	return ($b['traffic'] < $a['traffic']) ? -1 : 1;
}

?>
