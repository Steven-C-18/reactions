<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\reaction;

class resync_reactions
{
	protected $auth;
	protected $config;
	protected $db;
	protected $language;
	protected $request;
	protected $template;
	protected $user;
	protected $php_ext;
	protected $root_path;

	protected $type_operator;
	protected $reactions_table;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,		
		\phpbb\db\driver\driver_interface $db,
		\phpbb\language\language $language,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		$php_ext,
		$root_path,

		\steve\reactions\reaction\reaction_types $type_operator,
		$reactions_table)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->php_ext = $php_ext;
		$this->root_path = $root_path;

		$this->type_operator = $type_operator;
		$this->reactions_table = $reactions_table;
	}

	public function resync($post_id, $user_id)
	{
		if (empty($this->config['reactions_enabled']))
		{
			throw new \phpbb\exception\http_exception(404, 'REACTIONS_DISABLED');
		}

 		if (!isset($post_id) || !isset($user_id))
		{
			throw new \phpbb\exception\http_exception(404, 'REACTION_ERROR');
		}

		$user_enable_reactions = (!$this->auth->acl_get('u_disable_reactions', $this->user->data['user_id'])) ? true : ($this->user->data['user_enable_reactions'] ? true  : false);
		if (!check_link_hash($this->request->variable('hash', ''), 'resync_reaction') || !$user_enable_reactions || !$this->auth->acl_get('u_resync_reactions')
			&& ($user_id != $this->user->data['user_id'] || !$this->auth->acl_get('a_', 'm_')))
		{
			throw new \phpbb\exception\http_exception(403, 'NO_AUTH_OPERATION');
		}

		if (empty($this->config['reactions_resync_enable']))
		{
			throw new \phpbb\exception\http_exception(404, 'RESYNC_DISABLED');
		}
		
		$sql = 'SELECT COUNT(post_id) AS count
			FROM ' . $this->reactions_table . '
			WHERE post_id = ' . (int) $post_id;
		$result = $this->db->sql_query($sql);
		$post_count = (int) $this->db->sql_fetchfield('count');
		$this->db->sql_freeresult($result);

		//needs a bit more work, 16 5 22
		$sql = 'SELECT reaction_type_id, reaction_file_name, post_id
			FROM ' . $this->reactions_table . '
			WHERE post_id = ' . (int) $post_id;
		$result = $this->db->sql_query($sql);

		$ids = $reactions = $json_data = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$ids[] = (int) $row['reaction_type_id'];
			$reactions[(int) $row['reaction_type_id']] = $row['reaction_type_id'] . ',' . $row['reaction_file_name'];
		}
		$this->db->sql_freeresult($result);	

		$count = array_count_values($ids);

		$reaction_data = array_unique($reactions);
		foreach ($reaction_data as $data)
		{	
			list($id, $src) = explode(',', $data);

			$json_data[] = [
				'id'		=> intval($id),
				'src'		=> strval($src),
				'count'		=> intval($count[$id]),
			];
		}
		unset($reaction_data);

		$post_data = $this->type_operator->remove_duplicate_encode($json_data);

		$sql = 'UPDATE ' . POSTS_TABLE . "
			SET post_reaction_data = '" . $this->db->sql_escape($post_data) . "', post_reactions = $post_count
			WHERE post_id = " . (int) $post_id;
		$this->db->sql_query($sql);
		
		//use ajax
		redirect(append_sid("{$this->root_path}viewtopic.{$this->php_ext}?p=$post_id#post-reactions-$post_id"));
		
		return $this;
	}

	public function resync_refresh($reaction_id, $start_count, $u_action)
	{
		$batch_size = $this->config['reactions_resync_batch'];

		$count = $this->type_operator->reation_type_count($reaction_id);
		$type_data = $this->type_operator->obtain_reaction_type($reaction_id);

		$sql = 'SELECT *
			FROM ' . $this->reactions_table . '
			WHERE reaction_type_id = ' . (int) $reaction_id . "
				AND reaction_file_name <> '" . $this->db->sql_escape($type_data['reaction_file_name']) . "'";
		$result = $this->db->sql_query_limit($sql, $batch_size);

		$reaction_ids = $post_ids = $json_data = [];
		if ($row = $this->db->sql_fetchrow($result))
		{
			do
			{
				$reaction_ids[] = (int) $row['reaction_id'];
				$post_ids[] = (int) $row['post_id'];
			}
			while ($row = $this->db->sql_fetchrow($result));

			$this->db->sql_transaction('begin');

			if(count($reaction_ids))
			{
				$sql = 'SELECT post_id, post_reaction_data
					FROM ' . POSTS_TABLE . '
					WHERE ' . $this->db->sql_in_set('post_id', array_unique($post_ids));
				$result = $this->db->sql_query($sql);

				$post_data = [];
				while ($row = $this->db->sql_fetchrow($result))
				{
					$post_data[(int) $row['post_id']] = $row;
				}
				$this->db->sql_freeresult($result);

				if (!empty($post_data))
				{
					foreach ($post_data as $data)
					{
						$json_data = json_decode($data['post_reaction_data']);

						if (!empty($json_data))
						{
							foreach ($json_data as $key => $value)
							{
								if (empty($value))
								{
									continue;
								}
								if ($value->id == $reaction_id)
								{
									$value->src = strval($type_data['reaction_file_name']);
								}
							}
						}

						$new_post_data = $this->type_operator->remove_duplicate_encode($json_data);

						$sql = 'UPDATE ' . POSTS_TABLE . "
							SET post_reaction_data = '" . $this->db->sql_escape($new_post_data) . "'
							WHERE post_id = " . (int) $data['post_id'];
						$this->db->sql_query($sql);
					}
					unset($post_data, $json_data);
				}
			}

			$sql = 'UPDATE ' . $this->reactions_table . "
				SET reaction_file_name = '" . $this->db->sql_escape($type_data['reaction_file_name']) . "'
				WHERE " . $this->db->sql_in_set('reaction_id', $reaction_ids);
			$this->db->sql_query($sql);
			$affected = $this->db->sql_affectedrows();

			$this->db->sql_transaction('commit');

			//convert to ajax
			if (!empty($affected))
			{
				$refresh_time = isset($this->config['reactions_resync_time']) ? $this->config['reactions_resync_time'] : (int) 1;
				meta_refresh($refresh_time, $u_action . "&amp;action=resync_refresh&amp;reaction_id=$reaction_id&amp;start_count=" . ($start_count + $affected));
			}

			$this->template->assign_vars([
				'NEW_IMG'	=> $this->type_operator->get_reaction_file($type_data['reaction_file_name']),
				'OLD_IMG'	=> $this->type_operator->get_reaction_file($this->config['reactions_old_file']),
				'START'     => !$start_count ? $this->language->lang('ACP_RESYNCING_REACTION') : $this->language->lang('ACP_REACTIONS_RESYNCING', $start_count, $count),
				'SYNC'		=> true,
				'S_REACTIONS_STYLESHEET'	=> true,
			]);

			return $this;
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
			'DONE'		=> $this->language->lang('ACP_REACTIONS_RESYNC_DONE', $count),
			'NEW_IMG'	=> $this->type_operator->get_reaction_file($type_data['reaction_file_name']),
			'OLD_IMG'	=> $this->type_operator->get_reaction_file($this->config['reactions_old_file']),
			'SYNC'		=> true,
			'S_REACTIONS_STYLESHEET'	=> true,
			'U_BACK'	=> $u_action,
		]);

		$this->type_operator->delete_reaction_types_cache();

		return  $this;
	}
}
