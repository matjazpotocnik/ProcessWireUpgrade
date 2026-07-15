<?php namespace ProcessWire;

class ProcessWireUpgradeCheckConfig extends ModuleConfig {
	public function __construct() {
		$this->add([
			[
				'name' => 'useLoginHook',
				'type' => 'radios',
				'label' => $this->_('Check for upgrades on login?'),
				'description' => $this->_('When enabled, the system will automatically check for upgrades in the background whenever a Superuser logs in. When disabled, upgrades are only checked manually via Setup > Upgrades.'),
				'notes' => $this->_('Useful if you want to be notified of upgrades without visiting Setup > Upgrades. Checks are cached, so this won\'t slow down logins.'),
				'options' => [
					'0' => $this->_('Only check manually'),
					'1' => $this->_('Check automatically on login')
				],
				'columnWidth' => 25,
				'value' => '0',
			],

			[
				'name' => 'showUninstalled',
				'type' => 'radios',
				'label' => $this->_('Check Uninstalled Modules?'),
				'description' => $this->_('By default, only currently installed modules are checked for upgrades. Enabling this will also check uninstalled modules that exist in your /site/modules/ directory.'),
				'notes' => $this->_('Useful when you\'ve manually uploaded module files and want to check for newer versions before installing them.'),
				'options' => [
					'0' => $this->_('Only check installed modules'),
					'1' => $this->_('Include uninstalled modules'),
				],
				'columnWidth' => 25,
				'value' => '0',
			],

			[
				'name' => 'useModuleInfoRepo',
				'type' => 'radios',
				'label' => $this->_('Prioritize Local GitHub URLs?'),
				'description' => $this->_('Select whether to prefer the GitHub URL found in the local module\'s "More Information" (href) property, or the official repository URL from the ProcessWire modules directory.'),
				'notes' => $this->_('Useful when you\'ve installed a custom fork and want to track its GitHub commits instead of the ProcessWire modules directory.'),
				'options' => [
					'0' => $this->_('Use the official directory repository'),
					'1' => $this->_('Prioritize the local module\'s GitHub URL'),
				],
				'columnWidth' => 25,
				'value' => '0',
			],

			[
				'name' => 'githubTrackingEnabled',
				'type' => 'radios',
				'label' => $this->_('Enable GitHub module tracking?'),
				'description' => $this->_('Checks module repositories for commits published without a version-number change. This can detect unreleased fixes and improvements not yet listed as a formal module update.'),
				'notes' => $this->_('The ProcessWire dev branch is checked automatically. This setting controls additional GitHub checks for site modules.'),
				'options' => [
				'0' => $this->_('No additional GitHub checks'),
				'1' => $this->_('Check GitHub for new commits'),
				],
				'columnWidth' => 25,
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
