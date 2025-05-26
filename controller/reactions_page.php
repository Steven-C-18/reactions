<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\controller;

class reactions_page
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

	public function view_reactions()
	{
		$sql = 'SELECT COUNT(DISTINCT poster_id) AS poster_count
			FROM ' . $this->reactions_table;
		$result = $this->db->sql_query($sql);
		$poster_count = (int) $this->db->sql_fetchfield('poster_count');
		$this->db->sql_freeresult($result);

		if (empty($poster_count))
		{
			throw new \phpbb\exception\http_exception(404, 'REACTIONS_DISABLED');
		}

 		$this->general_checks();

		$this->type_operator->obtain_top_reaction_types('page', '', 0, 0);
		$reaction_type = $this->type_operator->obtain_reaction_types();

		$enabled_reaction_ids = $this->type_operator->obtain_reaction_type_ids();
		$enabled_reaction_ids = explode(',', $enabled_reaction_ids);

		$poster_ids = $recent_reactions = $reaction_user = $user_disabled_reaction_ids = [];

		$sql = 'SELECT user_id, username, user_avatar, user_avatar_type, user_avatar_width, user_avatar_height, user_colour, user_rank, user_reactions, user_enable_reactions, user_disabled_reaction_ids
			FROM ' . USERS_TABLE . '
			WHERE user_reactions > 0
				AND user_type != '. USER_INACTIVE . '
				AND user_id <> ' . ANONYMOUS . '
			ORDER BY user_reactions DESC';
		$result = $this->db->sql_query_limit($sql, $this->query_limit, $this->page);

		$i = 0;
		while ($rows = $this->db->sql_fetchrow($result))
		{
			$user_enable_reactions = (!$this->auth->acl_get('u_disable_reactions')) ? true : ($rows['user_enable_reactions'] ? true : false);
			if (empty($rows) || !$user_enable_reactions)
			{
				continue;
			}
			$reaction_user[$i] = $rows;
			$poster_ids[$i] = $rows['user_id'];
			$user_disabled_reaction_ids[(int) $rows['user_id']] = $rows['user_disabled_reaction_ids'];
			$i++;
		}
		$this->db->sql_freeresult($result);

		if (count($poster_ids))
		{
			$sql = " SELECT COUNT(reaction_type_id) as total_count, reaction_type_id, poster_id, reaction_file_name, GROUP_CONCAT(DISTINCT reaction_type_title) as title
				FROM " . $this->reactions_table . "
					WHERE " . $this->db->sql_in_set('poster_id', array_unique($poster_ids)) . "
						AND " . $this->db->sql_in_set('reaction_type_id', array_unique($enabled_reaction_ids)) . "
				GROUP BY reaction_type_id, poster_id, reaction_file_name
				ORDER BY reaction_type_id ASC";
			$result = $this->db->sql_query($sql);

			while ($rows = $this->db->sql_fetchrow($result))
			{
				if (!empty($user_disabled_reaction_ids[$rows['poster_id']]))
				{
					$user_disabled_ids = explode('|', $user_disabled_reaction_ids[$rows['poster_id']]);
					if (in_array($rows['reaction_type_id'], $user_disabled_ids))
					{
						continue;
					}
				}
				$recent_reactions[(int) $rows['poster_id']][] = $rows;
			}
			$this->db->sql_freeresult($result);
		}

		for ($i = 0, $end = count($reaction_user); $i < $end; ++$i)
		{
			if (!isset($reaction_user[$i]))
			{
				continue;
			}

			$row = $reaction_user[$i];
			$poster_id = $row['user_id'];

			$option_avatar = $this->user->optionget('viewavatars') ? true : false;

			$user_row = [
				'DISPLAY_AVATAR'	=> $option_avatar,
				'USER_AVATAR'		=> $option_avatar ? phpbb_get_user_avatar($row) : false,
				'USER_NAME'			=> get_username_string('full', $poster_id, $row['username'], $row['user_colour']),
				'USER_REACTIONS'	=> $this->language->lang('USER_REACTIONS', $row['user_reactions']),
			];

			$this->template->assign_block_vars('reactions', $user_row);

			if (!empty($recent_reactions[$poster_id]))
			{
				foreach ($recent_reactions[$poster_id] as $reaction)
				{
					$reaction_types = [
						'COUNT' 		=> $reaction['total_count'],
						'PERCENT'		=> !empty($this->config['reactions_enable_percentage']) ? $this->percentage($reaction['total_count'], $row['user_reactions']) . $this->language->lang('REACTION_PERCENT') : false,
						'IMAGE_SRC'		=> $this->type_operator->get_reaction_file($reaction['reaction_file_name']),
						'TITLE'			=> censor_text($reaction['title']),
						'U_VIEW'		=> $this->helper->route('steve_reactions_view_user_reactions_controller_pages', ['user_id' 	=> $poster_id, 'type_id' => $reaction['reaction_type_id'], 'view' => 'received']),
					];

					$this->template->assign_block_vars('reactions.recent', $reaction_types);
				}
				unset($recent_reactions[$poster_id]);
			}
		}
		unset($reaction_user);

		$view_reactions = $this->helper->route('steve_reactions_view_reactions_controller_page');
		$this->pagination->generate_template_pagination($view_reactions, 'pagination', 'page', $poster_count, $this->query_limit, $this->page);

		$welome_reactions = $this->user->data['user_id'] != ANONYMOUS ? get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour']) : $this->language->lang('GUEST');
		$this->type_operator->tpr_common_vars([
			'POSTER_COUNT'				=> $this->language->lang('TOTAL_USERS', $poster_count),
			'WELCOME_REACTIONS_PAGE'	=> $this->language->lang('WELCOME_REACTIONS_PAGE', $welome_reactions, $poster_count),
		]);

		$this->template->assign_block_vars('navlinks', [
			'U_BREADCRUMB'		=> $view_reactions,
			'BREADCRUMB_NAME'	=> $this->language->lang('REACTIONS'),
		]);
		
		$page_title = $this->page ? $this->language->lang('REACTIONS_TITLES', $this->pagination->get_on_page($this->query_limit, $this->page)) : $this->language->lang('REACTIONS_TITLE');
		return $this->helper->render('reactions_page_body.html', $page_title);
	}

	private function percentage($reaction_type_ids, $reactions_total)
	{
		return (string) number_format($reaction_type_ids / $reactions_total * 100, 1);		
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
}
