<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\migrations\dev;

class m1_reactions extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['reactions_enabled']);
	}

	static public function depends_on()
	{
		return ['\phpbb\db\migration\data\v320\v320'];
	}

	public function update_data()
	{
		return [
			['config.add', ['reactions_enabled', true]],

			['config.add', ['reactions_page_enabled', true]],
			['config.add', ['reactions_posts_page_enabled', true]],
			['config.add', ['reactions_enable_percentage', false]],
			['config.add', ['reactions_enable_badge', false]],
			['config.add', ['reactions_enable_count', false]],

			['config.add', ['reactions_author_react', false]],

			['config.add', ['reactions_notifications_enabled', true]],
			['config.add', ['reactions_notifications_emails_enabled', true]],

			['config.add', ['reactions_resync_enable', false]],
			['config.add', ['reactions_resync_batch', 25]],
			['config.add', ['reactions_resync_time', 1]],
			['config.add', ['reactions_per_page', 25]],
			['config.add', ['reactions_sql_cache', 86400]],
			['config.add', ['reactions_flood_time', 5]],

			['config.add', ['reactions_image_path', 'ext/steve/reactions/images/emoji']],

			['config.add', ['reaction_image_height', 25]],
			['config.add', ['reaction_image_width', 25]],	
			['config.add', ['reaction_type_count_enable', true]],
			['config.add', ['reaction_old_file', '']],

			['config.add', ['reactions_dropdown_width', 215]],
			['config.add', ['reactions_button_icon', 'fa-smile-o']],
			['config.add', ['reactions_button_color', '6b5f5f']],
			['config.add', ['reactions_button_top', true]],

			['permission.add', ['u_add_reactions']],
			['permission.add', ['u_change_reactions']],
			['permission.add', ['u_delete_reactions']],

			['permission.add', ['u_disable_post_reactions']],
			['permission.add', ['u_disable_topic_reactions']],
			['permission.add', ['u_disable_reactions']],
			['permission.add', ['u_disable_reaction_types']],
			['permission.add', ['u_manage_reactions_settings']],
			
			['permission.add', ['u_resync_reactions']],	

			['permission.add', ['u_view_reactions']],
			['permission.add', ['u_view_reactions_pages']],
			['permission.add', ['u_view_post_reactions_page']],
			['permission.add', ['u_view_user_reactions_page']],

			['permission.permission_set', ['ROLE_USER_FULL', 'u_add_reactions']],
			['permission.permission_set', ['ROLE_USER_STANDARD', 'u_add_reactions']],
			['permission.permission_set', ['REGISTERED', 'u_add_reactions', 'group']],
			['permission.permission_set', ['ROLE_USER_FULL', 'u_change_reactions']],
			['permission.permission_set', ['ROLE_USER_STANDARD', 'u_change_reactions']],
			['permission.permission_set', ['REGISTERED', 'u_change_reactions', 'group']],
			['permission.permission_set', ['ROLE_USER_FULL', 'u_delete_reactions']],
			['permission.permission_set', ['ROLE_USER_STANDARD', 'u_delete_reactions']],
			['permission.permission_set', ['REGISTERED', 'u_delete_reactions', 'group']],

			['permission.permission_set', ['ROLE_USER_FULL', 'u_manage_reactions_settings']],
			['permission.permission_set', ['ROLE_USER_STANDARD', 'u_manage_reactions_settings']],
			['permission.permission_set', ['REGISTERED', 'u_manage_reactions_settings', 'group']],

			['permission.permission_set', ['ROLE_USER_FULL', 'u_view_reactions']],
			['permission.permission_set', ['ROLE_USER_STANDARD', 'u_view_reactions']],
			['permission.permission_set', ['REGISTERED', 'u_view_reactions', 'group']],
			['permission.permission_set', ['ROLE_USER_FULL', 'u_view_reactions_pages']],
			['permission.permission_set', ['ROLE_USER_STANDARD', 'u_view_reactions_pages']],
			['permission.permission_set', ['REGISTERED', 'u_view_reactions_pages', 'group']],
			['permission.permission_set', ['ROLE_USER_FULL', 'u_view_post_reactions_page']],
			['permission.permission_set', ['ROLE_USER_STANDARD', 'u_view_post_reactions_page']],
			['permission.permission_set', ['REGISTERED', 'u_view_post_reactions_page', 'group']],
		];
	}

	public function update_schema()
	{
		return [
			'add_tables'		=> [
				$this->table_prefix . 'steve_reactions'	=> [
					'COLUMNS'		=> [
						'reaction_id'			=> ['UINT', null, 'auto_increment'],
						'reaction_user_id'		=> ['UINT', 0],
						'poster_id'				=> ['UINT', 0],
						'post_id'				=> ['UINT', 0],
						'topic_id'				=> ['UINT', 0],
						'pm_id'					=> ['UINT', 0],
						'chat_msg_id'			=> ['UINT', 0],
						'reaction_type_id'		=> ['UINT', 0],
						'reaction_file_name'	=> ['VCHAR:255', ''],
						'reaction_type_title'	=> ['VCHAR:255', ''],
						'reaction_time'			=> ['TIMESTAMP', 0],
					],
					'PRIMARY_KEY'	=> 'reaction_id',
				],
				$this->table_prefix . 'steve_reaction_types'	=> [
					'COLUMNS'		=> [
						'reaction_type_id'			=> ['UINT', null, 'auto_increment'],
						'reaction_type_order_id'	=> ['UINT', 0],
						'reaction_file_name'		=> ['VCHAR:255', ''],
						'reaction_type_title'		=> ['VCHAR:255', ''],
						'reaction_type_height'		=> ['INT:3', 25],
						'reaction_type_width'		=> ['INT:3', 25],
						'reaction_type_enable'		=> ['BOOL', 1],
					],
					'PRIMARY_KEY'	=> 'reaction_type_id',
				],
			],
			'add_columns'	=> [
				$this->table_prefix . 'forums'	=> [
					'forum_enable_reactions'		=> ['BOOL', 1],
					'forum_reaction_type_ids'		=> ['VCHAR:255', ''],//disabled_reaction_ids
				],
				$this->table_prefix . 'posts'	=> [
					'post_reactions'				=> ['INT:9', 0],
					'post_reaction_data'			=> ['TEXT', null],
					'post_enable_reactions'			=> ['BOOL', 1],
					'post_disabled_reaction_ids'	=> ['VCHAR:255', ''],
				],
				$this->table_prefix . 'topics'	=> [
					'topic_enable_reactions'		=> ['BOOL', 1],
				],
				$this->table_prefix . 'users'	=> [
					'user_reactions'				=> ['INT:9', 0],
					'user_disabled_reaction_ids'	=> ['VCHAR:255', ''],
					'user_enable_reactions'			=> ['BOOL', 1],
					'user_enable_post_reactions'	=> ['BOOL', 1],
					'user_enable_topic_reactions'	=> ['BOOL', 1],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns'	=> [
				$this->table_prefix . 'forums'		=> [
					'forum_enable_reactions',
					'forum_reaction_type_ids',
				],
				$this->table_prefix . 'posts'		=> [
					'post_disabled_reaction_ids',
					'post_reactions',
					'post_enable_reactions',
					'post_reaction_data',
				],
				$this->table_prefix . 'topics'		=> [
					'topic_enable_reactions',
				],
				$this->table_prefix . 'users'		=> [
					'user_reactions',
					'user_disabled_reaction_ids',
					'user_enable_reactions',
					'user_enable_post_reactions',
					'user_enable_topic_reactions',
				],
			],
			'drop_tables'		=> [
				$this->table_prefix . 'steve_reactions',
				$this->table_prefix . 'steve_reaction_types',
			],
		];
	}	
}
