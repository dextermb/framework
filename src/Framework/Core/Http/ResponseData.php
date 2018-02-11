<?php
namespace Framework\Core\Http;

/**
 * Class ResponseData
 *
 * @property-read integer $status
 * @property-read $data
 * @property-read $meta
 *
 * @method status($status)
 * @method data($data)
 * @method meta($meta)
 *
 * @package Framework\Core\Http
 */
final class ResponseData
{
	protected $status;
	protected $data;
	protected $meta;

	public function __construct($data = null, $meta = null, $status = 200)
	{
		$this->status = $status;
		$this->data   = $data;
		$this->meta   = $meta;
	}

	public function __call($method, $args)
	{
		if (property_exists($this, $method)) {
			return $this->{$method} = $args[0];
		}

		return null;
	}

	public function __get($property)
	{
		if (property_exists($this, $property)) {
			return $this->{$property};
		}

		return null;
	}
}