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
 * EID to add/delete/move bookmarks
 *
 * @author	Emilie Sagniez <emilie@in-cite.net>
 * @package	TYPO3
 * @subpackage	tx_icsbookmarks
 */
class tx_icsbookmarks_manage implements t3lib_singleton {
	
	var $extKey = 'ics_bookmarks';	// The extension key.
	var $table 	= 'tx_icsbookmarks';
	var $libs = null;
	
	
	/**
	 *	Main function
	 *
	 *	@return int/boolean : position of bookmark (case add, up or down) or true/false
	 */
	function exec() {
		$params = t3lib_div::_GET();
		
		if ($params['action'] != 'add'
			&& $params['action'] != 'delete'
			&& $params['action'] != 'up'
			&& $params['action'] != 'down') {
			return false;
		}
		
		tslib_eidtools::connectDB();
		
		$this->libs = t3lib_div::makeInstance('tx_icsbookmarks_libs');
		$this->libs->initCookie();
		
		$ret = false;
		switch ($params['action']) {
			case 'add':
				$ret = $this->execAdd($params['type'], $params['data']);
				break;
			case 'delete':
				$ret = $this->execDelete($params['type'], $params['sorting']);
				break;
			case 'up':
				$ret = $this->execUpDown(true, $params['type'], $params['sorting']);
				break;
			case 'down':
				$ret = $this->execUpDown(false, $params['type'], $params['sorting']);
				break;
		}
		
		if ($params['redirect'] &&!$params['ajax']) {
			if($params['action'] == 'delete') {
				$additionnalParams = '&changeBookmark=1';
			}
			else {
				$additionnalParams = '&changeBookmark=0';
			}
		
			header('Location: ' . $params['redirect'] . $additionnalParams);
			exit();
		}
		
		if ($params['ajax'])
			echo $ret;
		
		return $ret;
	}	
	
	/**
	 *	Insert bookmark
	 *
	 *	@param	string	$type	bookmark type
	 *	@param	mixed	$data	bookmark data
	 *	@return boolean
	 */
	function execAdd($type, $data) {
		if (!$type || !$data)
			return false;
		
		$position = 0;
		// récupérer la dernière position
		$sortings = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'sorting', 
			$this->table, 
			'`cookie` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($_COOKIE[$this->extKey], $this->table) . ' 
				AND `type` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($type, $this->table), 
			'',
			'`sorting` desc',
			1
		);
		if (is_array($sortings) && !empty($sortings))
			$position = $sortings[0]['sorting'] + 1;
			
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			$this->table, 
			array(
				'cookie' => $_COOKIE[$this->extKey],
				'type' => $type,
				'data' => $data,
				'sorting' => $position,
				'tstamp' => time(),
			)
		);
		
		return $this->libs->getURL(tx_icsbookmarks_libs::ACTION_DELETE, $type, $position);
	}
	
	/**
	 *	Delete bookmark and change positions of next bookmarks
	 *
	 *	@param	string	$type		bookmark type
	 *	@param	int		$sorting	bookmark position
	 *	@return boolean
	 */
	function execDelete($type, $sorting) {
		if (!$type || !isset($sorting))
			return false;
		
		// récupérer le data pour générer l'url d'ajout
		$bookmark = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'`data`',
			$this->table, 
			'`cookie` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($_COOKIE[$this->extKey], $this->table) . ' 
				AND `type` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($type, $this->table) . '
				AND `sorting` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($sorting, $this->table)
		);
		
		if (is_array($bookmark) && !empty($bookmark)) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				$this->table, 
				'`cookie` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($_COOKIE[$this->extKey], $this->table) . ' 
					AND `type` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($type, $this->table) . '
					AND `sorting` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($sorting, $this->table)
			);
			
			// décrémenter les positions des favoris suivants
			$req = 'UPDATE `' . $this->table . '` 
				SET `sorting` = (`sorting`-1)
				WHERE 
					`cookie` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($_COOKIE[$this->extKey], $this->table) . '
					AND `type` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($type, $this->table) . ' 
					AND `sorting` > ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($sorting, $this->table);
					
			$GLOBALS['TYPO3_DB']->sql_query($req);
			
			return $this->libs->getURL(tx_icsbookmarks_libs::ACTION_ADD, $type, $bookmark[0]['data']);
		} 
		return false;
	}
	
	/**
	 *	Move bookmark (up or down)
	 *
	 *	@param	boolean	$up		true if up, false if down
	  *	@param	string	$type		bookmark type
	 *	@param	int		$sorting	bookmark position
	 *	@return boolean
	 */
	function execUpDown($up, $type, $sorting) {
		if (!$type || !isset($sorting) 
			|| ($up && !$sorting)) 
			return false;
		
		/*
			Exemple: bookmark position 2:
			Up: 	Il va passer en position 1, et celui en position 1 va passer en 2
				2 => 1
				1 => 2
			Down:	Il va passer en position 3, et celui en position 3 va passer en position 2
				2 => 3
				3 => 2
				
				=> gérer les cas particuliers: 	
					- down du 0 (premier) => sorting peut être à 0
					- up du 0 (premier) => retourne faux si sorting = 0 et up 
					- down du dernier => vérifier qu'il ne s'agit pas du dernier
		*/
		
		if (!$up && $sorting) {
			// cas particulier: on cherche à descendre d'une place le dernier bookmark
			$sortings = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'sorting', 
				$this->table, 
				'`cookie` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($_COOKIE[$this->extKey], $this->table) . ' 
					AND `type` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($type, $this->table), 
				'',
				'`sorting` desc',
				1
			);
			if (is_array($sortings) && !empty($sortings) && $sortings[0]['sorting'] == $sorting)
				return false;
				
		}
		// $sortingUp => le bookmark qui va descendre d'une place (sorting + 1) 
		// $sortingDown => le bookmark qui va grimper d'une place (sorting - 1)
		
		if ($up) {
			$sortingUp = $sorting-1;
			$sortingDown = $sorting;
			$finalSorting = $sortingUp;
		} else {
			$sortingUp = $sorting;
			$sortingDown = $sorting+1;
			$finalSorting = $sortingDown;
		}
		
		$req = 'UPDATE `' . $this->table .'`
				SET `sorting` = CASE `sorting`
						WHEN ' . $sortingUp . ' THEN ' . $sortingDown . '
						WHEN ' . $sortingDown . ' THEN ' . $sortingUp . '
					END
				WHERE 
					`cookie` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($_COOKIE[$this->extKey], $this->table) . '
					AND `type` = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($type, $this->table) . ' 
					AND `sorting` IN (' . $sortingUp . ', ' . $sortingDown . ')
		';
		
		$GLOBALS['TYPO3_DB']->sql_query($req);
		
		return $finalSorting;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_bookmarks/class.tx_icsbookmarks_manage.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_bookmarks/class.tx_icsbookmarks_manage.php']);
}

?>