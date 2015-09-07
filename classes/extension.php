<?php

/*
 * Base class for all PeepSo Application extensions
 */
class PeepSoExtension
{
	protected static $_instance = NULL;

	public $template_tags = array();

	public static function get_instance()
	{
		if (self::$_instance === NULL)
			self::$_instance = new self();
		return (self::$_instance);
	}
}

// EOF