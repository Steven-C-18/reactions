<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\controller;

class popup
{
	protected $auth;
	protected $config;
	protected $db;
	protected $helper;
    protected $language;
	protected $request;
	protected $template;
	protected $user;
	protected $php_ext;
	protected $root_path;
	protected $pagination;
	protected $type_operator;
	protected $reactions_table;
	protected $query_limit;
	protected $page;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\controller\helper $helper,
		\phpbb\language\language $language,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\pagination $pagination,
		$php_ext,
		$root_path,
		\steve\reactions\reaction\reaction_types $type_operator,
		$reactions_table)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->db = $db;
		$this->helper = $helper;
		$this->language = $language;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->pagination = $pagination;
		$this->php_ext = $php_ext;
		$this->root_path = $root_path;
		$this->type_operator = $type_operator;
		$this->reactions_table = $reactions_table;
		$this->query_limit = $query_limit = isset($this->config['reactions_per_page']) ? $this->config['reactions_per_page'] : (int) 10;
		$this->page = $page = $this->request->variable('page', 0);
	}

  	public function load_post_reactions($post_id, $reaction_id)
	{
 		$this->general_checks();

		$where_and = '';
		$view_list_url = $this->helper->route('steve_reactions_view_reactions_controller_pages', ['post_id' => $post_id, 'reaction_id' => !$reaction_id ? 0 : $reaction_id]);

		if ($reaction_id)
		{
			$where_and = ' AND reaction_type_id = ' . (int) $reaction_id;
		}

		$sql = 'SELECT COUNT(post_id) AS item_count
			FROM ' . $this->reactions_table . '
			WHERE post_id = ' . (int) $post_id .
				$where_and;
		$result = $this->db->sql_query($sql);
		$this->item_count = (int) $this->db->sql_fetchfield('item_count');
		$this->db->sql_freeresult($result);

		$select_col = 'r.*, p.forum_id, p.post_id, p.post_visibility, p.post_enable_reactions, p.post_disabled_reaction_ids, t.topic_id, t.topic_enable_reactions,
						f.forum_id, f.forum_enable_reactions, f.forum_reaction_type_ids, ';
		$select_col .= 'u.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_enable_reactions, u.user_type';

		$sql_arr = [
			'SELECT'    => $select_col,
			'FROM'		=> [
				$this->reactions_table  => 'r',
			],
			'LEFT_JOIN' => [
				[
					'FROM'  => [USERS_TABLE => 'u'],
					'ON'    => 'u.user_id = r.reaction_user_id',
				],
				[
					'FROM'  => [POSTS_TABLE => 'p'],
					'ON'    => 'r.post_id = p.post_id',
				],
				[
					'FROM'  => [FORUMS_TABLE => 'f'],
					'ON'    => 'f.forum_id = p.forum_id',
				],
				[
					'FROM'  => [TOPICS_TABLE => 't'],
					'ON'    => 't.topic_id = r.topic_id',
				],
			],				
			'WHERE'		=> 'p.post_id = '. (int) $post_id . '
				AND p.post_enable_reactions = 1
				AND p.post_visibility = ' . ITEM_APPROVED . ' 
				AND r.post_id = ' . (int) $post_id . '
				AND u.user_type != ' . USER_INACTIVE . 
				$where_and,
		];
		$sql = $this->db->sql_build_query('SELECT', $sql_arr);
		$result = $this->db->sql_query_limit($sql, $this->query_limit, $this->page);

		$post_reactions = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			if (empty($row))
			{
				continue;
			}

			$user_enable_reactions = !$this->auth->acl_get('u_disable_reactions', $row['user_id']) ? true : ($row['user_enable_reactions'] ? true : false);
			if (empty($user_enable_reactions) && $item_count == (int) 1)
			{
				throw new \phpbb\exception\http_exception(404, 'REACTIONS_DISABLED_USER');
			}

			$post_reactions[] = $row;
		}
		$this->db->sql_freeresult($result);

		if (count($post_reactions))
		{
			foreach ($post_reactions as $row)
			{
				// more work on disabled [ids]
				$check_disabled_reactions = isset($row['post_disabled_reaction_ids']) ? $this->type_operator->disabled_reactions($row['forum_reaction_type_ids'], $row['post_disabled_reaction_ids'], '') : '';
				if ($check_disabled_reactions && in_array($row['reaction_type_id'], $check_disabled_reactions))
				{
					if (!empty($reaction_id))
					{
						throw new \phpbb\exception\http_exception(403, 'REACTIONS_DISABLED_USER');
					}
					else
					{
						continue;
					}
				}

				if (!$this->auth->acl_get('f_read', $row['forum_id']) || empty($row['forum_enable_reactions']) || empty($row['topic_enable_reactions']) || empty($row['post_enable_reactions']))
				{
					if (!empty($reaction_id))
					{
						throw new \phpbb\exception\http_exception(403, 'REACTIONS_DISABLED_USER');
					}
					else
					{
						continue;
					}
				}

				$user_enable_reactions = !$this->auth->acl_get('u_disable_reactions', $row['user_id']) ? true : ($row['user_enable_reactions'] ? true : false);			
				if (empty($user_enable_reactions) && $item_count == (int) 1)
				{
					throw new \phpbb\exception\http_exception(403, 'REACTIONS_DISABLED_USER');
				}
				
				if (empty($user_enable_reactions))
				{
					continue;
				}

				$user_data[] = [
					'name'				=> $row['username'],
					'color'				=> $row['user_colour'],
					'user_id'			=> $row['user_id'],
					'time'				=> $this->user->format_date($row['reaction_time']),
					'post_id'			=> $row['post_id'],
					'avatar'			=> $row['user_avatar'] ? $row['user_avatar'] : false,
					'img'				=> $row['reaction_file_name'],
					'title'				=> $row['reaction_type_title'],
					'has_id'			=> $reaction_id ? true : false,
				];

				$json_data = json_encode($user_data);
			}
			unset($post_reactions);
		}

		$success = true;
		return $this->response([
			'success'		 	=> $success,
			'count'				=> $this->item_count,
			'page'				=> $this->page,
			'url'				=> $view_list_url,
			'user_data'			=> $json_data,
			'auth'				=> $this->auth->acl_gets('a_', 'm_') ? true : false,
		]);
	}

	private function response($data = [])
	{
		if (!$this->request->is_ajax() || !$data)
		{
			throw new \phpbb\exception\http_exception(500, 'GENERAL_ERROR');
		}

		$json_response = new \phpbb\json_response;
		return $json_response->send($data);
	}

	public function user_reactions($user_id, $type_id, $view)
	{
		$this->general_checks();

		$item_count = $this->count($user_id, $type_id, $view);

		if (empty($user_id) || empty($type_id) || empty($item_count) || !in_array($view, ['received', 'reacted']))
		{
			throw new \phpbb\exception\http_exception(404, 'REACTIONS_DISABLED');
		}

		$sql = 'SELECT user_id, username, user_colour
			FROM ' . USERS_TABLE . '
				WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$user_data = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$poster_name = $user_data['username'];
		$poster_route = get_username_string('profile', $user_data['user_id'], $user_data['username'], $user_data['user_colour']);
		
		$result = $this->get_reactions($user_id, $type_id, $view);

		$reactions = [];
		$reaction_type_title = $reaction_file_name = '';
		$i = 0;
		while ($row = $this->db->sql_fetchrow($result))
		{
			if (empty($row))
			{
				continue;
			}

			$reaction_file_name = $row['reaction_file_name'];
			$reaction_type_title = $row['reaction_type_title'];
			$reactions[$i] = $row;
			$i++;
		}
		$this->db->sql_freeresult($result);

		if (count($reactions))
		{
			foreach ($reactions as $row)
			{
				if (empty($row) || empty($row['user_enable_reactions']))
				{
					continue;
				}
				$check_disabled_reactions = isset($row['post_disabled_reaction_ids']) ? explode('|', $row['post_disabled_reaction_ids']) : '';
				if ($check_disabled_reactions !== '' && in_array($row['reaction_type_id'], $check_disabled_reactions, true))
				{
					continue;
				}

				$u_view_post = true;
				if (!$this->auth->acl_get('f_read', $row['forum_id']) || empty($row['forum_enable_reactions']) || empty($row['topic_enable_reactions']) || empty($row['post_enable_reactions']))
				{
					$u_view_post = false;
				}

				$this->template->assign_block_vars('reaction', [
					'NAME'				=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
					'TIME'				=> $this->user->format_date($row['reaction_time']),
					'USER_AVATAR'		=> !empty($this->user->optionget('viewavatars')) ? phpbb_get_user_avatar($row) : false,
					'U_VIEW_POST'		=> $u_view_post ? append_sid($this->root_path . 'viewtopic.' . $this->php_ext . '?p=' . $row['post_id'] . '#p' . $row['post_id']) : false,
					'POST_SUBJECT'		=> censor_text($row['post_subject']),
				]);
			}
			unset($reactions);
		}

		$this->type_operator->tpr_common_vars([
			'USER_REACTIONS'	=> true,
			'IMG_SRC'			=> $this->type_operator->get_reaction_file($reaction_file_name),
			'TITLE'				=> censor_text($reaction_type_title),
			'COUNT' 			=> $item_count,
		]);

		$view_user_reactions = $this->helper->route('steve_reactions_view_user_reactions_controller_pages', ['user_id' => $user_id, 'type_id' => $type_id, 'view' => $view]);
		$this->pagination->generate_template_pagination($view_user_reactions, 'pagination', 'page', $item_count, $this->query_limit, $this->page);

		$page_title_info = ' ' . $poster_name . ' ' . $reaction_type_title . ' ';

		$this->nav_links([
			['title' => $this->language->lang('REACTIONS'), 'route' => $this->helper->route('steve_reactions_view_reactions_controller_page')],
			['title' => $poster_name, 'route' => $poster_route],
			['title' => $reaction_type_title . ' ' . $this->language->lang('REACTIONS'), 'route' => $view_user_reactions],
		]);

		$page_title = $this->page ? $page_title_info . ' ' . $this->language->lang('REACTIONS_TITLES', $this->pagination->get_on_page($this->query_limit, $this->page)) : $page_title_info . ' ' . $this->language->lang('REACTIONS');
		
		//convert to popup like viewtopic/
		return $this->helper->render('memberlist_reactions_body.html', $page_title);
	}

	private function get_reactions($user_id, $type_id, $view, $sql_and = true)
	{
		$sql_where = $view == 'received' ? " r.poster_id = " . (int) $user_id : " r.reaction_user_id = " . (int) $user_id;
		$sql_and = 'AND r.reaction_type_id = ' . (int) $type_id;

		$sql_arr = [
			'SELECT'    => 'r.*,
				u.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_enable_reactions,
				p.post_id, p.post_enable_reactions, p.post_disabled_reaction_ids, p.post_subject,
				t.topic_id, t.topic_enable_reactions,
				f.forum_id, f.forum_enable_reactions',
			'FROM'		=> [
				$this->reactions_table  => 'r',
			],
			'LEFT_JOIN' => [
				[
					'FROM'  => [USERS_TABLE => 'u'],
					'ON'    => 'u.user_id = r.reaction_user_id',
				],
				[
					'FROM'  => [POSTS_TABLE => 'p'],
					'ON'    => 'r.post_id = p.post_id',
				],
				[
					'FROM'  => [FORUMS_TABLE => 'f'],
					'ON'    => 'f.forum_id = p.forum_id',
				],
				[
					'FROM'  => [TOPICS_TABLE => 't'],
					'ON'    => 't.topic_id = r.topic_id',
				],
			],
			'WHERE'		=> $sql_where . '
				' . $sql_and,
			'ORDER_BY'	=> 'r.reaction_time DESC',
		];
		$sql = $this->db->sql_build_query('SELECT', $sql_arr);

		return $this->db->sql_query_limit($sql, $this->query_limit, $this->page);
	}

	private function count($user_id, $type_id, $view)
	{
		$sql_where = $view == 'received' ? " poster_id = " . (int) $user_id : " reaction_user_id = " . (int) $user_id;

		$sql = 'SELECT COUNT(reaction_type_id) AS count
			FROM ' . $this->reactions_table . '
			WHERE ' . $sql_where . '
				AND reaction_type_id = ' . (int) $type_id;
		$result = $this->db->sql_query($sql);
		$count = (int) $this->db->sql_fetchfield('count');
		$this->db->sql_freeresult($result);

		return $count;
	}

	private function general_checks()
	{
 		if (!$this->auth->acl_get('u_view_reactions_pages'))
		{
			throw new \phpbb\exception\http_exception(403, 'NOT_AUTHORISED_REACTIONS');
		}

		$user_enable_reactions = (!$this->auth->acl_get('u_disable_reactions', $this->user->data['user_id'])) ? true : ($this->user->data['user_enable_reactions'] ? true  : false);
		$reactions_enabled = (!empty($this->config['reactions_enabled']) && !empty($this->config['reactions_page_enabled'])) ? true : false;
 		if (empty($reactions_enabled) || empty($user_enable_reactions))
		{
			throw new \phpbb\exception\http_exception(404, 'REACTIONS_DISABLED');
		}
	}

	private function nav_links($navlinks = [])
	{
		foreach ($navlinks as $navlink)
		{
			$this->template->assign_block_vars('navlinks', [
				'BREADCRUMB_NAME' 		=> $navlink['title'],
				'U_BREADCRUMB' 			=> $navlink['route'],
			]);
		}
		unset($navlinks);

		return $this;
	}
}
