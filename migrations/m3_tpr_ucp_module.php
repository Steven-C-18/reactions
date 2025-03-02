<?php
/**
	* Topic/Post Reactions. Extends for the phpBB Forum Software package.
	* @author Steve <https://steven-clark.tech/>
*/

namespace steve\reactions\migrations;

class m3_tpr_ucp_module extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		$sql = 'SELECT module_id
			FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'ucp'
				AND module_langname = 'UCP_REACTIONS_TITLE'";
		$result = $this->db->sql_query($sql);
		$module_id = $this->db->sql_fetchfield('module_id');
		$this->db->sql_freeresult($result);

		return $module_id !== false;
	}

	static public function depends_on()
	{
		return ['\phpbb\db\migration\data\v320\v320'];
	}

	public function update_data()
	{
		return [
			['module.add', [
				'ucp',
				0,
				'UCP_REACTIONS_TITLE'
			]],
			['module.add', [
				'ucp',
				'UCP_REACTIONS_TITLE',
				[
					'module_basename'	=> '\steve\reactions\ucp\main_module',
					'modes'				=> ['settings'],
				],
			]],
		];
	}
}
