<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
	
	* batched refresh, can be done with AJAX
*/

namespace steve\reactions\reaction;

class delete_reactions
{
	protected $config;
	protected $db;
	protected $language;
	protected $notification_manager;
	protected $template;

	protected $type_operator;
	protected $reactions_table;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\language\language $language,
		\phpbb\notification\manager $notification_manager,
		\phpbb\template\template $template,
		\steve\reactions\reaction\reaction_types $type_operator,
		$reactions_table,
		$reaction_types_table)
	{
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
		$this->notification_manager = $notification_manager;
		$this->template = $template;
		$this->type_operator = $type_operator;	
		$this->reactions_table = $reactions_table;
		$this->reaction_types_table = $reaction_types_table;
	}

	public function delete_post_reactions($in_set, $ids)
	{
		$this->check_array_ids($in_set, $ids);

		$sql = 'SELECT *
			FROM ' . $this->reactions_table . "
			WHERE " . $this->db->sql_in_set($in_set, $ids);
		$result = $this->db->sql_query($sql);

		$reactions = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			if (empty($row))
			{
				continue;
			}
			$reactions[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $reactions;
	}

	public function update_reaction_counts($reactions)
	{
		if (!empty($reactions))
		{
			foreach ($reactions as $key => $reaction)
			{
				$sql = 'UPDATE ' . POSTS_TABLE . '
					SET post_reactions = post_reactions - 1
					WHERE post_id = ' . (int) $reaction['post_id'];
				$this->db->sql_query($sql);

				$sql = 'UPDATE ' . USERS_TABLE . '
					SET user_reactions = user_reactions - 1
					WHERE user_id = ' . (int) $reaction['poster_id'];
				$this->db->sql_query($sql);

				$this->notification_manager->delete_notifications('steve.reactions.notification.type.post_reaction', $reaction['reaction_id']);
			}
			unset($reactions);
		}

		return (bool) $this->db->sql_affectedrows();
	}

	public function delete_reactions($in_set = '', $ids)
	{
		$this->check_array_ids($in_set, $ids);
		
		$sql = 'DELETE FROM ' . $this->reactions_table . "
			WHERE " . $this->db->sql_in_set($in_set, $ids);
		$this->db->sql_query($sql);

		return (bool) $this->db->sql_affectedrows();
	}

	public function delete_reaction_type($reaction_id)
	{
		$sql = 'DELETE FROM ' . $this->reaction_types_table . '
			WHERE reaction_type_id = ' . (int) $reaction_id;
		$this->db->sql_query($sql);

		return (bool) $this->db->sql_affectedrows();
	}

	public function acp_delete_reaction_type($reaction_id, $u_action, $start_count)
	{
		$batch_size = $this->config['reactions_resync_batch'];
		$counted = $this->type_operator->reation_type_count($reaction_id);
		$type_data = $this->type_operator->obtain_reaction_type($reaction_id);

		$sql = 'SELECT reaction_id, post_id, poster_id, reaction_type_id
			FROM ' . $this->reactions_table . "
			WHERE reaction_type_id = " . (int) $reaction_id;
		$result = $this->db->sql_query_limit($sql, $batch_size);

		$post_ids = $poster_ids = $posts = $reaction_ids = [];
		if ($row = $this->db->sql_fetchrow($result))
		{
			do
			{
				$posts[] = (int) $row['post_id'];
				$poster_ids[(int) $row['poster_id']] = !empty($poster_ids[$row['poster_id']]) ? $poster_ids[$row['poster_id']] + 1 : 1;
				$post_ids[(int) $row['post_id']] = !empty($post_ids[$row['post_id']]) ? $post_ids[$row['post_id']] + 1 : 1;
				$reaction_ids[] = (int) $row['reaction_id'];
			}
			while ($row = $this->db->sql_fetchrow($result));

			$this->db->sql_transaction('begin');

			if (!empty($poster_ids))
			{
				foreach ($poster_ids as $poster_id => $count)
				{
					$sql = 'UPDATE ' . USERS_TABLE . '
						SET user_reactions = 0
						WHERE user_id = ' . (int) $poster_id . '
							AND user_reactions < ' . (int) $count;
					$this->db->sql_query($sql);

					$sql = 'UPDATE ' . USERS_TABLE . '
						SET user_reactions = user_reactions - ' . (int) $count . '
						WHERE user_id = ' . (int) $poster_id . '
							AND user_reactions >= ' . (int) $count;
					$this->db->sql_query($sql);
				}
				unset($poster_ids);
			}

			if (!empty($post_ids))
			{
				foreach ($post_ids as $post_id => $count)
				{
					$sql = 'UPDATE ' . POSTS_TABLE . '
						SET post_reactions = 0
						WHERE post_id = ' . (int) $post_id . '
							AND post_reactions < ' . (int) $count;
					$this->db->sql_query($sql);

					$sql = 'UPDATE ' . POSTS_TABLE . '
						SET post_reactions = post_reactions - ' . (int) $count . '
						WHERE post_id = ' . (int) $post_id . '
							AND post_reactions >= ' . (int) $count;
					$this->db->sql_query($sql);
				}
				unset($post_ids);
			}

			if (!empty($posts))
			{
				$sql1 = 'SELECT post_id, post_reaction_data
					FROM ' . POSTS_TABLE . "
					WHERE " . $this->db->sql_in_set('post_id', $posts);
				$results = $this->db->sql_query($sql1);

				$post_data = [];
				while ($row = $this->db->sql_fetchrow($results))
				{
					if (empty($row))
					{
						continue;
					}
					$post_data[] = $row;
				}
				$this->db->sql_freeresult($results);

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
									unset($json_data[$key]);
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

			if (!empty($reaction_ids))
			{
				$this->notification_manager->delete_notifications('steve.reactions.notification.type.post_reaction', $reaction_ids);
			}

			$sql = 'DELETE FROM ' . $this->reactions_table . '
				WHERE reaction_type_id = ' . (int) $reaction_id . '
					AND ' . $this->db->sql_in_set('post_id', $posts);
			$this->db->sql_query($sql);
			$affected = $this->db->sql_affectedrows();

			$this->db->sql_transaction('commit');

			unset($posts);

			if (!empty($affected))
			{
				$refresh_time = isset($this->config['reactions_resync_time']) ? $this->config['reactions_resync_time'] : (int) 1;
				meta_refresh($refresh_time, $u_action . "&amp;action=delete_reaction_type&amp;reaction_id=$reaction_id&amp;start_count=" . ($start_count + $affected));
			}

			$this->template->assign_vars([
				'OLD_IMG'	=> $this->type_operator->get_reaction_file($type_data['reaction_file_name']),
				'START'     => $this->language->lang('ACP_REACTIONS_DELETING', $start_count),
				'SYNC'		=> true,
				'S_REACTIONS_STYLESHEET'	=> true,
			]);

			return $this;
		}
		$this->db->sql_freeresult($result);

		$this->delete_reaction_type($reaction_id);

		$this->template->assign_vars([
			'DONE'		=> $this->language->lang('ACP_REACTIONS_DELETING_DONE', $start_count),
			'OLD_IMG'	=> $this->type_operator->get_reaction_file($type_data['reaction_file_name']),
			'SYNC'		=> true,
			'U_BACK'	=> $u_action,
			'S_REACTIONS_STYLESHEET'	=> true,			
		]);

		return $this;
	}

	private function check_array_ids($in_set, $ids)
	{
		if (empty($ids) || empty($in_set))
		{
			return false;
		}
		
		if (!is_array($ids))
		{
			$ids = [$ids];
		}

		return array_unique($ids);
	}
}
