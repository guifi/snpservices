<?php
/*
 * Created on 24/09/2008
 *
 * example "Hello CNML World!" CNML service to be used as a template for 
 * developing new services
 */
 
// info hook
// provides a message with information about the service and how to use it
function example_info() {
	echo "example";
  echo "\n\n";

	echo "outputs a 'Hello CNML World!' message\n";
	echo "to be used as a reference/template for developers who want to create new services\n";
	echo "\n";
}

// main hook
// provides the service
function example_main() {
	echo "Hello CNML World!\n\n";
}

?>
