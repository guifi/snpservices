<?php

function snmplist($did,$dev) {
  if (!isset($dev[$did])) 
    return;
  $att = $dev[$did]->attributes();

  $ipv4 = null;
  $snmp = array();

  $rnum = 0;

  foreach ($dev[$did] as $key=>$value) {
    $tatt = $value->attributes();
    // any other server
    if ($att->type!='radio') {
      if ($key == 'interface') {
        if ($tatt->type=='Lan' or $tatt->type=='wLan/Lan')
          $ipv4=$tatt->ipv4;
      }
      if (isset($att->snmp_index))
        $snmp[(int)$att->id] = (int)$att->snmp_index;
      continue;
    }

    // is a radio, now let's take the interface
    switch ($key) {
    case 'radio':
      if ($tatt->mode=='client') {
        $itype="Wan";
      } else {
        $itype="wLan/Lan";
      }

      if (isset($tatt->snmp_index)) {
        $snmp[(int)$tatt->id] = (int)$tatt->snmp_index;
      } else if (isset($tatt->snmp_name))
//        $snmp[(int)$tatt->id] = 'wlan'.((int)$tatt->id + 1).';'.$tatt->ssid;
        $snmp[(int)$tatt->id] = $tatt->snmp_name.';'.$tatt->ssid;

      break;
    case 'interface':
      if (!empty($itype) and $tatt->type==$itype)
        $ipv4=$tatt->ipv4;
      break;
    }
  }

  if ($ipv4) {
    if ((isset($_GET['cp'])) or (isset($_GET['list']))) {
      $devfn = str_replace(array (' ','.','-','?','&','%','$'),"",strtolower($att->title));
      if (isset($_GET['cp']))
        print "cp ".$devfn."_ping.rrd ".$did."_ping.rrd\n";
      if (isset($_GET['list']))
        print $did."_ping.rrd\n";
      foreach ($snmp as $k=>$idx) {
        if (is_numeric($idx)) {
          if (isset($_GET['cp']))
            print "cp ".$devfn."_".$idx.".rrd ".$did."-".$idx."_traf.rrd\n";
          if (isset($_GET['list']))
            print $did."-".$idx."_traf.rrd\n";
        }
        else {
          $wlan = explode(';',$idx);
          $sidfn = str_replace(array (' ','.','-','?','&','%','$'),"",strtolower($wlan[1]));
          if (isset($_GET['cp']))
            print "cp ".$sidfn.".rrd ".$did."-".$k."_traf.rrd\n";
          if (isset($_GET['list']))
            print $did."-".$k."_traf.rrd\n";
        }
      }
    } else
      print $did.','.$att->title.','.$ipv4.','.implode('|',$snmp)."\n";
  }
}

function snmp($device) {
	
	if ($device->getAttribute('id') == 464)
	  $mrtg = "# ADSL de Gurb ";
	
	if (!($device->getAttribute('id')))
	  return;
	  
	$mainip = array('Lan','wLan/Lan');
	  
	// get ipv4 / snmp interfaces 
	$ipv4 = 0;
	$snmp = array();
	foreach ($device->childNodes as $child) {

  	if ($child->nodeType != XML_ELEMENT_NODE)
  	  continue;
  	  		  
		if ($child->tagName == 'radio') {
      $child->hasAttribute('snmp_name') ? 
        $snmp[$child->getAttribute('id')] = 
          $child->getAttribute('snmp_name').';'.
          $child->getAttribute('ssid') :
        $snmp[$child->getAttribute('id')] = $child->getAttribute('snmp_index');
        
      if ($child->getAttribute('mode')=='client')
        $mainip[] = 'Wan';
		}

    if ($child->tagName == 'interface') {
    	if ($device->hasAttribute('snmp_name')) 
        $snmp[$device->getAttribute('id')] = $device->getAttribute('snmp_name');
      else if ($device->hasAttribute('snmp_index'))
        $snmp[$device->getAttribute('id')] = $device->getAttribute('snmp_index');
            	
      if ((!$ipv4) and in_array($child->getAttribute('type'),$mainip)) 
          $ipv4 = $child->getAttribute('ipv4');
    }
	}
	
	// if no ipv4, nothing;
	if (!$ipv4)
	  return;

        
        $mrtg = '#';

	$mrtg .= 
	  $device->getAttribute('title').','.
	  $ipv4;
	if (count($snmp))
	  $mrtg .= ','.implode('|',$snmp);
        
        $mrtg .= ','.$device->getAttribute('status');	  
	return $mrtg;
}

