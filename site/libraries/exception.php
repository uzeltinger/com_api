<?php
/**
 * @package com_api
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API) 
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
 */

class ApiException extends Exception
{
	var $http_code = 400;
}
