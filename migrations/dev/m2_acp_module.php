<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\migrations\dev;

class m2_acp_module extends \phpbb\db\migration\migration
{	
	static public function depends_on()
	{
		return ['\steve\reactions\migrations\dev\m1_reactions'];
	}

	public function update_data()
	{
		return [
			['module.add', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_REACTIONS_TITLE'
			]],
			['module.add', [
				'acp',
				'ACP_REACTIONS_TITLE',
				[
					'module_basename'	=> '\steve\reactions\acp\main_module',
					'modes'				=> ['settings', 'reactions'],
				],
			]],
		];
	}
}
