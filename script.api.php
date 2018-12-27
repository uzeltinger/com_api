<?php
/**
 * @package     Com.Api
 *
 * @copyright   Copyright (C) 2005 - 2017 Techjoomla, Techjoomla Pvt. Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die();

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
jimport('joomla.application.component.controller');

if (! defined('DS'))
{
	define('DS', DIRECTORY_SEPARATOR);
}

/**
 * API Installation class
 *
 * @since  1.0
 */
class Com_ApiInstallerScript
{
	// Used to identify new install or update
	private $componentStatus = "install";

	private $installationQueue = array(
			// Modules => { (folder) => { (module) => { (position), (published) } }* }*
			'modules' => array(
				'admin' => array(), 'site' => array()
			),

			// Plugins => { (folder) => { (element) => (published) }* }*
			'plugins' => array(
				'system' => array()
			),

			// Libraries
			'libraries' => array()
	);

	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @param   STRING  $type    type
	 * @param   STRING  $parent  parent
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function preflight($type, $parent)
	{
	}

	/**
	 * Runs after install, update or discover_update
	 *
	 * @param   string      $type    install, update or discover_update
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function postflight($type, $parent)
	{
		$msgBox = array();

		// Install subextensions
		$status = $this->installSubextensions($parent);

		/*
		 * Show the post-installation page
		 * $this->renderPostInstallation($status, $parent, $msgBox);
		 */
	}

	/**
	 * method to install the component
	 *
	 * @param   STRING  $parent  parent
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function install($parent)
	{
	}

	/**
	 * Runs on uninstallation
	 *
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function uninstall($parent)
	{
	}

	/**
	 * method to update the component
	 *
	 * @param   STRING  $parent  parent
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function update($parent)
	{
		$this->componentStatus = "update";
	}

	/**
	 * Renders the post-installation message
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	private function renderPostInstallation()
	{
	}

	/**
	 * Installs subextensions (modules, plugins) bundled with the main extension
	 *
	 * @param   JInstaller  $parent  parent
	 *
	 * @return JObject  The subextension installation status
	 *
	 * @since 1.0
	 */
	private function installSubextensions($parent)
	{
		$src = $parent->getParent()->getPath('source');

		$db = JFactory::getDbo();

		$status = new JObject;
		$status->modules = array();
		$status->plugins = array();

		// Modules installation

		if (count($this->installationQueue['modules']))
		{
			foreach ($this->installationQueue['modules'] as $folder => $modules)
			{
				if (count($modules))
				{
					foreach ($modules as $module => $modulePreferences)
					{
						// Install the module
						if (empty($folder))
						{
							$folder = 'site';
						}

						$path = "$src/modules/$folder/$module";

						// If not dir
						if (! is_dir($path))
						{
							$path = "$src/modules/$folder/mod_$module";
						}

						if (! is_dir($path))
						{
							$path = "$src/modules/$module";
						}

						if (! is_dir($path))
						{
							$path = "$src/modules/mod_$module";
						}

						if (! is_dir($path))
						{
							$fortest = '';

							// Continue;
						}

						// Was the module already installed?
						$sql = $db->getQuery(true)
							->select('COUNT(*)')
							->from('#__modules')
							->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
						$db->setQuery($sql);

						$count = $db->loadResult();
						$installer = new JInstaller;
						$result = $installer->install($path);
						$status->modules[] = array(
							'name' => $module, 'client' => $folder, 'result' => $result, 'status' => $modulePreferences[1]
						);

						// Modify where it's published and its published state
						if (! $count)
						{
							// A. Position and state
							list ($modulePosition, $modulePublished) = $modulePreferences;

							if ($modulePosition == 'cpanel')
							{
								$modulePosition = 'icon';
							}

							$sql = $db->getQuery(true)
								->update($db->qn('#__modules'))
								->set($db->qn('position') . ' = ' . $db->q($modulePosition))
								->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));

							if ($modulePublished)
							{
								$sql->set($db->qn('published') . ' = ' . $db->q('1'));
							}

							$db->setQuery($sql);
							$db->query();

							// B. Change the ordering of back-end modules to 1 + max ordering
							if ($folder == 'admin')
							{
								$query = $db->getQuery(true);
								$query->select('MAX(' . $db->qn('ordering') . ')')
									->from($db->qn('#__modules'))
									->where($db->qn('position') . '=' . $db->q($modulePosition));
								$db->setQuery($query);
								$position = $db->loadResult();
								$position ++;

								$query = $db->getQuery(true);
								$query->update($db->qn('#__modules'))
									->set($db->qn('ordering') . ' = ' . $db->q($position))
									->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
								$db->setQuery($query);
								$db->query();
							}

							// C. Link to all pages
							$query = $db->getQuery(true);
							$query->select('id')
								->from($db->qn('#__modules'))
								->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
							$db->setQuery($query);
							$moduleid = $db->loadResult();

							$query = $db->getQuery(true);
							$query->select('*')
								->from($db->qn('#__modules_menu'))
								->where($db->qn('moduleid') . ' = ' . $db->q($moduleid));
							$db->setQuery($query);
							$assignments = $db->loadObjectList();
							$isAssigned = ! empty($assignments);

							if (! $isAssigned)
							{
								$o = (object) array(
									'moduleid' => $moduleid, 'menuid' => 0
								);

								$db->insertObject('#__modules_menu', $o);
							}
						}
					}
				}
			}
		}

		// Plugins installation
		if (count($this->installationQueue['plugins']))
		{
			foreach ($this->installationQueue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$path = "$src/plugins/$folder/$plugin";

						if (! is_dir($path))
						{
							$path = "$src/plugins/$folder/plg_$plugin";
						}

						if (! is_dir($path))
						{
							$path = "$src/plugins/$plugin";
						}

						if (! is_dir($path))
						{
							$path = "$src/plugins/plg_$plugin";
						}

						if (! is_dir($path))
						{
							continue;
						}

						// Was the plugin already installed?
						$query = $db->getQuery(true)
							->select('COUNT(*)')
							->from($db->qn('#__extensions'))
							->where('( ' . ($db->qn('name') . ' = ' . $db->q($plugin)) . ' OR ' .
								($db->qn('element') . ' = ' . $db->q($plugin)) . ' )'
							)
							->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($query);
						$count = $db->loadResult();

						$installer = new JInstaller;
						$result = $installer->install($path);

						$status->plugins[] = array(
							'name' => $plugin, 'group' => $folder, 'result' => $result, 'status' => $published
						);

						if ($published && ! $count)
						{
							$query = $db->getQuery(true)
								->update($db->qn('#__extensions'))
								->set($db->qn('enabled') . ' = ' . $db->q('1'))
								->where('( ' . ($db->qn('name') . ' = ' . $db->q($plugin)) . ' OR ' .
									($db->qn('element') . ' = ' . $db->q($plugin)) . ' )'
								)
								->where($db->qn('folder') . ' = ' . $db->q($folder));
							$db->setQuery($query);
							$db->query();
						}
					}
				}
			}
		}

		// Library installation
		if (count($this->installationQueue['libraries']))
		{
			foreach ($this->installationQueue['libraries'] as $folder => $status1)
			{
				$path = "$src/libraries/$folder";

				$query = $db->getQuery(true)
					->select('COUNT(*)')
					->from($db->qn('#__extensions'))
					->where('( ' . ($db->qn('name') . ' = ' . $db->q($folder)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($folder)) . ' )')
					->where($db->qn('folder') . ' = ' . $db->q($folder));
				$db->setQuery($query);
				$count = $db->loadResult();

				$installer = new JInstaller;
				$result = $installer->install($path);

				$status->libraries[] = array(
					'name' => $folder, 'group' => $folder, 'result' => $result, 'status' => $status1
				);

				if ($published && ! $count)
				{
					$query = $db->getQuery(true)
						->update($db->qn('#__extensions'))
						->set($db->qn('enabled') . ' = ' . $db->q('1'))
						->where('( ' . ($db->qn('name') . ' = ' . $db->q($folder)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($folder)) . ' )')
						->where($db->qn('folder') . ' = ' . $db->q($folder));
					$db->setQuery($query);
					$db->query();
				}
			}
		}

		return $status;
	}
}
