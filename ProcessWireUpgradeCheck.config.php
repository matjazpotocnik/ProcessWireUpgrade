<?php namespace ProcessWire;

class ProcessWireUpgradeCheckConfig extends ModuleConfig {
	public function __construct() {
		$this->add([
			[
				'name' => 'useLoginHook',
				'type' => 'radios',
				'label' => $this->_('Check for upgrades on superuser login?'),
				'description' => $this->_('If "No" is selected, then upgrades will only be checked manually when you click to Setup > Upgrades.'),
				'notes' => $this->_('Automatic upgrade check requires ProcessWire 3.0.123 or newer.'),
				'options' => [
					'1' => $this->_('Yes'),
					'0' => $this->_('No')
				],
				//'columnWidth' => 50,
				'optionColumns' => 1,
				'value' => 0,
			],
			[
				'name' => 'useModuleInfoRepo',
				'type' => 'radios',
				'label' => $this->_('Choose source for GitHub repo URL'),
				'description' => $this->_('Select whether to prefer the installed module\'s local repo URL from its module info array, or the repo URL from the ProcessWire modules directory.'),
				'notes' => $this->_('This only affects modules that provide a GitHub repo URL.'),
				'options' => [
					'0' => $this->_('Use directory repo URL'),
					'1' => $this->_('Use module info repo URL'),
				],
				//'columnWidth' => 50,
				'optionColumns' => 1,
				'value' => 0,
			],
		]);
	}
}
