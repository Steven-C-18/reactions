<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\reaction;

class reaction_types
{
	protected $auth;
	protected $cache;
	protected $config;
	protected $db;
	protected $helper;
    protected $language;
	protected $template;
	protected $path_helper;
	protected $root_path;
	protected $php_ext;
	protected $reactions_table;
	protected $reaction_types_table;
	protected $cache_time;
	
	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\cache\driver\driver_interface $cache,
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\controller\helper $helper,
		\phpbb\language\language $language,
		\phpbb\template\template $template,
		\phpbb\path_helper $path_helper,
		$php_ext,
		$root_path,
		$reactions_table,
		$reaction_types_table)
	{
		$this->auth = $auth;
		$this->cache = $cache;
		$this->config = $config;
		$this->db = $db;
		$this->helper = $helper;
		$this->language = $language;
		$this->template = $template;
		$this->path_helper = $path_helper;
		$this->php_ext = $php_ext;
		$this->root_path = $root_path;
		$this->reactions_table = $reactions_table;
		$this->reaction_types_table = $reaction_types_table;
		$this->const = new \steve\reactions\reaction\constants;
		$this->cache_time = $cache_time = isset($this->config['reactions_sql_cache']) ? (int) $this->config['reactions_sql_cache'] : $this->const::TPR_CACHE_TIME;
	}

	public function obtain_reaction_types()
	{
		$reaction_types = [];
		$reaction_types = $this->cache->get('_reaction_types');
		if ($reaction_types === false)
		{
			$sql = "SELECT *
				FROM " . $this->reaction_types_table . "
				WHERE reaction_type_enable = 1
					ORDER BY reaction_type_order_id ASC";
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				if (empty($row))
				{
					continue;
				}
				$reaction_types[] = $row;
			}
			$this->db->sql_freeresult($result);

			$this->cache->put('_reaction_types', $reaction_types, $this->cache_time);
		}

		return $reaction_types;
	}

	private function percentage($reaction_type_ids, $reactions_total)
	{
		return (string) number_format(count($reaction_type_ids) / $reactions_total * 100, 1) . ' ' . $this->language->lang('REACTION_PERCENT');		
	}

	private function total_count($reaction_type_ids)
	{
		return count($reaction_type_ids);		
	}

	public function obtain_top_reaction_types($switch, $u_action, $query_limit, $start)
	{
		$sql_where = $switch === 'admin' ? "" : " WHERE reaction_type_enable = 1";

		$sql = 'SELECT *
			FROM ' . $this->reaction_types_table . "
			$sql_where
				ORDER BY reaction_type_order_id ASC";
		$result = $switch != 'admin' ? $this->db->sql_query($sql) : $this->db->sql_query_limit($sql, (int) $query_limit, $start);
		
		$i = 0;
		$reaction_types = $reaction_type_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			if (empty($row))
			{
				continue;
			}
			$reaction_types[$i] = $row;
			$reaction_type_ids[$i] = (int) $row['reaction_type_id'];
			$i++;
		}
		$this->db->sql_freeresult($result);

		if (count($reaction_type_ids))
		{
			$sql = 'SELECT reaction_type_id
				FROM ' . $this->reactions_table . "
				WHERE " . $this->db->sql_in_set('reaction_type_id', array_unique($reaction_type_ids));
			$result = $this->db->sql_query($sql);

			$count_reaction_types = [];
			while ($rows = $this->db->sql_fetchrow($result))
			{
				if (empty($rows))
				{
					continue;
				}
				$count_reaction_types[(int) $rows['reaction_type_id']][] = $rows;
			}
			$this->db->sql_freeresult($result);
		}

		if (count($reaction_types))
		{
			$reactions_total = $this->reactions_count();
					
			for ($i = 0, $end = count($reaction_types); $i < $end; ++$i)
			{
				if (!isset($reaction_types[$i]))
				{
					continue;
				}

				$row = $reaction_types[$i];
				$reaction_type_ids = $row['reaction_type_id'];
				$type_count = $percentage = $total_count = intval(0);

				if (!empty($count_reaction_types[$reaction_type_ids]) && $reactions_total)
				{
					$percentage = $this->percentage($count_reaction_types[$reaction_type_ids], $reactions_total);
				}
				if (!empty($count_reaction_types[$reaction_type_ids]) && $reactions_total)
				{
					$total_count = $this->total_count($count_reaction_types[$reaction_type_ids]);
				}

				$vars = [];
				switch($switch)
				{
					case 'page':
						$vars = [
							'COUNT' 		=> $total_count,
							'IMAGE_SRC'		=> $this->get_reaction_file($row['reaction_file_name']),
							'PERCENT'		=> $percentage,
							'TITLE'			=> censor_text($row['reaction_type_title']),
						];
					break;
					case 'admin':
						$active_lang = empty($row['reaction_type_enable']) ? $this->language->lang('DISABLED') : $this->language->lang('ENABLED');
						$active_value = empty($row['reaction_type_enable']) ? 'activate' : 'deactivate';

						$vars = array_merge($vars, [
							'L_ACTIVATE_DEACTIVATE'	=> $active_lang,
							'U_ACTIVATE_DEACTIVATE'	=> $u_action . '&amp;action=' . $active_value . '&amp;reaction_type_id=' . $row['reaction_type_id'] . '&amp;hash=' . generate_link_hash('acp_reactions'),
							'U_DELETE'				=> $u_action . '&amp;action=delete_data&amp;reaction_type_id=' . $row['reaction_type_id'] . '&amp;hash=' . generate_link_hash('acp_reactions'),
							'U_EDIT'				=> $u_action . '&amp;action=edit&amp;reaction_type_id=' . $row['reaction_type_id'],
							'U_MOVE_UP'				=> $u_action . '&amp;action=move_up&amp;reaction_type_id=' . $row['reaction_type_id'] . '&amp;hash=' . generate_link_hash('acp_reactions'),
							'U_MOVE_DOWN'			=> $u_action . '&amp;action=move_down&amp;reaction_type_id=' . $row['reaction_type_id'] . '&amp;hash=' . generate_link_hash('acp_reactions'),
						]);
					break;
					default:
						return false;
					break;
				}

				$this->template->assign_block_vars('reaction_types', $vars);
			}
			unset($reaction_types, $count_reaction_types[$reaction_type_ids]);
		}

		return $this;
	}

	public function disabled_ids($db_data, $disabled_data_ids)
	{
		$type_ids = array_column($this->obtain_reaction_types(), 'reaction_type_id');

		$disabled_data_ids = trim('|', $disabled_data_ids);
		$disabled_data_ids = explode('|', $disabled_data_ids);

		$response_data = json_decode($db_data);	
		if (!empty($response_data))
		{
			foreach ($response_data as $key => $value)
			{
				if (empty($value))
				{
					continue;
				}
				if (!in_array($value->id, $type_ids) || in_array($value->id, $disabled_data_ids))
				{
					unset($response_data[$key]);
				}
			}
		}

		return $this->remove_duplicate_encode($response_data);
	}

	public function disabled_reactions($forum_reaction_type_ids, $post_disabled_reaction_ids, $user_disabled_reaction_ids)
	{
		if (empty($forum_reaction_type_ids) && empty($post_disabled_reaction_ids) && empty($user_disabled_reaction_ids))
		{
			return false;
		}

		$disabled_reaction_ids = '';
		if ($forum_reaction_type_ids)
		{
			$disabled_reaction_ids .= $forum_reaction_type_ids;
		}
		if ($post_disabled_reaction_ids)
		{
			$disabled_reaction_ids .= '|' . $post_disabled_reaction_ids;
		}
		if ($user_disabled_reaction_ids)
		{
			$disabled_reaction_ids .= '|' . $user_disabled_reaction_ids;
		}
		$disabled_reaction_ids = array_map('intval', explode('|', $disabled_reaction_ids));
		if (empty($disabled_reaction_ids))
		{
			return false;
		}

		sort($disabled_reaction_ids);
		return array_unique($disabled_reaction_ids);
	}

	public function reactions_count()
	{
		$sql = 'SELECT COUNT(reaction_id) AS item_count
			FROM ' . $this->reactions_table;
		$result = $this->db->sql_query($sql);
		$reactions_total = (int) $this->db->sql_fetchfield('item_count');
		$this->db->sql_freeresult($result);
		
		return $reactions_total;
	}

	public function reaction_type_count()
	{
		$sql = 'SELECT COUNT(reaction_type_id) AS item_count
			FROM ' . $this->reaction_types_table;
		$result = $this->db->sql_query($sql);
		$types_total = (int) $this->db->sql_fetchfield('item_count');
		$this->db->sql_freeresult($result);
		
		return $types_total;
	}
	
	public function obtain_reaction_type($type_id)
	{
		if (empty($type_id))
		{
			return false;
		}

		$sql = 'SELECT *
			FROM ' . $this->reaction_types_table . '
			WHERE reaction_type_id = ' . (int) $type_id;
		$result = $this->db->sql_query($sql);
		$reaction_type = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $reaction_type;
	}

	public function obtain_reaction_type_ids()
	{
		$reaction_type_ids = [];
		$reaction_type_ids = $this->cache->get('_reaction_type_ids');
		if ($reaction_type_ids === false)
		{
			$sql = 'SELECT reaction_type_id
				FROM ' . $this->reaction_types_table . '
				WHERE reaction_type_enable = 1';
			$result = $this->db->sql_query($sql);
			$reaction_type_ids = $this->db->sql_fetchrowset($result);
			$this->db->sql_freeresult($result);

			$this->cache->put('_reaction_type_ids', $reaction_type_ids, $this->cache_time);
		}

		if (empty($reaction_type_ids))
		{
			return false;
		}

		return (string) implode(',', array_column($reaction_type_ids, 'reaction_type_id'));
	}

	public function reation_type_count($type_id)
	{
		if (empty($type_id))
		{
			return false;
		}

		$sql = 'SELECT COUNT(reaction_type_id) AS count
			FROM ' . $this->reactions_table . '
			WHERE reaction_type_id = ' . (int) $type_id;
		$result = $this->db->sql_query($sql);
		$type_count = (int) $this->db->sql_fetchfield('count');
		$this->db->sql_freeresult($result);

		return $type_count;
	}

	public function display_reaction_types($reaction_types, $post_id, $user_type_id, $disabled_reactions)
	{
		if (empty($reaction_types))
		{
			return false;
		}

		$type_row = [];

		if (!is_array($disabled_reactions))
		{
			$disabled_reactions = [$disabled_reactions];
		}

		foreach ($reaction_types as $reaction_type)
		{
			if (empty($reaction_type))
			{
				continue;
			}

			$disabled_type = in_array($reaction_type['reaction_type_id'], $disabled_reactions) ? true : false;

			$type_row = [
				'CHECKED'			=> $disabled_type,
				'ID'				=> $reaction_type['reaction_type_id'],
				'IMAGE_SRC'			=> isset($reaction_type['reaction_file_name']) ? $this->get_reaction_file($reaction_type['reaction_file_name']) : '',
				'TITLE'				=> isset($reaction_type['reaction_type_title']) ? $reaction_type['reaction_type_title'] : '',
				'U_REACTED'			=> $user_type_id == $reaction_type['reaction_type_id'] ? true : false,
			];

			if (!empty($post_id))
			{
				$reaction_add_url = $this->helper->route('steve_reactions_add_reaction_controller', ['post_id' => $post_id, 'type_id' => $reaction_type['reaction_type_id'], 'hash' => generate_link_hash('add_reaction')]);
				$type_row += [
					'U_REACTED'			=> $user_type_id == $reaction_type['reaction_type_id'] ? true : false,
					'U_REACTION_ADD'	=> $this->auth->acl_get('u_add_reactions') ? $reaction_add_url : false,
				];
			}

		 	$this->template->assign_block_vars(empty($post_id) ? 'reaction_types' : 'postrow.reaction_types', $type_row);
		}
		unset($reaction_types);

		return $this;
	}

	public function round_counts($count)
	{
		if (empty($count))
		{
			return intval(0);
		}

		switch ($count) {
			case $count < $this->const::TPR_K:
				return $count;
			break;
			case $count < $this->const::TPR_M:
				$count = round($count / $this->const::TPR_K, 2);
				return $count . $this->language->lang('REACTIONS_K');
			break;
			case $count >= $this->const::TPR_M:
				$count = round($count / $this->const::TPR_M, 2);
				return $count . $this->language->lang('REACTIONS_M');
			break;
  			default:
   				return false;
   			break;
		}
	}

	public function remove_duplicate_encode($json_data)
	{
		$json_data = array_map("unserialize", array_unique(array_map("serialize", $json_data)));
		$post_data = array_values($json_data);
		unset($json_data);
		$post_data = json_encode($post_data);

		return $post_data;
	}
