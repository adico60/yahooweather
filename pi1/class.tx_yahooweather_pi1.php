<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Juergen Furrer <juergen.furrer@gmail.com>
 *  Based on yahooweatherwidget from Ulrich Barrot <u.barrot@insiders-technologies.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once ("typo3conf/ext/yahooweather/pi1/class.yahoo_api_resolver.php");

/**
 * Plugin 'Yahoo Weather Widget' for the 'yahooweather' extension.
 *
 * @author	Juergen Furrer <juergen.furrer@gmail.com>
 * @package	TYPO3
 * @subpackage	tx_yahooweather
 */
class tx_yahooweather_pi1 extends tslib_pibase {
	var $prefixId = 'tx_yahooweather_pi1';
	// Same as class name
	var $scriptRelPath = 'pi1/class.tx_yahooweather_pi1.php';
	// Path to this script relative to the extension dir.
	var $extKey = 'yahooweather';
	// The extension key.
	var $pi_checkCHash = true;
	// Extension Basepath
	var $base_path = null;
	// Path to image icons
	var $imagepath = "";
	// Image extension type
	var $imageextension = "";

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->bootstrap();
		$yahooapi = new yahooApiResolver();
		$yahooapi->setUnit($this->conf["unit"]);
		$yahooapi->setTimeout($this->conf["timeout"]);
		$yahooapi->setLocationCode($this->conf["location"]);
		//Configure API Cache
		$yahooapi->setCacheTime($this->conf["cacheTime"]);
		$yahooapi->setCachePath(t3lib_div::getFileAbsFileName("uploads/tx_yahooweather"));
		$yahooapi->setCacheName("apicache_".substr($this->conf["crc"], 0, 10).".json");
		//Enable/Disable API Cache
		$yahooapi->enableCache($this->conf["enableCache"]);
		$api_result = $yahooapi->getWeatherData();
		if (!$api_result) {
			$result = $this->renderWeather(array(),false);
		} else {
			$result = $this->renderWeather($this->buildMarkerArray($api_result),true);
		}
		if ($conf['removeWrapInBaseClass'] == 1) {
			return $result;
		} else {
			return $this->pi_wrapInBaseClass($result);
		}
	}

	/**
	 * Application bootstrapping
	 *
	 * @return	bool
	 */
	function bootstrap() {
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexForm();
		$this->base_path = t3lib_extMgm::siteRelPath('yahooweather');
		$this->conf = $this->readConfig(
			array(
				"generaloptions" => array(
					"templateFile", //Flexform	/	TS
					"unit", 		//Flexform	/	TS
					"timeFormat", 	//Flexform	/	TS
					"dateFormat", 	//Flexform	/	TS
					"location", 	//Flexform	/	TS
					"imagePath", 	//				TS
					"imageExt", 	//				TS
					),
				"adminoptions" => array(
					"timeout", 		//Flexform	/	TS
					"cacheTime", 	//Flexform	/	TS
					"enableCache", 	//Flexform	/	TS
					"cleanCache",	//Flexform  /	TS
				)
			)
		);
		return true;
	}

	/**
	 * Read the Plugin Flexform and TS Setup
	 *
	 * @param	array array		$fields: The configuration fields
	 * @return	array 			The plugin configuration
	 */
	function readConfig($fields){
		if(!is_array($fields))
			return false;
		$configFields = $fields;
		$pluginConfig= array();
		$concatFields = "";
		foreach($configFields as $sheetname => $sheetcontent){
			foreach ($sheetcontent as $field){
				$flexval = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],$field, $sheetname);
				if($flexval == "" OR $flexval == "Typoscript"){
					$pluginConfig[$field] = $this->conf[$field];
				}
				else {
					$pluginConfig[$field] = $flexval;
				}
				$concatFields.=$pluginConfig[$field];
			}
			$pluginConfig["crc"] = md5($concatFields);
		}
		return $pluginConfig;
	}
	
	/**
	 * Building the typo3 marker array
	 *
	 * @param	array		$data: The API Data
	 * @return	The markerArray for template substitution
	 */
	function buildMarkerArray($data){
		//Convert api values to markerArray Values
		$weathercode = 0;
		$markerArray = array();
		foreach ($data as $key => $value){
			if (is_array($value)) {
				foreach ($value as $subkey => $subvalue){
					if (is_array($subvalue)){
						foreach($subvalue as $endkey => $endval){
							if (is_array($endval)){
								foreach($endval as $endk => $endv){
									$markerArray[$key."_".$subkey."_".$endkey."_".$endk] = $endv; 
								}
							} else {
								$markerArray[$key."_".$subkey."_".$endkey] = $endval; 
							}
						}
					} else {
						$markerArray[$key."_".$subkey] 		= $subvalue; 
					}
				}
			}
		}
		foreach ($markerArray as $marker => $value){
			if ($this->pi_getLL($marker) != "") {
				$markerArray[$marker] = $this->pi_getLL($marker);
			}
			$markerArray["###".$marker."###"] 			= $markerArray[$marker];
			unset($markerArray[$marker]);
		}
		
		//Custom Fields
		$markerArray["###astronomy_sunrise###"] 		= $this->convertTime($markerArray["###astronomy_sunrise###"]);
		$markerArray["###astronomy_sunset###"] 			= $this->convertTime($markerArray["###astronomy_sunset###"]);
		
		if ($markerArray["###wind_speed###"] == 0) {
			$markerArray["###wind_direction_text###"] 	= $this->pi_getLL("wind_0");
		} else {
			$markerArray["###wind_direction_text###"] 	= $this->convertWindDirection($markerArray["###wind_direction###"]);
		}
		$markerArray["###item_condition_text###"] 		= $this->pi_getLL("weather_code_".$markerArray["###item_condition_code###"]);
		$markerArray["###item_condition_image###"] 		= $this->conf["imagePath"].$markerArray["###item_condition_code###"].".".$this->conf["imageExt"] ;
		$markerArray["###item_condition_date###"] 		= date($this->conf["dateFormat"],time());
		
		//Generate custom forecast fields
		for ($i = 0; $i <= 4; $i++){
			$markerArray["###item_forecast_".$i."_condition###"] = $this->pi_getLL("weather_code_".$markerArray["###item_forecast_".$i."_code###"]);
			$markerArray["###item_forecast_".$i."_image###"]     = $this->conf["imagePath"].$markerArray["###item_forecast_".$i."_code###"].".".$this->conf["imageExt"] ;
			$markerArray["###item_forecast_".$i."_date###"]      = date($this->conf["dateFormat"],time()+$i*86400);
		}
		
		//End of custom fields
		
		return $markerArray;
	}

	/**
	 * Localization for template file
	 *
	 * @param	string		$html: The template code
	 * @return	Localized html
	 */
	function autoLocalization($html){
		//Format: {LL:var_name}
		preg_match_all ("/(\\{)(L)(L)(:)((?:[a-z][a-z0-9_]*))(\\})/is", $html, $matches);
		foreach($matches[0] as $match){
			$ll_key=substr($match, 4,-1);
			if ($this->pi_getLL($ll_key) != "") {
				$html=str_replace($match, $this->pi_getLL($ll_key), $html);
			} else {
				$html=str_replace($match, $this->pi_getLL("translation_error"), $html);
			}
		}
		
		return $html;
	}

	 /**
	 * Render Plugin HTML Output
	 *
	 * @param	array		$marker: The Marker Array
	 * @param	bool		$mode: Normal or error mode
	 * @return	The Plugin html output
	 */
	function renderWeather($marker,$mode) {
		$path = null;
		if (isset($this->conf['templateFile']))
			$path = $this->conf['templateFile'];
		else
			$path = 'EXT:yahooweather_dev/template/template.html';
		$this->templateCode = $this->cObj->fileResource($path);
		if ($mode) {
			$subparts['template'] = $this->cObj->getSubpart($this->templateCode, '###TEMPLATE###');
			$content = $this->cObj->substituteMarkerArrayCached($subparts['template'], $marker); 
		} else {
			$subparts['template'] = $this->cObj->getSubpart($this->templateCode, '###ERROR###');
			$content = $subparts['template']; 
		}
		$content=$this->autoLocalization($content);
		
		return $content;
	}
	
	/**
	 * Convert 12/24h Timeformat (12h is Yahoo API default)
	 *
	 * @param	string		$time: The API time
	 * @return	Formated time
	 */
	 function convertTime($time){
		if ($this->pluginconf["timeFormat"] == 12) {
			return $time;
		} else {
			$time_parts = explode(" ",$time);
			$time_h_s = explode(":",$time_parts[0]);
			$hours = $time_h_s[0];
			$label = "";
			if ($time_parts[1] == "pm")
				$hours+=12;
			$minutes = $time_h_s[1];//." ".$this->pi_getLL("time_label");
			
			return $hours." h ".$minutes;
	 	}
	 }
	 
	 /**
	 * Convert wind degree value to string
	 *
	 * @param	string		$degree: The API degree value
	 * @return	Word
	 */
	 function convertWindDirection($degree){
		$match = array(
			"0" 	=> $this->pi_getLL("wind_n"),
			"360" 	=> $this->pi_getLL("wind_n"),
			"22.5" 	=> $this->pi_getLL("wind_nne"),
			"45" 	=> $this->pi_getLL("wind_ne"),
			"67.5" 	=> $this->pi_getLL("wind_ene"),
			"90" 	=> $this->pi_getLL("wind_e"),
			"112.5" => $this->pi_getLL("wind_ese"),
			"135" 	=> $this->pi_getLL("wind_se"),
			"157.5" => $this->pi_getLL("wind_sse"),
			"180" 	=> $this->pi_getLL("wind_s"),
			"202.5" => $this->pi_getLL("wind_ssw"),
			"225" 	=> $this->pi_getLL("wind_sw"),
			"247.5" => $this->pi_getLL("wind_wsw"),
			"270" 	=> $this->pi_getLL("wind_w"),
			"292.5" => $this->pi_getLL("wind_wnw"),
			"315" 	=> $this->pi_getLL("wind_nw"),
			"337.5" => $this->pi_getLL("wind_nnw")
		);
		//Down Direction
		$steps_down = 0;
		$degree_down = $degree;
		while(!array_key_exists((String)$degree_down, $match)){
			$degree_down-=0.5;
			$steps_down++;
		}
		//Up Direction
		$steps_up = 0;
		$degree_up = $degree;
		while(!array_key_exists((String)$degree_up, $match)){
			$degree_up+=0.5;
			$steps_up++;
		}
		if($steps_up > $steps_down){
			return $match[(String)$degree_down];
		}else{
			return $match[(String)$degree_up];
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/yahooweather_dev/pi1/class.tx_yahooweather_dev_pi1.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/yahooweather_dev/pi1/class.tx_yahooweather_dev_pi1.php']);
}
?>
