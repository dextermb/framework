<?php
namespace Framework\Core\Helpers;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;

use Framework\Core\Exceptions\AuthException;
use Framework\Core\Exceptions\ArrayException;

class Auth
{
	protected $issuer;
	protected $audience;

	protected $validator;

	public function __construct()
	{
		$this->validator = new ValidationData;

		$this->validator->setIssuer($this->issuer = env('JWT_ISSUER', 'github.com/dextermb/api-framework'));
		$this->validator->setAudience($this->audience = env('JWT_ISSUER', 'github.com/dextermb/api-framework'));
	}

	/**
	 * Create a JWT token
	 *
	 * @param array          $payload
	 * @param string|integer $sub
	 * @param integer        $exp
	 * @throws ArrayException
	 * @return string
	 */
	public static function create(array $payload, $sub = null, int $exp = null)
	{
		Arr::associative($payload, true);

		$auth  = new Auth;
		$token = new Builder;

		$token->setIssuer($auth->issuer)
			  ->setAudience($auth->audience)
			  ->setIssuedAt(time())
			  ->setExpiration(time() + (($exp ?: 24) * (60 * 60)));

		if (!is_null($sub)) {
			$token->setSubject($sub);
		}

		$token->set('data', $payload);

		return (string)$token->getToken();
	}

	/**
	 * Return a token class
	 *
	 * @param string $token
	 * @return \Lcobucci\JWT\Token
	 */
	public static function decode(string $token)
	{
		return (new Parser)->parse($token);
	}

	/**
	 * Return payload data for a token
	 *
	 * @param string $token
	 * @return array
	 */
	public static function payload(string $token)
	{
		$token = self::decode($token);

		return array_merge($token->hasClaim('sub') ? [ 'id' => $token->getClaim('sub') ] : [], (array)$token->getClaim('data'));
	}

	/**
	 * Validate a token
	 *
	 * @param string $token
	 * @param bool   $throw
	 * @throws AuthException
	 * @return Token|bool
	 */
	public static function validate(string $token, bool $throw = false)
	{
		$auth  = new Auth;
		$token = self::decode($token);

		if (!$token->validate($auth->validator)) {
			if ($throw) {
				throw new AuthException('Invalid token');
			}

			return false;
		};

		return $token;
	}
}