<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\controller\viewtopic;

class delete_reaction
{
	protected $auth;
	protected $config;
	protected $db;
	protected $helper;
	protected $notification_manager;
	protected $request;
	protected $user;
	protected $type_operator;
	protected $reactions;

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

	public function delete($post_id, $user_id)
	{
		if (empty($this->config['reactions_enabled']))
		{
			throw new \phpbb\exception\http_exception(404, 'REACTIONS_DISABLED');
		}
		//optimize
		$sql = 'SELECT *
			FROM ' . POSTS_TABLE . ' p LEFT JOIN ' . TOPICS_TABLE . ' t ON p.topic_id = t.topic_id
			WHERE p.post_id = ' . (int) $post_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result); 
		$this->db->sql_freeresult($result);
		
		$sql = 'SELECT forum_id, forum_enable_reactions, forum_reaction_type_ids
			FROM ' . FORUMS_TABLE . ' 
			WHERE forum_id = ' . (int) $row['forum_id'];
		$result = $this->db->sql_query($sql);
		$forum_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		
		if (empty($this->config['reactions_topic_locked']) && $row['topic_status'] == ITEM_LOCKED)
		{
			throw new \phpbb\exception\http_exception(403, 'NO_AUTH_OPERATION');		
		}
		
		$sql = 'SELECT p.post_id, p.post_reaction_data, p.poster_id, p.post_reactions, r.*
			FROM ' . POSTS_TABLE . ' p LEFT JOIN ' . $this->reactions_table . ' r ON p.post_id = r.post_id
			WHERE p.post_id = ' . (int) $post_id . '
				AND r.reaction_user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$post_row = $this->db->sql_fetchrow($result); 
		$this->db->sql_freeresult($result);
	
		if (!empty($post_row))
		{
			$reaction = $this->type_operator->obtain_reaction_type($post_row['reaction_type_id']);
		}

		if (empty($post_id) || $post_id != isset($post_row['post_id']) || empty($user_id) || empty($post_row) || empty($reaction) || empty($reaction['reaction_type_enable']))
		{
			throw new \phpbb\exception\http_exception(404, 'REACTION_ERROR');
		}

		if (($post_row['reaction_user_id'] != $user_id || !$this->auth->acl_get('u_delete_reactions')) || (!$this->auth->acl_get('a_', 'm_') && $user_id != $this->user->data['user_id']))
		{
			throw new \phpbb\exception\http_exception(403, 'NO_AUTH_OPERATION');
		}

		if (!check_link_hash($this->request->variable('hash', ''), 'delete_reaction') || (!$this->request->is_ajax() && !$this->auth->acl_get('a_', 'm_')))
		{
			throw new \phpbb\exception\http_exception(403, 'NO_AUTH_OPERATION');
		}

 		$sql = 'SELECT COUNT(reaction_type_id) AS type_counted
			FROM ' . $this->reactions_table . '
			WHERE reaction_type_id = ' . (int) $post_row['reaction_type_id'] . '
				AND post_id = ' . (int) $post_id;
		$result = $this->db->sql_query($sql);
		$type_counted = (int) $this->db->sql_fetchfield('type_counted');
		$this->db->sql_freeresult($result);

		$post_data = $post_row['post_reaction_data'];
		$json_data = json_decode($post_data);

		foreach ($json_data as $key => $value)
		{
			if (empty($value))
			{
				continue;
			}

			if ($value->id == $post_row['reaction_type_id'] && $type_counted == (int) 1)
			{
				unset($json_data[$key]);
			}
			else if ($value->id == $post_row['reaction_type_id'])
			{	
				$new_value = ($type_counted - (int) 1);
				$value->count = strval($new_value);
			}
		}

		$post_data = $this->type_operator->remove_duplicate_encode($json_data);
		if (!empty($post_data))
		{
			$disabled_data_ids = $row['post_disabled_reaction_ids'] . '|' . $forum_row['forum_reaction_type_ids']; 
			$response_data = $this->type_operator->disabled_ids($post_data, $disabled_data_ids);
		}
		
		unset($json_data);

		$this->db->sql_transaction('begin');
		
		$sql = 'UPDATE ' . POSTS_TABLE . "
			SET post_reaction_data = '" . $this->db->sql_escape($post_data) . "',
				post_reactions = post_reactions - 1
			WHERE post_id = " . (int) $post_id;
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->reactions_table . ' 
			WHERE post_id  = ' . (int) $post_row['post_id'] . ' 
				AND reaction_user_id = ' . (int) $post_row['reaction_user_id'];
		$this->db->sql_query($sql);
		
		$sql = 'UPDATE ' . USERS_TABLE . '
			SET user_reactions = user_reactions - 1
			WHERE user_id = ' . (int) $post_row['poster_id'];
		$this->db->sql_query($sql);

		$this->notification_manager->delete_notifications('steve.reactions.notification.type.post_reaction', [
			'item_id'			=> $post_row['reaction_id'],
			'item_parent_id'	=> $post_row['post_id'],
		]);
		
		$this->db->sql_transaction('commit');
		
		$view_url = ($this->auth->acl_get('u_view_post_reactions_page') && !empty($this->config['reactions_posts_page_enabled'])) ? $this->helper->route('steve_reactions_view_reactions_controller_pages', ['post_id' => $post_id, 'reaction_id' => $post_row['reaction_type_id']]) : '';
		
		$get_user = [];
		$get_user = $this->get_user($post_row['poster_id']);
		
		if ($this->request->is_ajax())
		{
			$json_response = new \phpbb\json_response;
			$data_send = [
				'success' 				=> true,
				'POST_ID'				=> $post_id,
				'POSTER_ID'				=> $post_row['poster_id'],
				'REACTIONS'				=> ($post_row['post_reactions'] - (int) 1),
				'TYPE_DATA' 			=> $response_data,
				'USER_TOTAL'			=> $get_user['user_reactions'],
				'VIEW_URL'				=> $view_url,
			];
			
			return $json_response->send($data_send);
		}
		else
		{
			meta_refresh(1, $this->type_operator->viewtopic_url() . $post_id . '#p' . $post_id );
			throw new \phpbb\exception\http_exception(200, 'REACTION_DELETED');
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
