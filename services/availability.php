<?php
/*
 * Created on 24/09/2008
 *
 * availability CNML service
 */

// info hook
// provides a message with information about the service and how to use it
function availability_info() {
	echo "availability\n\n";

	echo "provides a PNG image with information about the device status & availability\n";
	echo "parameters:\n";
	echo "  device=<device_id>\n";
	echo "    device id to gather statistics from\n";
	echo "  format=long|short\n";
	echo "    format of the PNG image, default format=long\n";
	echo "\n";
}

// main hook
// provides the service
function availability_main() {
	$format='long';
  if (isset($_GET['format']))
	  $format=$_GET['format'];

	$pingslast = guifi_get_pings($_GET['device'],time()-3600);
	if (!isset($pingslast['last_sample']))
	  $pingslast['last_sample'] = '--:--';
	$pings = guifi_get_pings($_GET['device']);
	if ($pings['samples'] > 0) {
		$available = sprintf("%.2f%%",$pings['succeed']);
		if ($pings['last_succeed'] == 0)
			$last = 'Down';
		else
			$last = 'Up';
	} else {
		$last = 'number';
	}
	if (isset($available)) {
      $var['available'] = $available;
      $var['last'] = $last;
	} else {
      $var['available'] = 'n/a';
      $var['last'] = 'Down';
	}

	// create a image
	if ($format=='short')
		$pixlen=77;
	else
		$pixlen=117;
	$im = imagecreate($pixlen, 15);

	// white background and blue text
	//$bg = imagecolorallocate($im,0x33, 0xff, 0);

	if ($var['last'] == "Up")
		$bg = imagecolorallocate($im,0x33, 0xff, 0);
	else if ($var['last'] == "Down")
		$bg = imagecolorallocate($im,0xff, 0x33, 0);
	else
		return;

	$textcolor = imagecolorallocate($im, 0, 0, 100);

	// write the string at the top left
	if ($format=="short")
		imagestring($im, 2, 3, 1, sprintf("%s (%s)",$var['last'],$var['available']), $textcolor);
	else
		imagestring($im, 2, 3, 1, sprintf("%s %s (%s)",$var['last'],$pingslast['last_sample'],$var['available']), $textcolor);

	// output the image
	header("Content-type: image/png");
	imagepng($im);
}

?>
