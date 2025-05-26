<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\controller\viewtopic;

class add_reaction
{
	protected $auth;
	protected $config;
	protected $db;
	protected $helper;
	protected $notification_manager;
	protected $request;
	protected $user;
	protected $type_operator;
	protected $reactions_table;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\controller\helper $helper,
		\phpbb\notification\manager $notification_manager,
		\phpbb\request\request $request,
		\phpbb\user $user,
		\steve\reactions\reaction\reaction_types $type_operator,
		$reactions_table)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->helper = $helper;
		$this->db = $db;
		$this->notification_manager = $notification_manager;
		$this->request = $request;
		$this->user = $user;
		$this->type_operator = $type_operator;
		$this->reactions_table = $reactions_table;
	}

	public function add($post_id, $type_id)
	{
		if (empty($this->config['reactions_enabled']))
		{
			throw new \phpbb\exception\http_exception(404, 'REACTIONS_DISABLED');
		}

		$sql = 'SELECT p.post_id, p.poster_id, p.post_subject, p.topic_id, p.forum_id, p.post_reaction_data, p.post_reactions, p.post_enable_reactions, p.post_disabled_reaction_ids, t.topic_status
			FROM ' . POSTS_TABLE . ' p LEFT JOIN ' . TOPICS_TABLE . ' t ON p.topic_id = t.topic_id
			WHERE post_id = ' . (int) $post_id;
		$result = $this->db->sql_query($sql);
		$post_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$sql = 'SELECT forum_id, forum_enable_reactions, forum_reaction_type_ids
			FROM ' . FORUMS_TABLE . ' 
			WHERE forum_id = ' . (int) $post_row['forum_id'];
		$result = $this->db->sql_query($sql);
		$forum_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$post_data = $post_row['post_reaction_data'];

 		if (!isset($post_row['post_id']) || !isset($post_id) || !isset($type_id) || (empty($this->config['reactions_topic_locked']) && $post_row['topic_status'] == ITEM_LOCKED))
		{
			throw new \phpbb\exception\http_exception(404, 'REACTION_ERROR');
		}

		$user_enable_reactions = !$this->auth->acl_get('u_disable_reactions') ? true : ($this->user->data['user_enable_reactions'] ? true  : false);
		if (!$user_enable_reactions || !$post_row['post_enable_reactions'] || !$this->auth->acl_get('u_add_reactions') || !check_link_hash($this->request->variable('hash', ''), 'add_reaction'))
		{
			throw new \phpbb\exception\http_exception(403, 'NO_AUTH_OPERATION');
		}

		$sql = 'SELECT *
			FROM ' . $this->reactions_table . '
			WHERE reaction_user_id = ' . (int) $this->user->data['user_id'] . ' 
				AND post_id = ' . (int) $post_id;
		$result = $this->db->sql_query($sql);
		$user_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$reaction = $this->type_operator->obtain_reaction_type($type_id);

 		if (empty($reaction) || empty($reaction['reaction_type_enable']))
		{
			throw new \phpbb\exception\http_exception(404, 'REACTION_ERROR');
		}

 		$sql = 'SELECT COUNT(reaction_type_id) AS reaction_count
			FROM ' . $this->reactions_table . '
			WHERE reaction_type_id = ' . (int) $type_id . '
				AND post_id = ' . (int) $post_id;
		$result = $this->db->sql_query($sql);
		$new_type_counted = (int) $this->db->sql_fetchfield('reaction_count');
		$this->db->sql_freeresult($result);
		
		$delete_url = $this->auth->acl_get('u_delete_reactions') ? $this->helper->route('steve_reactions_delete_reaction_controller', ['post_id' => $post_id, 'user_id' => $this->user->data['user_id'], 'hash' => generate_link_hash('delete_reaction')]) : '';
		$view_url = $this->auth->acl_get('u_view_post_reactions_page') && !empty($this->config['reactions_posts_page_enabled']) ? $this->helper->route('steve_reactions_view_reactions_controller_pages', ['post_id' => $post_id, 'reaction_id' => $type_id]) : '';

		$new_type = $reaction['reaction_file_name'];
		$add_reaction = $update_reaction = false;

 		if (empty($user_row['reaction_user_id']))
		{
			$json_data = json_decode($post_data);
			if (!empty($json_data))
			{
				foreach ($json_data as $key => $value)
				{
					if (empty($value))
					{
						continue;
					}
					if ($value->id == $type_id)
					{
						$new_value = ($new_type_counted + (int) 1);
						$value->count = strval($new_value);
					}
				}
			}

			if (empty($post_data) || empty($new_type_counted))
			{
				$json_data[] = ['id' => $type_id, 'src' => $new_type, 'count' => "1"];
			}

			$post_data = $this->type_operator->remove_duplicate_encode($json_data);
			if (!empty($post_data))
			{
				$disabled_data_ids = $post_row['post_disabled_reaction_ids'] . '|' . $forum_row['forum_reaction_type_ids']; 
				$response_data = $this->type_operator->disabled_ids($post_data, $disabled_data_ids);
			}
			
			$this->db->sql_transaction('begin');

			$sql = 'UPDATE ' . POSTS_TABLE . "
				SET post_reaction_data = '" . $this->db->sql_escape($post_data) . "', post_reactions = post_reactions + 1
				WHERE post_id = " . (int) $post_id;
			$this->db->sql_query($sql); 
			
			$sql_ary = [
				'post_id'					=> (int) $post_id,
				'poster_id'					=> (int) $post_row['poster_id'],
				'reaction_user_id'			=> (int) $this->user->data['user_id'],
				'reaction_type_id'			=> (int) $type_id,
				'reaction_file_name'		=> (string) $reaction['reaction_file_name'],
				'reaction_type_title'		=> (string) $reaction['reaction_type_title'],
				'reaction_time'				=> time(),
				'topic_id'					=> (int) $post_row['topic_id'],
			];
			
			$sql = 'INSERT INTO ' . $this->reactions_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql); 
			$reaction_nextid = (int) $this->db->sql_nextid();

			$sql = 'UPDATE ' . USERS_TABLE . '
				SET user_reactions = user_reactions + 1
				WHERE user_id = ' . (int) $post_row['poster_id'];
			$this->db->sql_query($sql);

			if ($post_row['poster_id'] != $this->user->data['user_id'])
			{
				$notification_data = [
					'reaction_id'			=> $reaction_nextid,			
					'post_id'				=> $post_id,
					'poster_id'				=> $post_row['poster_id'],
					'post_subject'			=> $post_row['post_subject'],
					'reaction_file_name'	=> $reaction['reaction_file_name'],
					'reaction_type_title'	=> $reaction['reaction_type_title'],
					'user_id' 				=> $this->user->data['user_id'],
				];
				$this->notification_manager->add_notifications('steve.reactions.notification.type.post_reaction', $notification_data);
			}

			$this->db->sql_transaction('commit');
			$add_reaction = true;
		}

		if (!empty($user_row['reaction_user_id']))
		{
			if (!check_link_hash($this->request->variable('hash', ''), 'add_reaction') || !$this->auth->acl_get('u_change_reactions'))
			{
				throw new \phpbb\exception\http_exception(403, 'NO_AUTH_OPERATION');
			}
			if ($type_id == $user_row['reaction_type_id'])
			{
				throw new \phpbb\exception\http_exception(403, 'REACTION_TYPE_DUPLICATE');
			}

			$sql = 'SELECT COUNT(reaction_type_id) AS count
				FROM ' . $this->reactions_table . '
				WHERE reaction_type_id = ' . (int) $user_row['reaction_type_id'] . '
					AND post_id = ' . (int) $post_id;
			$result = $this->db->sql_query($sql);
			$old_type_counted = (int) $this->db->sql_fetchfield('count');
			$this->db->sql_freeresult($result);

			$json_data = json_decode($post_data);
			if (!empty($json_data))
			{
				foreach ($json_data as $key => $value)
				{
					if (empty($value))
					{
						continue;
					}
					if ($value->id == $user_row['reaction_type_id'] && $old_type_counted <= 1)
					{
						unset($json_data[$key]);
					}
					else if ($value->id == $user_row['reaction_type_id'])
					{	
						$new_value = ($old_type_counted - (int) 1);
						$value->count = strval($new_value);
					}
					if ($value->id == $type_id)
					{
						$new_value = ($new_type_counted + (int) 1);
						$value->count = strval($new_value);
					}
				}
			}

			if (!$new_type_counted)
			{
				$json_data[] = ['id' => $type_id, 'src' => $new_type, 'count' => "1"];
			}

			$post_data = $this->type_operator->remove_duplicate_encode($json_data);
			if (!empty($post_data))
			{
				$disabled_data_ids = $post_row['post_disabled_reaction_ids'] . '|' . $forum_row['forum_reaction_type_ids']; 
				$response_data = $this->type_operator->disabled_ids($post_data, $disabled_data_ids);
			}

			$this->db->sql_transaction('begin');

			$sql = 'UPDATE ' . POSTS_TABLE . "
				SET post_reaction_data = '" . $this->db->sql_escape($post_data) . "'  
				WHERE post_id = " . (int) $post_id;
			$this->db->sql_query($sql); 
			
			$sql_ary = [
				'reaction_file_name'		=> (string) $reaction['reaction_file_name'],
				'reaction_type_id'			=> (int) $type_id,
				'reaction_type_title'		=> (string) $reaction['reaction_type_title'],
				'reaction_time'				=> time(),
				'topic_id'					=> (int) $post_row['topic_id'],
			];

			$sql = 'UPDATE ' . $this->reactions_table . '
				SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
				WHERE post_id = ' . (int) $post_id . ' 
					AND reaction_user_id = ' . (int) $this->user->data['user_id'];
			$this->db->sql_query($sql);

			if ($post_row['poster_id'] != $this->user->data['user_id'])
			{
				$notification_data = [
					'reaction_id'			=> $user_row['reaction_id'],
					'item_id'				=> $user_row['reaction_id'],
					'item_parent_id'		=> $post_id,
					'post_id'				=> $post_id,
					'poster_id'				=> $post_row['poster_id'],
					'post_subject'			=> $post_row['post_subject'],
					'reaction_file_name'	=> $reaction['reaction_file_name'],
					'reaction_type_title'	=> $reaction['reaction_type_title'],
					'user_id' 				=> $this->user->data['user_id'],
				];
				$this->notification_manager->update_notifications('steve.reactions.notification.type.post_reaction', $notification_data);
			}

			$this->db->sql_transaction('commit');
			$update_reaction = true;
		}

		$get_user = [];
		$get_user = $this->get_user($post_row['poster_id']);

		if ($this->request->is_ajax() && ($add_reaction || $update_reaction))
		{
			$json_response = new \phpbb\json_response;
			$data_send = [
				'success' 				=> true,
				'NEW_TYPE'				=> $type_id,
				'POST_ID' 				=> $post_id,
				'POSTER_ID'				=> $post_row['poster_id'],
				'REACTION_DELETE'		=> $delete_url,
				'REACTIONS'				=> empty($update_reaction) ? ($post_row['post_reactions'] + (int) 1) : $post_row['post_reactions'],
				'TYPE_DATA' 			=> $response_data,
				'USER_TOTAL'			=> $get_user['user_reactions'],
				'VIEW_URL'				=> $view_url,
			];

			return $json_response->send($data_send);
		}
		else
		{
			meta_refresh(1, $this->type_operator->viewtopic_url() . $post_id);
			throw new \phpbb\exception\http_exception(200, 'REACTION_ADDED');
		}
	}

	private function get_user($user_id)
	{
		if (empty($user_id))
		{
			return false;
		}

		$sql = 'SELECT user_id, user_enable_reactions, user_reactions
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$user = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $user;
	}
}
