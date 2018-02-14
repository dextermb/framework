<?php
namespace Framework;

use Dotenv\Dotenv;

class Bootstrap
{
	static function init()
	{
		// Load environment files
		(new Dotenv($_SERVER['DOCUMENT_ROOT']))->load();
	}
}

Bootstrap::init();