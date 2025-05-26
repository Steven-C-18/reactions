<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
	
	* for use in extensions/pages
*/

namespace steve\reactions\reaction;

class topic_reactions
{
	protected $auth;
	protected $config;
	protected $dispatcher;
	protected $template;
	protected $type_operator;
	
	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\event\dispatcher_interface $dispatcher,		
		\phpbb\template\template $template,
		\steve\postreactions\reaction\reaction_types $type_operator)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->dispatcher = $dispatcher;		
		$this->template = $template;
		$this->type_operator = $type_operator;
	}

	public function obtain_topic_reactions($post_reaction_data, $post_id)
	{
		if (empty($this->config['reactions_enabled']) || empty($post_reaction_data))
		{
			return false;
		}

		$json_data = json_decode($post_reaction_data);
		
		if (empty($json_data))
		{
			return false;
		}

		foreach ($json_data as $key => $value)
		{
			if (empty($value) || !is_object($value))
			{
				continue;
			}

			$reactions_row = [
				'ID'				=> $value->id,
				'COUNT'				=> !empty($value->count) && !empty($this->config['reactions_enable_count']) ? $value->count : (int) 0,
				'IMAGE_SRC'			=> $this->type_operator->get_reaction_file($value->src),
				'U_VIEW_LIST'		=> ($this->auth->acl_get('u_view_post_reactions_page') && !empty($this->config['reactions_posts_page_enabled'])) ? $this->type_operator->list_url($post_id, $value->id): false,
			];
			
			$loop = 'index_topics.reactions';
			$this->template->assign_block_vars($loop, $reactions_row);
			
			$vars = ['value', 'reactions_row', 'loop'];
			extract($this->dispatcher->trigger_event('steve.reactions.topic_reactions_modify_rows', compact($vars)));
		}
		unset($json_data);
		
		return $this;
	}
}
