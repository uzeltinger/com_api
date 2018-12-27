<?php
/**
 * @package    Com.Api
 *
 * @copyright  Copyright (C) 2005 - 2017 Techjoomla, Techjoomla Pvt. Ltd. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die();

/**
 * key Table class
 *
 * @since  1.0
 */
class ApiTableStorekey extends JTable
{
	/**
	 * Hashed string stored in table
	 *
	 * @var    string
	 * @since  1.0
	 */
	public $hash;

	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  Database object
	 *
	 * @since  1.0
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__api_keys', 'id', $db);
		$this->setColumnAlias('published', 'state');
	}

	public function loadByHash($hash)
	{
		$this->load(array('hash' => $hash));
	}
}
