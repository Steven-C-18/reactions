<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\migrations\dev;

use \phpbb\db\migration\container_aware_migration;

class mu1 extends container_aware_migration
{
	static public function depends_on()
	{
		return ['\steve\reactions\migrations\dev\m1_reactions'];
	}

	public function update_data()
	{
		return [
			['config.add', ['reactions_topic_locked', true]],
		];
	}
}