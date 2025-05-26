<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace steve\reactions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class user_listener implements EventSubscriberInterface
{
	protected $auth;
	protected $config;
	protected $db;
	protected $user;
	protected $delete_operator;
	protected $user_operator;
	protected $reactions_table;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user,
		\steve\reactions\reaction\delete_reactions $delete_operator,
		\steve\reactions\reaction\user_reactions $user_operator,
		$reactions_table)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
		$this->delete_operator = $delete_operator;
		$this->user_operator = $user_operator;
		$this->reactions_table = $reactions_table;
	}

	static public function getSubscribedEvents()
	{
		return [
			'core.memberlist_view_profile'		=> 'memberlist_view_profile',
			'core.delete_user_after'			=> 'delete_user_after',
		];
	}

	public function memberlist_view_profile($event)
	{
		$user_enable_reactions = !$this->auth->acl_get('u_disable_reactions', $this->user->data['user_id']) ? true : ($this->user->data['user_enable_reactions'] ? true  : false);
		$member_enable_reactions = !$this->auth->acl_get('u_disable_reactions', $event['member']['user_id']) ? true : ($event['member']['user_enable_reactions'] ? true  : false);

		if (empty($this->config['reactions_enabled']) || !$member_enable_reactions || !$user_enable_reactions || !$this->auth->acl_get('u_view_reactions'))
		{
			return;
		}

		$disabled_reactions = $this->user_operator->obtain_disabled_reactions();

		$reacted = $this->get_user_counts('reaction_user_id', $event['member']['user_id'], $disabled_reactions, $event['member']['user_disabled_reaction_ids']);
		$reactions = $this->get_user_counts('poster_id', $event['member']['user_id'], $disabled_reactions, $event['member']['user_disabled_reaction_ids']);

		if (!empty($reactions) || !empty($reacted))
		{
			$this->user_operator->obtain_all_disabled_reactions($disabled_reactions, $event['member']['user_disabled_reaction_ids']);
		}

		if (!empty($reactions))
		{
			$this->user_operator->obtain_user_reactions($event['member']['user_id'], $reactions, true);
		}

		if (!empty($reacted))
		{
			$this->user_operator->obtain_user_reactions($event['member']['user_id'], $reacted, false);
		}
	}

	private function get_user_counts($row, $member_user_id, $disabled_reactions, $user_disabled_reaction_ids)
	{
		$sql = 'SELECT COUNT(' . $row . ') AS count
			FROM ' . $this->reactions_table . "
				WHERE " . $row . " = " . (int) $member_user_id . "
			AND " . $this->db->sql_in_set('reaction_type_id', array_unique($disabled_reactions), false) . "
				AND " . $this->db->sql_in_set('reaction_type_id', array_unique(explode('|',  $user_disabled_reaction_ids)), true);
		$result = $this->db->sql_query($sql);
		
		return (int) $this->db->sql_fetchfield('count');
	}

	public function delete_user_after($event)
	{
 		foreach ($event['user_ids'] as $user_id)
		{
			$sql = 'SELECT p.post_id, p.post_reaction_data, p.poster_id, r.*
				FROM ' . POSTS_TABLE . ' p LEFT JOIN ' . $this->reactions_table . ' r ON p.post_id = r.post_id
					WHERE r.reaction_user_id = ' . (int) $user_id . '
				AND p.poster_id <> ' . (int) $user_id;
			$result = $this->db->sql_query($sql);

			$post_data = [];
			if ($row = $this->db->sql_fetchrow($result))
			{
				do
				{
					if (empty($row))
					{
						continue;
					}

					$post_data[] = $row;
				}
				while ($row = $this->db->sql_fetchrow($result));

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

								if ($value->id == $data['reaction_type_id'] && $value->count == (int) 1)
								{
									unset($json_data[$key]);
								}
								else if ($value->id == $data['reaction_type_id'])
								{
									$new_value = ($value->count - (int) 1);
									$value->count = strval($new_value);
								}
							}
						}

						$json_data = array_map("unserialize", array_unique(array_map("serialize", $json_data)));
						$post_data = array_values($json_data);
						$post_data = json_encode($post_data);

						$sql = 'UPDATE ' . POSTS_TABLE . "
							SET post_reaction_data = '" . $this->db->sql_escape($post_data) . "'
							WHERE post_id = " . (int) $data['post_id'];
						$this->db->sql_query($sql);	
					}
					unset($post_data, $json_data);
				}
			}
			$this->db->sql_freeresult($result);
		}

		$reactions = $this->delete_operator->delete_post_reactions('reaction_user_id', $event['user_ids']);

		$this->delete_operator->update_reaction_counts($reactions);

		$this->delete_operator->delete_reactions('reaction_user_id', $event['user_ids']);

		$this->delete_operator->delete_reactions('poster_id', $event['user_ids']);
	}
}
