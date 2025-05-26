<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\migrations\dev;

use \phpbb\db\migration\container_aware_migration;

class m4_reactions_data extends container_aware_migration
{
	public function effectively_installed()
	{
		$sql = 'SELECT * FROM ' . $this->table_prefix . 'steve_reaction_types';
		$result = $this->db->sql_query_limit($sql, (int) 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row !== false;
	}

	static public function depends_on()
	{
		return ['\steve\reactions\migrations\dev\m1_reactions'];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'insert_tp_reactions']]],
		];
	}

	public function insert_tp_reactions()
	{
		$lang = $this->container->get('language');
		$lang->add_lang('common', 'steve/reactions');

		$reaction_type = [
			[
				'reaction_type_order_id'		=> 1,
				'reaction_file_name'			=> '1f44d.svg',
				'reaction_type_title'			=> $lang->lang('REACTION_LIKE'),
				'reaction_type_enable'			=> true,
			],
			[
				'reaction_type_order_id'		=> 2,
				'reaction_file_name'			=> '1f44e.svg',
				'reaction_type_title'			=> $lang->lang('REACTION_DISLIKE'),
				'reaction_type_enable'			=> true,
			],
			[
				'reaction_type_order_id'		=> 3,
				'reaction_file_name'			=> '1f642.svg',
				'reaction_type_title'			=> $lang->lang('REACTION_HAPPY'),
				'reaction_type_enable'			=> true,
			],
			[
				'reaction_type_order_id'		=> 4,
				'reaction_file_name'			=> '1f60d.svg',
				'reaction_type_title'			=> $lang->lang('REACTION_LOVE'),
				'reaction_type_enable'			=> true,
			],
			[
				'reaction_type_order_id'		=> 5,
				'reaction_file_name'			=> '1f602.svg',
				'reaction_type_title'			=> $lang->lang('REACTION_FUNNY'),
				'reaction_type_enable'			=> true,
			],
			[
				'reaction_type_order_id'		=> 6,
				'reaction_file_name'			=> '1f611.svg',
				'reaction_type_title'			=> $lang->lang('REACTION_NEUTRAL'),
				'reaction_type_enable'			=> true,
			],
			[
				'reaction_type_order_id'		=> 7,
				'reaction_file_name'			=> '1f641.svg',
				'reaction_type_title'			=> $lang->lang('REACTION_UNHAPPY'),
				'reaction_type_enable'			=> true,
			],
			[
				'reaction_type_order_id'		=> 8,
				'reaction_file_name'			=> '1f62f.svg',
				'reaction_type_title'			=> $lang->lang('REACTION_SURPRISED'),
				'reaction_type_enable'			=> true,
			],
			[
				'reaction_type_order_id'		=> 9,
				'reaction_file_name'			=> '1f62d.svg',
				'reaction_type_title'			=> $lang->lang('REACTION_CRY'),
				'reaction_type_enable'			=> true,
			],
			[
				'reaction_type_order_id'		=> 10,
				'reaction_file_name'			=> '1f621.svg',
				'reaction_type_title'			=> $lang->lang('REACTION_MAD'),
				'reaction_type_enable'			=> true,
			],
		];

		$this->db->sql_multi_insert($this->table_prefix . 'steve_reaction_types', $reaction_type);
	}
}
