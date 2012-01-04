<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 In Cite Solution <technique@in-cite.net>
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

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'Manage bookmarks' for the 'ics_bookmarks' extension.
 *
 * @author	Emilie Sagniez <emilie@in-cite.net>
 * @package	TYPO3
 * @subpackage	tx_icsbookmarks
 */
class tx_icsbookmarks_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_icsbookmarks_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_icsbookmarks_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'ics_bookmarks';	// The extension key.
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
		$this->pi_initPIflexForm(); // Init and get the flexform data of the plugin
		
		$manageFF = $this->pi_getFFValue($this->cObj->data['pi_flexform'], 'manage');
		$manage = $manageFF ? true : false;
		if (!$manage && $this->conf['manage']) 
			$manage = true;
		
		$content='';
	
		$types = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey];
		if (!empty($types)) {
		
			$libs = t3lib_div::makeInstance('tx_icsbookmarks_libs');
			
			$nbBookmarks = 0;
			foreach ($types as $type => $class) {
				$nbBookmarks += count($libs->getBookmarks($type));
			}
			
			if($nbBookmarks) {
				foreach ($types as $type => $class) {
					$bookmarks = $libs->getBookmarks($type);
					$obj = t3lib_div::getUserObj($class);
					$obj->cObj = $this->cObj;
					$content .= $obj->viewBookmarks($bookmarks, $manage);
				}
			}
			else {
				$content .= $this->cObj->stdWrap($this->pi_getLL('noData'), $this->conf['noData_stdWrap.']);
			}
			
		}
		
		return $this->pi_wrapInBaseClass($content);
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_bookmarks/pi1/class.tx_icsbookmarks_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_bookmarks/pi1/class.tx_icsbookmarks_pi1.php']);
}

?>