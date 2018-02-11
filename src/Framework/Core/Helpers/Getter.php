<?php
namespace Framework\Core\Helpers;

trait Getter
{
	final public function __get($key)
	{
		if (property_exists($this, $key)) {
			return $this->{$key};
		}

		return null;
	}
}