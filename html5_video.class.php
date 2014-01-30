<?php
/*
	HTML5 Video class.
	
	Writes a properly formatted <video> tag into a web page, 
	with a minimum of fuss and with usable defaults.
	
	For usage details, see the file: HTML5_video_README.txt

	@author Charles Schoenfeld, Adams & Knight
	@version 1.3
	
	Version History:
	1.3:
		Added support for poster frames to the Flash fallback.
	
	1.2:
		Added the option to make the video loop, using ->setLoop(true).
		Added width & height attributes into the generated HTML tag.
		Moved higher-quality OGG to first position & lower-quality WebM to last position.
	
	1.1:
		Fixes for situations with alternate base URLs, basepaths, etc.
		Fix for OGV file extension.
	
	1.0:
		Initial Release		
*/

class html5_video {

	// HTML element properties
	var $height;
	var $width;
	var $elementID;
	private $use_controls;
	private $autoplay;
	private $loop;

	// URLs and directories
	private $using_amazon;
	private $amazon_root;
	private $local_basepath;
	private $local_baseurl;
	private $local_dir;
	private $flashURL;
	private $permitBlankBaseURL;
	
	// Media filenames
	var $filename_base;
	var $filename_webm;
	var $filename_mp4;
	var $filename_ogg;
	var $poster_image;
	
	function __construct($id=null, $filebase=null) {
		$this->using_amazon = false;
		if (!empty($id)) { $this->elementID = $id; }
		if (!empty($filebase)) { $this->setFilenames($filebase); }
		$this->using_amazon = false; // default
		$this->local_dir = 'media'; // default
		$this->use_controls = true; // default
		$this->autoplay = false; // default
		$this->loop = false; // default
		$this->permitBlankBaseURL = false; // default
	}
	
	public static function hsl($str) { 
		return htmlentities(stripslashes($str), ENT_COMPAT, 'UTF-8', false); 
	}
	
	public function use_amazon($url=null) {
		if (empty($url)) { throw new Exception('Amazon base URL was not specified.'); }
		$this->using_amazon = true;
		$this->amazon_root = $url;
		return true;
	}
	
	public function useControls($b=true) {
		$this->use_controls = ($b !== false);
	}
	
	public function setAutoplay($b=false) {
		$this->autoplay = ($b === true);
	}
	
	public function setLoop($b=false) {
		$this->loop = ($b === true);
	}
	
	public function setLocalDir($f=null) {
		if (empty($f)) { throw new Exception('Local media directory name was not specified.'); }
		$this->local_dir = $f;
		return true;
	}
	
	function setFilenames($base=null) {
		if (empty($base)) { throw new Exception('Base filename is empty.'); }
		$this->filename_base = $base;
		$this->filename_webm = $base . '.webm';
		$this->filename_mp4 = $base . '.mp4';
		$this->filename_ogg = $base . '.ogv';
		$this->poster_image = $base . '.jpg';
		return true;
	}
	
	public function setDimensions($width=null, $height=null) {
		if (!(isset($width) && isset($height) && is_numeric($width) && is_numeric($height))) {
			throw new Exception('Width and height for the video element were not specified or not numeric.');
		}
		if ($width < 1 || $height < 1) {
			throw new Exception('Width and height for the video element must be greater than zero.');
		}
		$this->width = $width;
		$this->height = $height;
		return true;
	}
	
	public function setBaseURL($str=null) {
		if (!empty($str)) { 
			$this->local_baseurl = $str; 
			return true; 
		}
		if (!empty($this->local_baseurl)) {
			return true; // Value has already been set.
		}
		if ($str === false || $this->permitBlankBaseURL === true) {
			$this->local_baseurl = ''; // Empty base url is desired.
			$this->permitBlankBaseURL = true;
			return true;
		}
		if (defined('BASE_URL')) { 
			$this->local_baseurl = BASE_URL; 
			return true; 
		}
		throw new Exception('Could not determine the base URL for this site.');
	}
	
	public function setBasepath($str=null) {
		if (!empty($str)) { 
			$this->local_basepath = $str; 
			return true; 
		}
		if (!empty($this->local_basepath)) {
			return true; // Value has already been set.
		}
		if (defined('BASEPATH')) { 
			$this->local_basepath = BASEPATH; 
			return true; 
		}
		throw new Exception('Could not determine the base filepath for this site.');
	}
	
	public function setFlashURL($str=null) {
		if (!empty($str)) { 
			$this->flashURL = $str; 
			return true; 
		}
		if (!empty($this->flashURL)) {
			return true; // Value has already been set.
		}
		$this->flashURL = $this->local_baseurl . 'flvplayer.swf'; // Default
		return true;
	}
	
