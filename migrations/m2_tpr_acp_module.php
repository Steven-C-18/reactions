<?php
/**
	* Topic/Post Reactions. Extends for the phpBB Forum Software package.
	* @author Steve <https://steven-clark.tech/>
*/

namespace steve\reactions\migrations;

class m2_tpr_acp_module extends \phpbb\db\migration\migration
{	
	static public function depends_on()
	{
		return array('\steve\reactions\migrations\m1_tp_reactions');
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
