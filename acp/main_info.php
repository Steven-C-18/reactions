<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\acp;

class main_info
{
	public function module()
	{
		return [
			'filename'	=> '\steve\reactions\acp\main_module',
			'title'		=> 'ACP_REACTIONS_TITLE',
			'modes'		=> [
				'settings'	=> [
					'title'		=> 'ACP_REACTIONS_SETTINGS',
					'auth'		=> 'ext_steve/reactions && acl_a_board',
					'cat'		=> ['ACP_REACTIONS_TITLE']
				],
				'reactions'	=> [
					'title'	 	=> 'ACP_REACTION_TYPES',
					'auth' 		=> 'ext_steve/reactions && acl_a_board',
					'cat' 		=> ['ACP_REACTIONS_TITLE']
				],
			],
		];
	}
}
