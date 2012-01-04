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

/**
 * 
 *
 * @author	Emilie Sagniez <emilie@in-cite.net>
 * @package	TYPO3
 * @subpackage	tx_icsbookmarks
 */
class tx_icsbookmarks_libs {
	
	var $extKey = 'ics_bookmarks';	// The extension key.
	var $table 	= 'tx_icsbookmarks';
	var $type_timestamps = 'times';
	
	const ACTION_ADD = 'add'; 
	const ACTION_DELETE = 'delete'; 
	const ACTION_UP = 'up'; 
	const ACTION_DOWN = 'down'; 
	
	/**
	 * Constructor 
	 *
	 * @return void 
	 */
	public function __construct() {
		
	}
	
	/**
	 * 	Initialize cookie, create if not exist, update expire date if exist
	 *	@return void
	 */
	function initCookie() {
		/* PHP 5.3 $endCookie = new DateTime();
		$endCookie->add(new DateInterval('P1Y'));
		$endTime = $endCookie->format('U');*/
		$endTime = time()+60*60*24*364;
		
		if (!$_COOKIE[$this->extKey]) {
			// si pas de cookie, on en crée un => il faut lui attribuer un identifiant unique
			$unikid = false;
			do {
				$unikid = uniqid(true);
				$unikid = substr($unikid, 0, 16);
				
				$cookies_db = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'cookie', 
					$this->table, 
					'`cookie` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($unikid, $this->table)
				);
				if (is_array($cookies_db) && !empty($cookies_db))
					$unikid = false;
			} while(!$unikid);
			
			setcookie($this->extKey, $unikid, $endTime);
			$_COOKIE[$this->extKey] = $unikid;
			$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				$this->table, 
				array(
					'cookie' => $unikid,
					'type' => $this->type_timestamps,
					'data' => time(),
					'tstamp' => time(),
				)
			);
				
		} else {
			// il y a un cookie, on le réinitialise et on met à jour la date dans l'enregistrement type "timestamp"
			setcookie($this->extKey, $_COOKIE[$this->extKey], $endTime);
			
			// vérifier que l'enregistrement times existe toujours (j'ai eu le cas après avoir tout supprimé pour mes tests)
			$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'`cookie`',
				$this->table, 
				'`cookie` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($_COOKIE[$this->extKey], $this->table) . ' 
					AND `type` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->type_timestamps, $this->table)
			);
	
			if (!is_array($records) || empty($records)) {
				$GLOBALS['TYPO3_DB']->exec_INSERTquery(
					$this->table, 
					array(
						'cookie' => $_COOKIE[$this->extKey],
						'type' => $this->type_timestamps,
						'data' => time(),
						'tstamp' => time(),
					)
				);
			} else {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					$this->table, 
					'`cookie` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($_COOKIE[$this->extKey], $this->table) . ' 
						AND `type` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->type_timestamps, $this->table), 
					array(
						'data' => time(),
						'tstamp' => time(),
					)
				);
			}
		}
	}
		
	/**
	 * 	Get eID link 
	 *
	 *	@param	string	$action	add/delete/up/down
	 *	@param	string	$type	bookmark type
	 *	@param	mixed	: data if add, else sorting
	 *	@return string link
	 */
	function getURL($action, $type, $data, $redirect = '') {
		$params = array();
		$params['eID'] = 'ics_bookmarks';
		$params['type'] = $type;
		
		switch ($action) {
			case self::ACTION_ADD:
				$params['action'] = 'add';
				$params['data'] = $data;
				break;
			case self::ACTION_DELETE:
			case self::ACTION_UP:
			case self::ACTION_DOWN:
				$params['action'] = $action;
				$params['sorting'] = $data;
				break;
		}
		
		if ($redirect)
			$params['redirect'] = $redirect;
			
		return t3lib_div::getIndpEnv('TYPO3_SITE_URL') . '?' . t3lib_div::implodeArrayForUrl('', $params);
	}
	
	/**
	 *	Get type's bookmarks
	 *	
	 *	@param	string	$type	bookmark type
	 *	@return	array
	 */
	function getBookmarks($type) {
		$bookmarks = array();
		$bookmarksDB = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'`data`, `sorting`', 
			$this->table, 
			'`cookie` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($_COOKIE[$this->extKey], $this->table) . ' 
				AND `type` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($type, $this->table), 
			'',
			'`sorting`'
		);
		
		if (is_array($bookmarksDB) && !empty($bookmarksDB)) {
			foreach ($bookmarksDB as $bookmark) {
				$bookmarks[$bookmark['data']] = $bookmark['sorting'];
			}
		}
		return $bookmarks;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_bookmarks/class.tx_icsbookmarks_libs.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_bookmarks/class.tx_icsbookmarks_libs.php']);
}

?>