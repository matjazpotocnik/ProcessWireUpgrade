<?php namespace ProcessWire;

class ProcessWireUpgradeCheckConfig extends ModuleConfig {
	public function __construct() {
		$this->add([
			[
				'name' => 'useLoginHook',
				'type' => 'radios',
				'label' => $this->_('Check for upgrades on superuser login?'),
				'description' => $this->_('When enabled, the system will automatically check for upgrades in the background whenever a Superuser logs in. When disabled, upgrades are only checked manually via Setup > Upgrades.'),
				'notes' => $this->_('Note: Automatic checks require ProcessWire 3.0.123 or newer.'),
				'options' => [
					'0' => $this->_('Only check manually (recommended)'),
					'1' => $this->_('Check automatically on login')
				],
				'columnWidth' => 33,
				//'optionColumns' => 1,
				'value' => '0',
			],

			[
				'name' => 'useModuleInfoRepo',
				'type' => 'radios',
				'label' => $this->_('Prioritize Local GitHub URLs?'),
				'description' => $this->_('Select whether to prefer the GitHub URL found in the local module\'s "More Information" (href) property, or the official repository URL from the ProcessWire modules directory.'),
				'notes' => $this->_('Useful when you\'ve installed a custom fork or unreleased beta and want to track commits via your specific GitHub link instead of the ProcessWire modules directory.'),
				'options' => [
					'0' => $this->_('Use the official directory repository (recommended)'),
					'1' => $this->_('Prioritize the local module\'s GitHub URL'),
				],
				'columnWidth' => 34,
				//'optionColumns' => 1,
				'value' => '0',
			],

			[
				'name' => 'showUninstalled',
				'type' => 'radios',
				'label' => $this->_('Check Uninstalled Modules?'),
				'description' => $this->_('By default, only currently installed modules are checked for upgrades. Enabling this will also check uninstalled modules that exist in your /site/modules/ directory.'),
				'notes' => $this->_('Useful when you\'ve manually uploaded module files and want to check for newer versions before installing them.'),
				'options' => [
					'0' => $this->_('Only check installed modules (recommended)'),
					'1' => $this->_('Include uninstalled modules'),
				],
				'columnWidth' => 33,
				//'optionColumns' => 1,
				'value' => '0',
			],

			[
				'name' => '_clearCache',
				'type' => 'checkbox',
				'label' => $this->_('Clear cache?'),
				'description' => $this->_('Check this box and save to clear the upgrade check cache.'),
			],

		]);
	}

	/**
	 * Override getInputfields to intercept form submission for runtime actions
	 */
	public function getInputfields() {

		if ($this->input->post('_clearCache')) {
			$this->cache->delete(ProcessWireUpgradeCheck::CACHE_PREFIX . '*');
			$this->session->removeAllFor('ProcessWireUpgrade');
			$this->session->removeAllFor('ProcessWireUpgradeCheck');

			$this->log->save(ProcessWireUpgradeCheck::LOG_FILENAME, '[INFO] Cache and session data cleared from config.');
			$this->message($this->_('Cache and session data for this module cleared.'));
		}

		return parent::getInputfields();
	}

}
