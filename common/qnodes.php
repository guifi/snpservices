<?php

if (file_exists('../common/config.php'))
  include_once("../common/config.php");
else
  include_once("../common/config.php.template");

include_once("../common/misc.php");

if (isset($_GET['nodes']))
  $nodes=$_GET['nodes'];
else
  // for testing purposes, filling the query with a fixed list
  // $nodes = '3682,2673,2653,4675,5887,6362,5531,2201,5452,3310,6446,5506,5836,5846,6032,4631,6093,3718,6530,3725,3833,4030,4120,3998,5519,5525,6038,6173,6228,6298,2784,6601,6703,1636,4796,5486,5765,5784,5816,5046,4720,6199,6291,6555,6549,6596,6616,6659,6732,2984';
  die ('Error: At least one node has to be given to the query as a parameter. Syntax: qnodes?nodes=1,2,3,4,5');

$an = explode(',',$nodes);

// Opening CNML source
$doc = new DOMDocument;
$doc->preserveWhiteSpace = false;
$doc->Load('../data/guifi.cnml');

// building the xpath query for requested nodes
$xpath = new DOMXPath($doc);
$query = '//node[@id='.implode(' or @id=',$an).']';
$entries = $xpath->query($query);

// Creating output CNML document
$docout = new DOMDocument('1.0');
// we want a nice output
$docout->formatOutput = true;
$root = $docout->createElement('cnml');
$root = $docout->appendChild($root);

// CNML attributes
// TODO: Support for multi-site DATA servers, now creatied with fixed values
$cVersion = $docout->createAttribute('version');
$root->appendChild($cVersion);
$cVersion->appendChild($docout->createTextNode('0.1')); 
$cServerId = $docout->createAttribute('server_id');
$root->appendChild($cServerId);
$cServerId->appendChild($docout->createTextNode('1')); 
$cServerUrl = $docout->createAttribute('server_url');
$root->appendChild($cServerUrl);
$cServerUrl->appendChild($docout->createTextNode('http://guifi.net')); 
$cGenerated = $docout->createAttribute('generated');
$root->appendChild($cGenerated);
$cGenerated->appendChild($docout->createTextNode(date('Ymd hi',time()))); 

// CNML class description
$cClass = $docout->createElement('class');
$class = $root->appendChild($cClass);
$cType = $docout->createAttribute('node_description');
$cClass->appendChild($cType);
$cType->appendChild($docout->createTextNode($nodes)); 
$cMap = $docout->createAttribute('Mapping');
$cClass->appendChild($cMap);
$cMap->appendChild($docout->createTextNode('yes')); 



// iterate over the xpath result elements and add them to the output
foreach ($entries as $entry)
  $root->appendChild($docout->importNode($entry, true));
  
// Create output
header('Content-type: application/xml; charset=utf-8');
echo $docout->saveXML();


?>