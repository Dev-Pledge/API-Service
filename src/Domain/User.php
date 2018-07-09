<?php

namespace DevPledge\Domain;

use DevPledge\Application\Mapper\Mappable;
use DevPledge\Domain\PreferredUserAuth\UsernameEmailPassword;
use DevPledge\Uuid\Uuid;


/**
 * Class User
 * @package DevPledge\Domain
 */
class User extends AbstractDomain {


	/**
	 * @var string | null
	 */
	private $email;
	/**
	 * @var int | null
	 */
	private $gitHubId;
	/**
	 * @var string | null
	 */
	private $hashedPassword;
	/**
	 * @var bool
	 */
	private $developer;
	/**
	 * @var string
	 */
	private $username;
	/**
	 * @var string
	 */
	private $name;


	/**
	 * @param string $email
	 *
	 * @return User
	 */
	public function setEmail( string $email ): User {
		$this->email = $email;

		return $this;
	}

	/**
	 * @param string $hashedPassword
	 *
	 * @return User
	 */
	public function setHashedPassword( string $hashedPassword ): User {
		$this->hashedPassword = $hashedPassword;

		return $this;
	}


	/**
	 * @return null|string
	 */
	public function getEmail(): ?string {
		return $this->email;
	}

	/**
	 * @return UsernameEmailPassword
	 */
	public function getEmailPasswordAuth() {
		return new UsernameEmailPassword( $this->getEmail(), null, $this->getHashedPassword() );
	}

	/**
	 * @return null|string
	 */
	public function getHashedPassword(): ?string {
		return $this->hashedPassword;
	}

	/**
	 * @param bool $developer
	 *
	 * @return User
	 */
	public function setDeveloper( bool $developer ): User {
		$this->developer = $developer;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isDeveloper(): bool {
		return isset( $this->developer ) ? $this->developer : false;
	}

	/**
	 * @param string $name
	 *
	 * @return User
	 */
	public function setName( string $name ): User {
		$this->name = $name;

		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return User
	 */
	public function setUsername( string $username ): User {
		$this->username = $username;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getUsername() {
		return $this->username;
	}


	/**
	 * @return int|null
	 */
	public function getGitHubId(): ?int {
		return $this->gitHubId;
	}

	/**
	 * @param int|null $gitHubId
	 *
	 * @return User
	 */
	public function setGitHubId( ?int $gitHubId ): User {
		$this->gitHubId = $gitHubId;

		return $this;
	}

	/**
	 * @return \stdClass
	 */
	function toMap(): \stdClass {

		return (object) [
			'user_id'         => $this->getId(),
			'name'            => $this->getName(),
			'email'           => $this->getEmail(),
			'data'            => $this->getData()->getJson(),
			'username'        => $this->getUsername(),
			'modified'        => $this->getModified()->format( 'Y-m-d H:i:s' ),
			'created'         => $this->getCreated()->format( 'Y-m-d H:i:s' ),
			'hashed_password' => $this->getHashedPassword()
		];
	}

}