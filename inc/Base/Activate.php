<?php

/**
 * @package  AlecadddPlugin
 */

namespace Inc\Base;

class Activate
{
	
	public  function register()
	{
		flush_rewrite_rules();

		// $this->add_default_leagues();
	}
	
}