function cnmlwalk($cnml,$SNPServer,$arr = array(), $export = FALSE) {

  $time_start = microtime(true);

  foreach($cnml as $tag=>$value) {
    $sons = $export;
    $att = $value->attributes();
    if (!empty($att['graph_server']))
    if ($att['graph_server'] == $SNPServer)
       $sons = TRUE;
    else
       if ($att['graph_server'] != 0)
         $sons = FALSE;
    switch ($tag) {
      case 'device':
        $arr['device'][(int)$att['id']] = $value;
        if ($att['type'] == 'radio') 
        foreach($value as $dtag=>$dvalue) {
          $datt = $dvalue->attributes();
          switch ($dtag) {
          case 'radio':
            if ($datt['mode'] == 'ap') {
              if ($sons) { 
                $arr['mrtg'][(int)$att['id']]=NULL;
                // radio interfaces
                foreach($dvalue as $rtag=>$rvalue) {
                  if ($rtag == 'interface') foreach($rvalue as $itag=>$ivalue) {
                    if ($itag == 'link') {
                      $latt = $ivalue->attributes();
                      $arr['mrtg'][(int)$latt['linked_device_id']]=NULL;
                    } // foreach link
                  }  
                }
              } 
            } // radio in mode ap
            break;
          case 'interface':
            // check if the radio has its own links
            if ($sons) {
              $arr['mrtg'][(int)$att['id']]=NULL;
              foreach($dvalue as $itag=>$ivalue) {
                if ($itag == 'link') {
                  $latt = $ivalue->attributes();
                  $arr['mrtg'][(int)$latt['linked_device_id']]=NULL;
                } // foreach link
              }
            }
            break;
          }
        } // is a radio
      case 'node':
      case 'network':
      case 'zone':
        $arr = cnmlwalk($value,$SNPServer,$arr,$sons);
        break;
    }
  }


  return $arr;
}

function zonewalk($zone,$SNPServer,&$arr) {

  foreach ($zone->childNodes as $child) {
  	if ($child->nodeType != XML_ELEMENT_NODE)
  	  continue;

    if (in_array($child->tagName,array('node','zone'))) {
      if ($child->hasAttribute('graph_server')) 
        if ($child->getAttribute('graph_server') != $SNPServer)
          continue;
    } else continue;

  	switch ($child->tagName) {
    case 'zone':
      zonewalk($child,$SNPServer,$arr);
      break;
    case 'node':
      nodewalk($child,$SNPServer,$arr);
      break;
    }

//  	if ($child->tagName=='zone')
//  	  print "Child: ".$child->tagName." ".$child->getAttribute('title')." \n";
  }
}

function nodewalk($node,$SNPServer,&$arr) {
  foreach ($node->getElementsByTagName('device') as $child) {
    devicewalk($child,$SNPServer,$arr);
//    print "Child:--- ".$child->tagName." ".$child->getAttribute('title')." \n";
  }
}

function devicewalk($device,$SNPServer,&$arr,$links = true) {
	$id = $device->getAttribute('id');
	if (isset($arr['device'][$id]))
	  return;
	 
	$arr['device'][$id] = snmp($device);
	
	if (!$links)
	  return; 
	  
	if (isset($arr['linked'][$id]))
	  unset($arr['linked'][$id]);
	
	foreach ($device->getElementsByTagName('link') as $link) {
		$dlinked = $link->getAttribute('linked_device_id');
		if (!isset($arr['device'][$dlinked]))
		  $arr['linked'][$dlinked] = null;
	}
}

if (file_exists("../common/config.php")) {
   include_once("../common/config.php");
} else {
  include_once("../common/config.php.template");
}

