<?php

include_once("rrdtool.php");

/* **
 *  * _guifi_tostrunits convert a number to string format in B,kB,MB... 
 * **/
function _guifi_tostrunits($num) {
  $d = 0;
  $rn = $num;
  while (($rn / 1000) > 1) {
  	$rn = ($rn / 1000);
  	$d++;
  }
  $base = array('B ','kB','MB','GB','TB','PB');
  return sprintf("%s %s",number_format(($rn),2),$base[$d]);
}

  
/**
 * guifi_get_availability
**/

  
function guifi_get_pings($did, $start = NULL, $end = NULL) {
   
  global $rrdtool_path;
  global $rrddb_path;
  
  $now = time();
  $last_week = time() - (60*60*24*7);
  $var = array();
  $var['max_latency'] = 0;
  $var['min_latency'] = NULL;
  $var['last'] = NULL;
  $var['avg_latency'] = 0;
  $var['succeed'] = 0;
  $var['samples'] = 0;
  $var['last_online'] = 0;
  $var['last_offline'] = 0;

  if ($start == NULL)
    $start = $last_week;
  if ($end == NULL)
    $end = time();
     
  $opts = array(
    'AVERAGE',
    '--start',$start,
    '--end',$end
    );
    
  $fname = sprintf("%s/%d_ping.rrd",$rrddb_path,$did);
  $last = rrd_last($fname);
  $result = rrd_fetch($fname,$opts,count($opts));
  if (is_array($result['data']))
    $result['data'] = array_chunk($result['data'],$result['ds_cnt']);
  else
    return $var;
    
  foreach ($result['data'] as $k=>$data) 
    $fetched_data[$result['start'] + ($k * $result['step'])] = $data;  	
 
  if (isset($fetched_data))
    ksort($fetched_data);
  else
    return $var;

  foreach ($fetched_data as $interval=>$data) {
  	if ($interval >= $last)
  	  break;
  	  
  	list($failed,$latency) = $data;
  	
  	// ignore if obtained dity data
  	if ($failed > 100 or $failed < 0)
  	  continue;
  	
  	if (strtoupper($failed)=='NAN')
  	  continue;
  	
    $var['succeed'] += $failed;
    $last_succeed = $failed;
    if ($failed < 100) {
      $var['last_online'] = $interval;
      $var['avg_latency'] += $latency;
      if ($var['max_latency'] < $latency)
        $var['max_latency']    = $latency;
      if (($var['min_latency'] > $latency) || ($var['min_latency'] == NULL))
        $var['min_latency']    = $latency;
    } else
      $var['last_offline'] = $interval;
      
    $var['last'] = $interval;
    $var['samples']++;  	
  }

  if ($var['samples'] > 0) {
    $var['succeed'] = (100 - ($var['succeed'] / $var['samples']));
    $var['avg_latency'] = $var['avg_latency'] / $var['samples'];
    $var['last_sample'] = date('H:i',$var['last']);

    ($var['last_offline']) ? 
      $var['last_offline'] = date('Y/m/d H:i',$var['last_offline']) :
      $var['last_offline'] = 'n/a';

    ($var['last_online']) ? 
      $var['last_online'] = date('Y/m/d H:i',$var['last_online']) :
      $var['last_online'] = 'n/a';

    $var['last_sample_date'] = date('Ymd',$var['last']);
    $var['last_succeed'] = 100 - $last_succeed;
  }
  return $var;
}

function guifi_get_traffic($filename, $start = NULL, $end = NULL) {
  global $rrdtool_path;
  $var['in'] = 0;
  $var['out'] = 0;
  $var['max'] = 0;
  $data = array();
  $secs = NULL;
  
  if ($start == NULL)
    $start = -86400;
  if ($end == NULL)
    $end = -300;

  $opts = array(
    'AVERAGE',
    '--start',$start,
    '--end',$end
    );
  $last = rrd_last($filename);
  $result = rrd_fetch($filename,$opts,count($opts));
  if (!is_array($result['data']))
    return;
  $result['data'] = array_chunk($result['data'],$result['ds_cnt']);
  foreach ($result['data'] as $k=>$data) 
    $fetched_data[$result['start'] + ($k * $result['step'])] = $data;  	
  ksort($fetched_data);
  
  foreach ($fetched_data as $interval=>$data) {
  	if ($interval >= $last)
  	  break;
  	  
  	list($in,$out) = $data;
  	if (strtoupper($in)=='NAN')
  	  continue;
    if ($var['max'] < $in)
      $var['max'] = $in;
    if ($var['max'] < $out)
      $var['max'] = $out;
    $var['in'] += $result['step'] * $in;
    $var['out'] += $result['step'] * $out;
  }
  return $var;
}

