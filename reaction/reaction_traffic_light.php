<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\reaction;

class reaction_traffic_light
{
	protected $config;
	protected $db;
    protected $language;
	protected $reaction_types_table;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\language\language $language,
		//
		$reaction_types_table)
	{
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
		//
		$this->reaction_types_table = $reaction_types_table;
		$this->const = new \steve\reactions\reaction\constants;
	}
	
	public function reaction_type_tl_is_available()
	{
		$sql = 'SELECT reaction_type_tl_run
			FROM ' . $this->reaction_types_table . '
			WHERE reaction_type_tl_run = 0
				AND reaction_type_enable = 1';
		$result = $this->db->sql_query_limit($sql, (int) 1);
		$is_available = $this->db->sql_fetchrow($result);
		
		if (empty($is_available))
		{
			//
			$this->config->set('reactions_enable_traffic_light', false);
			return false;
		}

		return true;
	}
	
	public function set_reaction_type_tl_run($type_id)
	{
		if (empty($type_id))
		{
			return false;
		}

		$sql = 'UPDATE ' . $this->reaction_types_table . '
			SET reaction_type_tl_run = 1
			WHERE reaction_type_id = ' . (int) $type_id;
		$this->db->sql_query($sql);

		return $this->db->sql_affectedrows();//catch the count in ->log
	}

	public function get_user($user_id)
	{
		if (empty($user_id))
		{
			return false;
		}

		$sql = 'SELECT user_id, user_enable_reactions, user_reactions, user_reactions_positive, user_reactions_neutral, user_reactions_negative
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$user = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (array) $user;
	}

//needs reworked
	public function set_user_traffic_light($traffic_light, $count, $poster_id)
	{
		switch ($traffic_light) {
			case $this->const::TPR_TL_GO:
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET user_reactions_positive = ' . (int) $count . '
					WHERE user_id = ' . (int) $poster_id;
				$this->db->sql_query($sql);
		    break;
			case $this->const::TPR_TL_AMBER:
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET user_reactions_neutral = ' . (int) $count . '
					WHERE user_id = ' . (int) $poster_id;
				$this->db->sql_query($sql);
		    break;
			case $this->const::TPR_TL_STOP:
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET user_reactions_negative = ' . (int) $count . '
					WHERE user_id = ' . (int) $poster_id;
				$this->db->sql_query($sql);
   			break;
		}

		return $this;
	}

	public function adjust_counts_pos($traffic_light, $user_id, $update)
	{
		if (!isset($traffic_light))
		{
			return false;
		}
		
		// ?  + 1 : - 1;
		$user_reactions = $update == false ? 'user_reactions = user_reactions + 1,' : '';
		switch ($traffic_light) {
			case $this->const::TPR_TL_GO:
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET ' . $user_reactions . '
						user_reactions_positive = user_reactions_positive + 1
					WHERE user_id = ' . (int) $user_id;
				$this->db->sql_query($sql);
		    break;
			case $this->const::TPR_TL_AMBER:
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET ' . $user_reactions . '
						user_reactions_neutral = user_reactions_neutral + 1
					WHERE user_id = ' . (int) $user_id;
				$this->db->sql_query($sql);
		    break;
			case $this->const::TPR_TL_STOP:
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET ' . $user_reactions . '
						user_reactions_negative = user_reactions_negative + 1
					WHERE user_id = ' . (int) $user_id;
				$this->db->sql_query($sql);
   			break;
   			default:
   				return false;
   			break;
		}

		return $this;
	}

	public function adjust_counts_neq($traffic_light, $user_id, $update)
	{
		$user_reactions = $update == false ? 'user_reactions = user_reactions - 1,' : '';
		switch ($traffic_light) {
			case $this->const::TPR_TL_GO:
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET ' . $user_reactions . '
						user_reactions_positive = user_reactions_positive - 1
					WHERE user_id = ' . (int) $user_id;
				$this->db->sql_query($sql);
		    break;
			case $this->const::TPR_TL_AMBER:
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET ' . $user_reactions . '
						user_reactions_neutral = user_reactions_neutral - 1
					WHERE user_id = ' . (int) $user_id;
				$this->db->sql_query($sql);
		    break;
			case $this->const::TPR_TL_STOP:
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET ' . $user_reactions . '
						user_reactions_negative = user_reactions_negative - 1
					WHERE user_id = ' . (int) $user_id;
				$this->db->sql_query($sql);
   			break;
   			default:
   				return false;
   			break;
		}

		return $this;
	}

	public function reset_tl()
	{
		//table
		$sql = 'UPDATE ' . $this->reactions_table . '
			SET reaction_traffic_light = 0';
		$this->db->sql_query($sql);
		//build array
		$sql = 'UPDATE ' . USERS_TABLE . '
			SET user_reactions_positive = 0,
			 	user_reactions_neutral = 0,
			 	user_reactions_negative = 0';
		$this->db->sql_query($sql);

		$sql = 'UPDATE ' . $this->reaction_types_table . '
			SET reaction_type_traffic_light = 0';
		$this->db->sql_query($sql);

		$sql = 'UPDATE ' . $this->reaction_types_table . '
			SET reaction_type_tl_run = 0';
		$this->db->sql_query($sql);

		return $this;
	}

	public function traffic_light($traffic_light)
	{
		switch ($traffic_light) {
			case $this->const::TPR_TL_GO:
				return (string) 'traffic_light_go';
		    break;
			case $this->const::TPR_TL_AMBER:
				return (string) 'traffic_light_amber';
		    break;
			case $this->const::TPR_TL_STOP:
				return (string) 'traffic_light_stop';
   			break;
   			default:
   				return false;
   			break;
		}
		
		return $this;		
	}

	public function traffic_light_acp($traffic_light)
	{
		switch ($traffic_light) {
			case $this->const::TPR_TL_GO:
				return (string) 'fa-smile-o icon-green';
		    break;
			case $this->const::TPR_TL_AMBER:
				return (string) 'fa-meh-o acp-icon-resync';
		    break;
			case $this->const::TPR_TL_STOP:
				return (string) 'fa-frown-o icon-red';
   			break;
   			default:
   				return false;
   			break;
		}
		
		return $this;		
	}

	public function percentage($count, $total)
	{
		if (empty($total))
		{
			return (int) 0;
		}

		$division = $count / $total;

		$percentage = number_format($division * 100, 1) . $this->language->lang('REACTION_PERCENT');

		return (string) $count . ' (' . $percentage . ')';
	}
}
