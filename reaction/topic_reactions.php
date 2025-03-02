<?php
/**
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
	* @license GNU General Public License, version 2 (GPL-2.0)
*/
 
namespace steve\reactions\reaction;

class topic_reactions
{
	protected $auth;
	protected $config;
	protected $template;
	
	protected $type_operator;
	
	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\template\template $template,
		
		\steve\reactions\reaction\reaction_types $type_operator)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->template = $template;
		
		$this->type_operator = $type_operator;
	}

	public function obtain_topic_reactions($post_reaction_data, $post_id)
	{
		if (empty($this->config['reactions_enabled']))
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
			if (empty($value))
			{
				continue;
			}
			
			$view_list_url = $this->type_operator->list_url($post_id, $value->id);

			$reactions_row = [
				'ID'				=> $value->id,
				'COUNT'				=> !empty($value->count) ? $value->count : (int) 0,
				'IMAGE_SRC'			=> $this->type_operator->get_reaction_file($value->src),
				'U_VIEW_LIST'		=> ($this->auth->acl_get('u_view_post_reactions_page') && !empty($this->config['reactions_posts_page_enabled'])) ? $view_list_url : false,
			];

			$this->template->assign_block_vars('index_topics.reactions', $reactions_row);
		}
		
		return $this;
		unset($json_data);
	}
}