function customError($errno, $errstr)
 { 
 echo "<b>Error:</b> [$errno] $errstr";
 }

function simplexml_node_file($n,$waitcache=false,$prefix=null) {
  global $CNMLSource;
  
//  print "\n<br>Parameter: $n \n<br>";
  $btime = microtime(true);
  $CNML = '<cnml>';
  
  $perror = set_error_handler('customError');
  
  $nS = array();
  $an = explode(',',$n);
  foreach ($an as $nc) {
  	$try = 0;
  	$xml = false;
    $fn = $prefix.'tmp/'.$nc.'.cnml';
    do {
//  	  print " Processing $nc try $try cache: $waitcache\n<br>";
  	  if (file_exists($fn)) {
        if (time () < (filectime($fn) + (60 * 60))) {
          $xml = simplexml_load_file($fn);
          if ($xml) {
            $xpnxml = $xml->xpath('//node');
            foreach ($xpnxml as $nxml) {
//         	  print "node: ".$nxml->attributes()->id."\n<br>";
//          	  $xmlstr = $nxml->asXML();
//          	  print "\n<br>String XML:  $xmlstr \n<br>";
              $CNML .= $nxml->asXML();
            }
          }
        } 
      }
      if ($xml)
        break;
      
      // haven't got anything
      if ($waitcache) { 
      	print "Waiting... $try waitcache $waitcache \n<br>";
        $try++;
        sleep(1);
      }
    } while (($waitcache==true) and ($try < 10));
    
    if (!$xml) {
      $nS[] = $nc;
    }
  }
  
//  print "cache part, elapsed: ".(microtime(true)-$btime)."\n<br>";

//  print "Not cached: ";
//  print_r($nS);
  
  if (!count($nS)) {
  	$CNML .= '</cnml>';
//    print "\n<br>Cached: $CNML\n<br>";
    $ret = simplexml_load_string($CNML);
  	restore_error_handler();
//    print "Out, elapsed: ".(microtime(true)-$btime)."\n<br>";
    return $ret;
  }
    
  // Not cached files, query CNML source
  $cnmlS = sprintf($CNMLSource,implode(',',$nS));
  
  $xml = simplexml_load_file($cnmlS);
//  print sprintf("got new data %s, elapsed: %f \n<br>",$cnmlS,(microtime(true)-$btime));
    if ($xml) {
    $xpnxml = $xml->xpath('//node');
    foreach ($xpnxml as $nxml) {
      $fn = $prefix.'tmp/'.$nxml->attributes()->id.'.cnml';    	
      $wcnml = @fopen($fn, "w+") or die("\n<br>Error caching XML, can't write $fn\n");
      fwrite($wcnml,'<cnml>'.$nxml->asXML().'</cnml>');
      fclose($wcnml);
      $CNML .=$nxml->asXML();
    }
  }
  
//  print "\n<br>Not cached: $CNML \n<br>";
  $CNML .= '</cnml>';
  $ret  = simplexml_load_string($CNML);
  restore_error_handler();
//  print "Out, elapsed: ".(microtime(true)-$btime)."\n<br>";
  return $ret;
}

function guifi_get_traf_filename($did, $snmp_index, $snmp_name, $rid) {
  global $rrddb_path;

  if (isset($snmp_index))
    $rrdtraf = (string)$did."-".(string)$snmp_index;
  else if (isset($snmp_name))
    $rrdtraf = (string)$did."-".(string)$rid;
  else 
    return NULL;

  return  $rrddb_path.$rrdtraf.'_traf.rrd';
}


?>
