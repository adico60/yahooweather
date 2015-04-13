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
 * Yahoo! API Helper Class for Yahoo! Weather Widget
 *
 * @author	Juergen Furrer <juergen.furrer@gmail.com>
 * @package	TYPO3
 * @subpackage	tx_yahooweather
 */
class yahooApiResolver extends tx_yahooweather_pi1 {

	//API caching time
	var $cachetime;
	//API location code
	var $location_code;
	//API url
	var $weather_api_url;
	//API cache path
	var $cache_path;
	//API cache name
	var $cache_name;
	//API unit
	var $unit;
	//API timeout
	var $timeout;
	//Enable API cache
	var $useCache;
	// API clearCache Intervall
	var $clearCacheIntervall;
	
	/**
	 * The class constructor
	 *
	 */
	public function __construct() {
		$this ->cachtime = 3600;
		$this ->cache_path = "/cache/";
		$this ->clearCacheIntervall = 1;
		$this ->location_code = "GMXX3828";
		$this ->useCache = true;
		$this ->unit = "c";
		$this ->timeout = 5;
	}
	
	/**
	 * Setter for cachePath
	 *
	 * @param	string		$path: The Cache path
	 */
	public function setCachePath($path) {
		if (! preg_match("/\/$/", $path)) {
			$path = $path . "/";
		}
		$this->cache_path = $path;
	}
	
	/**
	 * Setter for cacheName
	 *
	 * @param	string		$path: The Cache name
	 */
	public function setCacheName($name) {
		$this->cache_name = $name;
	}
	
	/**
	 * Setter for cacheTime
	 *
	 * @param	string		$time: The time
	 */
	public function setCacheTime($time) {
		$this->cachetime= $time * 60;
	}
	
	/**
	 * Setter for location
	 *
	 * @param	string		$loc: The location
	 */
	public function setLocationCode($loc) {
		$this->location_code = $loc;
	}
	
	/**
	 * Setter for ApiUrl
	 *
	 * @param	string		$url: The URL
	 */
	public function setApiUrl($url) {
		$this->api_url = $url;
	}
	
	/**
	 * Setter for unit
	 *
	 * @param	string		$unit: The unit
	 */
	public function setUnit($unit) {
		$this->unit = $unit;
	}
	
	/**
	 * Setter for timeout
	 *
	 * @param	string		$time: The timeout
	 */
	public function setTimeout($time) {
		$this->timeout = $time;
	}
	
	/**
	 * Enable the API Cache
	 *
	 * @param	string		$bool: true/false
	 */
	public function enableCache($bool) {
		$this->useCache = (bool)$bool;	//Cast flexform integer (0/1) to bool
	}
	
	/**
	 * Setter for clearCache intervall
	 */
	
	public function setClearCacheIntervall($days) {
		$this->clearCacheIntervall = $days;
	}
	
	/**
	 * Clean the API-Cache
	 *
	 */
	public function cleanCache() {
		if ($handle = opendir($this->cache_path)) {
			while (false !== ($file = readdir($handle))) {
				if($file != "." && $file != "..") {
					if(@filemtime($this->cache_path.$file) < time()-$this->clearCacheIntervall*86400){
						unlink($this->cache_path.$file);
					}
				}
			}
			closedir($handle);
		}
	}

	/**
	 * Validate if given string is json
	 * 
	 * @param 	string	$string: the string for validation	
	 * @return	bool
	 */
	private function isJson($string) {
		json_decode($string);
		return (json_last_error() == "JSON_ERROR_NONE");
	}

	/**
	 * Get the weather data from yahoo api
	 * @return	API Data in assoc Format
	 */
	 
	public function getWeatherData() {
		$this->cleanCache();
		if (!$this->useCache || !file_exists($this->cache_path) || @filemtime(($this->cache_path) + $this ->cachtime  < time()) || file_get_contents($this->cache_path) == "") {
			$weather_api_url = "http://query.yahooapis.com/v1/public/yql?q=select+%2A+from+weather.forecast+where+woeid%3D%22".$this->location_code."%22+and+u%3D%22".$this->unit."%22&format=json";
			$rss_api_url = "http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20rss%20where%20url%3D%27http%3A%2F%2Fweather.yahooapis.com%2Fforecastrss%3Fw%3d".$this->location_code."%26u%3d".$this->unit."%27&format=json";

			$data = $this->getContentFromUrl($weather_api_url);
			$data_forecast = $this->getContentFromUrl($rss_api_url);
			if ($this->isJson($data) && $this->isJson($data_forecast)) {
				$weather_assoc = json_decode($data, true);
				if ($weather_assoc["query"]["results"]["channel"]["title"] == "Yahoo! Weather - Error") {
					return false;
				}
				$weather_assoc_forecast = json_decode($data_forecast,true);
				$weather_assoc["query"]["results"]["channel"]["item"]["forecast"]=$weather_assoc_forecast["query"]["results"]["item"]["forecast"];
				if ($this->useCache) {
					file_put_contents($this->cache_path.$this->cache_name, json_encode($weather_assoc));
				}
			}else{
					return false;
			}
		} else {
			$weather_assoc = json_decode(file_get_contents($this->cache_path.$this->cache_name));
		}
		
		return $weather_assoc["query"]["results"]["channel"];
	}

	/**
	 * Returns the Content of an URL
	 * 
	 * @param string $url
	 */
	private function getContentFromUrl($url) {
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse']) {
			// Open url by curl
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER , false);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, 'tx_error404multilingual=1');
			//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyTunnel']) {
				curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyTunnel']);
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']) {
				curl_setopt($ch, CURLOPT_PROXY, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']);
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass']) {
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass']); 
			}
			$data = curl_exec($ch);
			curl_close($ch);
		} else {
			$opts = array('http' =>array('timeout' => $this->timeout));
			$context  = stream_context_create($opts);
			// Open url by fopen
			set_time_limit(5);
			$data = file_get_contents($url, false, $context, -1, 40000);
		}
		
		return $data;
	}
}
?>
