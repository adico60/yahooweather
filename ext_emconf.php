<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "yahooweather".
 *
 * Auto generated 09-02-2015 21:00
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Yahoo Weather',
	'description' => 'Displays the weather and forecast for your location using the Yahoo! weather API. Please read and accept the terms of use by Yahoo! (http://developer.yahoo.com/weather/) before using the plugin!',
	'category' => 'plugin',
	'version' => '0.0.4',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_yahooweather',
	'clearcacheonload' => 0,
	'author' => 'Juergen Furrer',
	'author_email' => 'juergen.furrer@gmail.com',
	'author_company' => '',
	'constraints' => 
	array (
		'depends' => 
		array (
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.5.0-6.2.99',
		),
		'conflicts' => 
		array (
		),
		'suggests' => 
		array (
		),
	),
);

