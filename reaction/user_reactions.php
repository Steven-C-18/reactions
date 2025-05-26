<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\reaction;

class user_reactions
{
	protected $auth;
	protected $config;
	protected $helper;	
	protected $db;
    protected $language;	
	protected $template;
	protected $user;

	protected $type_operator;
	protected $reactions_table;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\controller\helper $helper,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\language\language $language,		
		\phpbb\template\template $template,
		\phpbb\user $user,

		\steve\reactions\reaction\reaction_types $type_operator,
		$reactions_table)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->helper = $helper;		
		$this->db = $db;
		$this->language = $language;
		$this->template = $template;
		$this->user = $user;

		$this->type_operator = $type_operator;	
		$this->reactions_table = $reactions_table;		
	}

	public function obtain_disabled_reactions()
	{
		return explode(',', $this->type_operator->obtain_reaction_type_ids());
	}

	public function obtain_all_disabled_reactions($disabled_reactions, $user_disabled_reactions)
	{
		$this->disabled_reaction_ids = $disabled_reactions;
		$this->user_disabled_ids = explode('|', $user_disabled_reactions);
	}

	public function obtain_user_reactions($user_id, $reaction_count, $recieved_reactions = false)
	{
		if (empty($user_id) || $user_id == ANONYMOUS || empty($reaction_count) || $this->user->data['is_bot'])
		{
			return false;
		}

		$sql_where = $recieved_reactions ? " WHERE poster_id = " . (int) $user_id : " WHERE reaction_user_id = " . (int) $user_id;

		$sql_select = $recieved_reactions ? 'SELECT COUNT(reaction_type_id) as total_count, reaction_type_id, reaction_file_name, GROUP_CONCAT(DISTINCT reaction_type_title) as title, GROUP_CONCAT(poster_id) as poster_id'
			: 'SELECT COUNT(reaction_type_id) as total_count, reaction_type_id, reaction_file_name, GROUP_CONCAT(DISTINCT reaction_type_title) as title, GROUP_CONCAT(reaction_user_id) as reaction_user_id';

		$sql = " $sql_select
			FROM " . $this->reactions_table . "
				$sql_where
				AND " . $this->db->sql_in_set('reaction_type_id', array_unique($this->disabled_reaction_ids), false) . "
					AND " . $this->db->sql_in_set('reaction_type_id', array_unique($this->user_disabled_ids), true) . "
			GROUP BY reaction_type_id, reaction_file_name
			ORDER BY reaction_type_id ASC";
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

		if (!empty($reactions))
		{
			foreach ($reactions as $row)
			{
				if (empty($row))
				{
					continue;
				}

				$reactions_vars = [
					'COUNT' 		=> $this->type_operator->round_counts($row['total_count']),
					'PERCENT'		=> !empty($this->config['reactions_enable_percentage']) ? $this->percentage($row['total_count'], $reaction_count) . $this->language->lang('REACTION_PERCENT') : false,
					'IMAGE_SRC'		=> $this->type_operator->get_reaction_file($row['reaction_file_name']),
					'TITLE'			=> censor_text(str_replace(',', ', ', $row['title'])),
					'VIEW'			=> $this->helper->route('steve_reactions_view_user_reactions_controller_pages', ['user_id' 	=> $user_id, 'type_id' 	=> $row['reaction_type_id'], 'view' =>  $recieved_reactions ? 'received' : 'reacted']),
				];
				$this->template->assign_block_vars($recieved_reactions ? 'recieved_reactions' : 'reactions', $reactions_vars);
			}
			unset($reactions);
		}

		$this->type_operator->tpr_common_vars([$recieved_reactions ? 'RECENT_REACTIONS' : 'REACTIONS' => $reaction_count, 'S_REACTION_MEMBERS' => true]);

		return $this;
	}

	private function percentage($reaction_type_ids, $reactions_total)
	{
		return (string) number_format($reaction_type_ids / $reactions_total * 100, 1);		
	}
}