	private function checkSources() {
		$hsl_webm = self::hsl($this->filename_webm);
		$hsl_mp4 = self::hsl($this->filename_mp4);
		$hsl_ogg = self::hsl($this->filename_ogg);
		$sources['webm'] = $this->local_baseurl . $this->local_dir . '/' . $hsl_webm;
		$sources['mp4'] = $this->local_baseurl . $this->local_dir . '/' . $hsl_mp4;
		$sources['ogg'] = $this->local_baseurl . $this->local_dir . '/' . $hsl_ogg;
		if ($this->using_amazon !== true) { return $sources; }
		
		// If using Amazon, check for files there.
		$amzn_webm = $this->amazon_root . $hsl_webm;
		$amzn_mp4 = $this->amazon_root . $hsl_mp4;
		$amzn_ogg = $this->amazon_root . $hsl_ogg;
		$amzn_webm_path = $this->amazon_root . urlencode($this->filename_webm);
		$amzn_mp4_path = $this->amazon_root . urlencode($this->filename_mp4);
		$amzn_ogg_path = $this->amazon_root . urlencode($this->filename_ogg);
		$webm_headers = get_headers($amzn_webm_path);
		$mp4_headers = get_headers($amzn_mp4_path);
		$ogg_headers = get_headers($amzn_ogg_path);
		if (is_array($webm_headers) && isset($webm_headers[0]) && is_string($webm_headers[0]) && (strpos($webm_headers[0], '200 OK') !== false)) {
			$sources['webm'] = $amzn_webm;
		}
		if (is_array($mp4_headers) && isset($mp4_headers[0]) && is_string($mp4_headers[0]) && (strpos($mp4_headers[0], '200 OK') !== false)) {
			$sources['mp4'] = $amzn_mp4;
		}
		if (is_array($ogg_headers) && isset($ogg_headers[0]) && is_string($ogg_headers[0]) && (strpos($ogg_headers[0], '200 OK') !== false)) {
			$sources['ogg'] = $amzn_ogg;
		}
		return $sources;
	}
	
	public function render($echo=true) {
		// Check for needed data.
		if (empty($this->local_baseurl)) { $this->setBaseURL(); }
		if (empty($this->local_basepath)) { $this->setBasepath(); }
		if (empty($this->filename_webm) && empty($this->filename_ogg) && empty($this->filename_mp4)) {
			throw new Exception('Media source files for the video element were not specified.');
		}
		
		// Render the opening video tag & its properties.
		$out = "\n<video ";
		if (!empty($this->elementID)) { $out .= 'id="'.$this->elementID.'" '; }
		if (empty($this->width) === false && empty($this->height) === false && is_numeric($this->width) === true && is_numeric($this->height) === true) {
			$out .= 'width="' . $this->width . '" height="' . $this->height . '" ';
		}
		if ($this->use_controls === true) { $out .= 'controls="controls" '; }
		if (empty($this->poster_image) === false && file_exists($this->local_basepath . $this->local_dir . '/' . $this->poster_image) === true) {
			$out .= 'poster="' . $this->local_baseurl . $this->local_dir . '/' . $this->poster_image . '" ';
			$flashposter = "&amp;image=" . $this->local_baseurl . $this->local_dir . '/' . $this->poster_image;
		} else {
			$flashposter = '';
		}
		if ($this->autoplay === true) {
			$out .= 'autoplay="autoplay" ';
		}
		if ($this->loop === true) {
			$out .= 'loop="loop" ';
		}
		$out .= ">\n";
		
		// Add the video sources.
		$sources = $this->checkSources();
		if (!empty($sources['ogg'])) {
			$out .= "\t<source src=\"" . $sources['ogg'] . "\" type='video/ogg; codecs=\"theora, vorbis\"'>\n";
		}
		if (!empty($sources['mp4'])) {
			$out .= "\t<source src=\"" . $sources['mp4'] . "\" type='video/mp4; codecs=\"avc1.42E01E, mp4a.40.2\"'>\n";
		}
		if (!empty($sources['webm'])) {
			$out .= "\t<source src=\"" . $sources['webm'] . "\" type='video/webm; codecs=\"vp8, vorbis\"'>\n";
		}
		
		// Add the Flash fallback. 
		// (Use local MP4 source, as Amazon doesn't support pseudo-streaming.)
		if (isset($this->flashURL) === false || empty($this->flashURL) === true) {
			$this->setFlashURL();
		}
		$out .= "\t<embed height=\"" . $this->height . "\" width=\"" . $this->width . "\" flashvars=\"wmode=transparent&amp;height=" . $this->height . "&amp;width=" . $this->width . "&amp;file=" . ($this->local_baseurl . $this->local_dir . '/' . self::hsl($this->filename_mp4)) . $flashposter . "\" allowfullscreen=\"true\" wmode=\"transparent\" quality=\"high\" name=\"player_" . $this->elementID . "\" style=\"undefined\" src=\"" . $this->flashURL . "\" type=\"application/x-shockwave-flash\">\n";
		
		// Close the video tag
		$out .= "</video>\n";
		
		// Return the HTML or write it to the page.
		if ($echo === false) { return $out; }
		echo $out; 
		return true;
	}

}
?>