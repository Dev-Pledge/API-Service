<?php

namespace DevPledge\Uuid;

use Ramsey\Uuid\Uuid as Ruuid;

/**
 * Class Uuid
 * @package DevPledge\Uuid
 */
class Uuid {
	/**
	 * @var array
	 */
	protected static $entities = [
		'permission'    => 'prm',
		'comment'       => 'cmt',
		'currency'      => 'cur',
		'kudos'         => 'kud',
		'org'           => 'org',
		'payment'       => 'pay',
		'payment_means' => 'pym',
		'pledge'        => 'plg',
		'problem'       => 'prb',
		'solution'      => 'sol',
		'topic'         => 'top',
		'user'          => 'usr',
		'user_group'    => 'usg',
	];

	protected static $uuidLength = 36;
	protected static $uuidParts = 5;

	/**
	 * @var string
	 */
	protected $prefix;

	/**
	 * @var string
	 */
	protected $uuid;

	/**
	 * Uuid constructor.
	 *
	 * @param string $uuid
	 * @param null|string $prefix
	 */
	public function __construct( string $uuid, string $prefix = null ) {
		if ( is_null( $prefix ) ) {
			$this->parsePrefixedUuid( $uuid );
		} else {
			$this->setPrefix( $prefix );
			$this->setUuid( $uuid );
		}
	}

	/**
	 * @return array
	 */
	public static function getEntities(): array {
		return self::$entities;
	}

	/**
	 * @param string $entity
	 *
	 * @return Uuid
	 */
	public static function make( string $entity ): Uuid {
		$uuid   = Ruuid::uuid4()->toString();
		$prefix = self::prefixFromEntity( $entity );

		return new static( $uuid, $prefix );
	}

	/**
	 * @param string $entity
	 *
	 * @return string
	 */
	public static function prefixFromEntity( string $entity ): string {
		if ( ! array_key_exists( $entity, self::$entities ) ) {
			throw new \InvalidArgumentException( "Invalid entity: {$entity}" );
		}

		return self::$entities[ $entity ];
	}

	/**
	 * @param string $prefix
	 */
	public function setPrefix( string $prefix ): void {
		if ( ! in_array( $prefix, self::$entities ) ) {
			throw new \InvalidArgumentException( "Invalid prefix: {$prefix}" );
		}
		$this->prefix = $prefix;
	}

	/**
	 * @return string
	 */
	public function getPrefix(): string {
		return $this->prefix;
	}

	/**
	 * @param string $uuid
	 */
	public function setUuid( string $uuid ): void {
		if ( strlen( $uuid ) !== self::$uuidLength ) {
			throw new \InvalidArgumentException( 'Invalid UUID length. Expecting 36.' );
		}
		$this->uuid = $uuid;
	}

	/**
	 * @return string
	 */
	public function getUuid(): string {
		return $this->uuid;
	}

	/**
	 * @param string $uuid
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function parsePrefixedUuid( string $uuid ) {
		$parts  = explode( '-', $uuid );
		$prefix = array_shift( $parts );
		if ( ! strlen( $prefix ) ) {
			throw new \InvalidArgumentException( 'Provided UUID is incorrect. Prefix is not included.' );
		}
		$uuid = implode( '-', $parts );
		$this->setPrefix( $prefix );
		$this->setUuid( $uuid );
	}

	public function toString() {
		return "{$this->prefix}-{$this->uuid}";
	}
}
