<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\ucp;

class main_info
{
	function module()
	{
		return [
			'filename'	=> '\steve\reactions\ucp\reactions_module',
			'title'		=> 'UCP_REACTIONS_TITLE',
			'modes'		=> [
				'settings'	=> [
					'title'	=> 'UCP_REACTIONS_SETTING',
					'auth'	=> 'ext_steve/reactions && acl_u_manage_reactions_settings',
					'cat'	=> ['UCP_REACTIONS_SETTING']
				],
			],
		];
	}
}
