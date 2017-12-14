<?php
/*
 * Created on 24/09/2008
 *
 * Main call to CNML services
 */

// Common Bootstrap
if (file_exists('common/config.php'))
  include_once("common/config.php");
else
  include_once("common/config.php.template");

include_once("common/misc.php");

$VERSION = '0.2.3';

function call_service($service) {
	if (file_exists('services/'.$service.'.php'))
	  include_once('services/'.$service.'.php');
	else {
    header("Content-Type: text/plain");
    echo "ERROR: Service $service unknown\n";
    getHelp();
    exit;
	}

  if (isset($_GET['info'])) {
    header("Content-Type: text/plain");
    if (function_exists($service.'_info'))
      call_user_func($service.'_info');
    else
      echo "No information available for $service\n";
    exit;
  }

  if (function_exists($service.'_main'))
    call_user_func($service.'_main');
  else
    echo "ERROR: No main hook for $service\n";
}

function getServerInfo() {
	global $VERSION;
	global $SNPGraphServerId;
	global $rootZone;
	global $rrdtool_version;
	global $CNMLSource;

  header("Content-Type: text/plain");
	echo "CNML services version $VERSION at ";
	echo exec('uname -a');
	echo "\n";
	echo "Server id: $SNPGraphServerId\n";
	echo "Root zone: $rootZone\n";
	echo "CNML source: $CNMLSource\n";
	echo "php version: ";
	echo system('php -v');
	echo "\n";
	echo "rrdtool version: ";
	echo exec("rrdtool|head -1|cut -f 2 -d' '");
	echo "  set to: $rrdtool_version \n";

	echo "\n";

}

function getHelp() {
	global $VERSION;

	echo "CNML services\n";
	echo "Version: ".$VERSION."\n";
  echo "USAGE:\n" .
  		"index.php?call=[service][&parameter[=value]]\n" .
  		"\n" .
  		"services: help version phpinfo serverinfo [service]\n" .
  		"  help\n" .
  		"    this message\n" .
  		"  version\n" .
  		"    gets version information\n" .
  		"  phpinfo\n" .
  		"    gets php version information\n" .
  		"  serverinfo\n" .
  		"    gets server information\n" .
  		"  [service]\n" .
  		"    name of the CNML service\n" .
  		"    optional parameters:\n" .
  		"      info\n" .
  		"         obtain the parameters & information of the service\n" .
  		"\n";
  echo "Available services:\n";
  $fservices = glob('services/*.php');

  foreach ($fservices as $fservice)
  	$services[] .= basename($fservice,'.php');
  echo implode('|',$services);
  echo "\n\nServices description:\n\n";
  foreach ($services as $service)
  	if (file_exists('services/'.$service.'.php')) {
	    include_once('services/'.$service.'.php');
      if (function_exists($service.'_info')) {
      	echo "--------------------------------------------------------------\n";
        call_user_func($service.'_info');
      }
    }
}

if (!isset($_GET['call'])) {
  header("Content-Type: text/plain");
  echo "ERROR: No service specified\n";
  getHelp();
  exit;
} else
  $service = $_GET['call'];


switch ($service) {
    case 'version':
        header("Content-Type: text/plain");
        echo $VERSION;
        exit;
    case 'help':
        header("Content-Type: text/plain");
        getHelp();
        break;
    case 'phpinfo':
        //echo phpinfo();
        //break;
    case 'serverinfo':
        //echo getServerInfo();
        //break;
    case 'disabled':
        echo "This call is disabled for security reasons. " .
        "See <a href=\"https://github.com/guifi/snpservices/issues/6\">" .
        "https://github.com/guifi/snpservices/issues/6</a> " .
        "for more details.";
        break;
    default:
        call_service($service);
}


?>