// Controlinmg time execution (this routine should be very efficient)
$time_start = microtime(true);

if (!isset($_GET['server'])) {
//  echo "You should provide the ID of a valid SNP graph server\n";
//  exit();
  $SNPServer=$SNPGraphServerId;
} else
  $SNPServer = $_GET['server'];
  
header("Content-Type: text/plain");

// Opening CNML source
$cnml = new DOMDocument;
$cnml->preserveWhiteSpace = false;
$cnml->Load('../data/guifi.cnml');
$time_s = microtime(true);
//print "# file guifi.cnml loaded".($time_s - $time_start)."\n";

// Validate the graph server
$xpath = new DOMXPath($cnml);
$query = "//service[@id=".$SNPServer." and @type='SNPgraphs']";
$entries = $xpath->evaluate($query);
//$time_x = microtime(true);
//print "guifi.cnml loaded and server verified ".($time_x - $time_start)."\n";

// print count($servers);
if (count($entries) == 0) {
  echo "You must provide a valid server id\n";
  exit();
}

$arr = array();
$time_x = microtime(true);

// Query for zones with the given graph server
$xpath = new DOMXPath($cnml);
$query = "//zone[@graph_server=".$SNPServer."]";
$entries = $xpath->query($query);
foreach ($entries as $entry) {
  zonewalk($entry,$SNPServer,$arr);
}

// Query for nodes with the given graph server
$xpath = new DOMXPath($cnml);
$query = "//node[@graph_server=".$SNPServer."]";
$entries = $xpath->query($query);
foreach ($entries as $entry) {
  nodewalk($entry,$SNPServer,$arr);
}

// Query for devices with the given graph server
$xpath = new DOMXPath($cnml);
$query = "//device[@graph_server=".$SNPServer."]";
$entries = $xpath->query($query);
foreach ($entries as $entry) {
  devicewalk($entry,$SNPServer,$arr);
}

//$time_x = microtime(true);
//print "guifi.cnml graph server XPATH ".($time_x - $time_start)."\n";


if (!empty($arr['linked'])) {
// Cleaning linked already filled
foreach ($arr['linked'] as $k=>$foo)
  if (isset($arr['device'][$k]))
    unset($arr['linked'][$k]);

  // Query for linked devices
  $xpath = new DOMXPath($cnml);
  $linkid = implode(' or @id=',array_keys($arr['linked']));
  if (!empty($linkid)) {
    $query = '//device[@id='.$linkid.']';
    $entries = $xpath->query($query);
    if (!empty($entries)){
      foreach ($entries as $entry) {
        devicewalk($entry,$SNPServer,$arr,false);
      }
    }
  }
}

//$time_1 = microtime(true);
//print "guifi.cnml walked ".($time_1 - $time_start)." got ".count($arr['device'])." devices (".count($arr['linked'])." linked)\n";
//print_r($arr);

//header('Content-type: application/csv');

// Going to dump the output
if (!empty($arr['device'])) 
      foreach($arr['device'] as $id=>$foo) {
	if ($foo != null)
	  if (isset($_GET['list']))  {
	  	$names = array();
	  	$a = explode(',',$foo);
	  	$names[] = $id.'_ping.rrd';
	  	if (isset($a[2])) {
	  		$i = explode('|',$a[2]);
	  		if (is_numeric($i))
	  		  $names[] = $id.'-'.$i[0].'_traf.rrd';
	  		else foreach($i as $k=>$si)
	  		  $names[] = $id.'-'.$k.'_traf.rrd';	  		
	  	}  	
	  	
	  	switch ($_GET['list']) {
	  		case 'NanoStation':
	  		  $i = explode(';',$a[2]);
	  		  if ($i[0] == 'wifi0')
	  		    print 'cp '.$id.'-4_traf.rrd '.$id."-0_traf.rrd\n";
	  		  break;
	  		default:
	  		  foreach ($names as $n)
	  		    print $n."\n";
	  	}
	  } else
 	     print $id.','.$foo."\n";
}
//$time_2 = microtime(true);
//print "snmp created ".($time_2 - $time_start)."\n";
?>