//$finder = $extension_manager->get_finder();

/* $images = $finder
    ->extension_suffix('.png')
    ->extension_directory('/images')
    ->find_from_extension('demo', $phpbb_root_path . 'ext/acme/demo/'); */
	public function select_reaction_image($image)
	{
		$imglist = filelist($this->reactions_image_path(), '', $this->image_type_ext());

		if (empty($imglist))
		{
			return false;
		}

		$filename_list = '<option value="">' . $this->language->lang('ACP_SELECT_REACTION_IMAGE') . '</option>' . PHP_EOL;

		foreach ($imglist as $path => $img_ary)
		{
			sort($img_ary);
			foreach ($img_ary as $img)
			{
				$img = $path . $img;
				$selected = ($img == $image) ? ' selected="selected"' : '';
				
				if (preg_match('/^[^!"#$%&*\'()+,.\/\\\\:;<=>?@\\[\\]^`{|}~ ]*$/', $img) || strlen($img) > $this->const::TPR_IMAGE_LENGH)
				{
					continue;
				}
				$filename_list .= '<option value="' . htmlspecialchars($img) . '"' . 'data-img="' . $img . '"' . $selected . '>' . $img . '</option>' . PHP_EOL;
			}
		}
		unset($imglist, $img_ary);

		return $filename_list;
	}

	public function image_type_ext()
	{
		return (string) 'gif|jpg|jpeg|png|svg';
	}

	public function image_type($reaction_file_name)
	{
		return preg_match('/.(' . $this->image_type_ext() . ')/', strtolower($reaction_file_name));
	}

	public function get_reaction_file($reaction_file_name)
	{
		if (!$this->image_type($reaction_file_name) || empty($reaction_file_name))
		{
			return false;
		}

		return $this->reactions_image_path() . $reaction_file_name;
	}

	public function list_url($post_id, $value_id)
	{
		return $this->helper->route('steve_reactions_view_reactions_controller_pages', ['post_id' => $post_id, 'reaction_id' => $value_id]);
	}

	public function reactions_image_path()
	{
		return $this->path_helper->get_web_root_path() . $this->config['reactions_image_path'] . '/';
	}

	public function delete_reaction_types_cache()
	{
		$this->cache->destroy('_reaction_type_ids');
		$this->cache->destroy('_reaction_types');
	}
	
	public function viewtopic_url()
	{
		return (string) append_sid($this->root_path . "viewtopic." . $this->php_ext . "?p=");
	}

	public function tpr_common_vars($new_vars = [])
	{
		$template_vars = [
			'REACTIONS_ENABLED'				=> !empty($this->config['reactions_enabled']) ? true : false,

			'REACTIONS_ENABLE_PAGES'		=> !empty($this->config['reactions_page_enabled']) ? true : false,
			'REACTIONS_ENABLE_POST_PAGES'	=> !empty($this->config['reactions_posts_page_enabled']) ? true : false,

			'REACTIONS_ENABLE_PERCENTAGE'	=> !empty($this->config['reactions_enable_percentage']) ? true : false,
			'REACTIONS_ENABLE_BADGE'		=> !empty($this->config['reactions_enable_badge']) ? true : false,
			'REACTIONS_ENABLE_COUNT'		=> !empty($this->config['reactions_enable_count']) ? true : false,
			'REACTION_TYPE_COUNT_ENABLE'	=> !empty($this->config['reaction_type_count_enable']) ? true : false,
			'REACTION_AUTHOR_REACT'			=> !empty($this->config['reactions_author_react']) ? true : false,
			'REACTIONS_RESYNC_ENABLE'		=> !empty($this->config['reactions_resync_enable']) ? true : false,
			'REACTIONS_TOPIC_LOCKED'		=> !empty($this->config['reactions_topic_locked']) ? true : false,

			'REACTIONS_NOTIFICATIONS_ENABLED'			=> !empty($this->config['reactions_notifications_enabled']) ? true : false,
			'REACTIONS_NOTIFICATIONS_EMAILS_ENABLED'	=> !empty($this->config['reactions_notifications_emails_enabled']) ? true : false,

			'REACTIONS_RESYNC_BATCH'		=> isset($this->config['reactions_resync_batch']) ? $this->config['reactions_resync_batch'] : $this->const::TPR_BATCH,
			'REACTIONS_RESYNC_TIME'			=> isset($this->config['reactions_resync_time']) ? $this->config['reactions_resync_time'] : $this->const::TPR_SYNC_TIME,
			'REACTIONS_PER_PAGE'			=> isset($this->config['reactions_per_page']) ? $this->config['reactions_per_page'] : $this->const::TPR_PER_PAGE,
			'REACTIONS_FLOOD_TIME'			=> isset($this->config['reactions_flood_time']) ? $this->config['reactions_flood_time'] : (int) 3,

			'REACTION_PATHS'				=> $this->reactions_image_path(),
			'REACTION_PATH'					=> isset($this->config['reactions_image_path']) ? $this->config['reactions_image_path'] : '',
			'REACTION_IMAGE_TYPES_EXT'		=> $this->image_type_ext(),
			'REACTION_IMAGE_HEIGHT'			=> isset($this->config['reaction_image_height']) ? $this->config['reaction_image_height'] : $this->const::TPR_DIMENSION,
			'REACTION_IMAGE_WIDTH'			=> isset($this->config['reaction_image_width']) ? $this->config['reaction_image_width'] : $this->const::TPR_DIMENSION,
			'REACTIONS_IMAGE_CACHE'			=> isset($this->config['reactions_sql_cache']) ? $this->config['reactions_sql_cache'] : $this->const::TPR_CACHE_TIME,

			'REACTIONS_DROPDOWN_WIDTH'		=> isset($this->config['reactions_dropdown_width']) ? $this->config['reactions_dropdown_width'] : $this->const::TPR_DROPDOWN,
			'REACTIONS_BUTTON_ICON'			=> isset($this->config['reactions_button_icon']) ? $this->config['reactions_button_icon'] : '',
			'REACTIONS_BUTTON_COLOR'		=> isset($this->config['reactions_button_color']) ? $this->config['reactions_button_color'] : '',
			'REACTIONS_BUTTON_TOP'			=> !empty($this->config['reactions_button_top']) ? true : false,

			'REACTIONS_SESSION'				=> intval($this->config['session_length']),
			'REACTION_AVATAR'				=> $this->root_path . "download/file." . $this->php_ext . "?avatar=",
			'REACTION_URL'					=> $this->reactions_image_path(),
			'REACTION_USER_URL'				=> $this->root_path . "memberlist." . $this->php_ext . "?mode=viewprofile&u=",
			'REACTION_A_M_DELETE'			=> $this->root_path . 'app.' . $this->php_ext . '/delete_reaction/',
			'REACTION_HASHED'				=> '?hash=' . generate_link_hash('delete_reaction'),
		];

		if (count($new_vars))
		{
			$template_vars = array_merge($template_vars, $new_vars);
		}

		return $this->template->assign_vars($template_vars);
	}
}
