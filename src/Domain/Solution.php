<?php

namespace DevPledge\Domain;

use DevPledge\Integrations\Route\Example;
use DevPledge\Uuid\Uuid;

/**
 * Class Solution
 * @package DevPledge\Domain
 */
class Solution extends AbstractDomain implements Example {

	use CommentsTrait;
	/**
	 * @var string
	 */
	protected $solutionGroupId;
	/**
	 * @var string
	 */
	protected $problemSolutionGroupId;
	/**
	 * @var string
	 */
	protected $userId;
	/**
	 * @var string
	 */
	protected $problemId;
	/**
	 * @var string
	 */
	protected $name;
	/**
	 * @var string
	 */
	protected $openSourceLocation;
	/**
	 * @var User
	 */
	protected $user;

	/**
	 * @return \stdClass
	 */
	function toPersistMap(): \stdClass {
		return (object) [
			'solution_id'               => $this->getId(),
			'problem_solution_group_id' => $this->getSolutionGroupId(),
			'solution_group_id'         => $this->getProblemSolutionGroupId(),
			'user_id'                   => $this->getUserId(),
			'name'                      => $this->getName(),
			'problem_id'                => $this->getProblemId(),
			'open_source_location'      => $this->getOpenSourceLocation(),
			'modified'                  => $this->getModified()->format( 'Y-m-d H:i:s' ),
			'created'                   => $this->getCreated()->format( 'Y-m-d H:i:s' )
		];
	}

	function toAPIMapArray(): array {
		return parent::toAPIMapArray();
	}

	/**
	 * @return null|string
	 */
	public function getSolutionGroupId(): ?string {
		return $this->solutionGroupId;
	}

	/**
	 * @param string $solutionGroupId
	 *
	 * @return Solution
	 */
	public function setSolutionGroupId( string $solutionGroupId ): Solution {
		$this->solutionGroupId = $solutionGroupId;

		return $this;
	}

	/**
	 * @param string $problemSolutionGroupId
	 *
	 * @return Solution
	 */
	public function setProblemSolutionGroupId( string $problemSolutionGroupId ): Solution {
		$this->problemSolutionGroupId = $problemSolutionGroupId;

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getProblemSolutionGroupId(): ?string {
		return $this->problemSolutionGroupId;
	}

	/**
	 * @param string $userId
	 *
	 * @return Solution
	 */
	public function setUserId( string $userId ): Solution {
		$this->userId = $userId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUserId(): string {
		return $this->userId;
	}

	/**
	 * @param string $problemId
	 *
	 * @return Solution
	 */
	public function setProblemId( string $problemId ): Solution {
		$this->problemId = $problemId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getProblemId(): string {
		return $this->problemId;
	}

	/**
	 * @param string $openSourceLocation
	 *
	 * @return Solution
	 */
	public function setOpenSourceLocation( string $openSourceLocation ): Solution {
		$this->openSourceLocation = $openSourceLocation;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getOpenSourceLocation(): string {
		return $this->openSourceLocation;
	}


	/**
	 * @return User
	 */
	public function getUser(): User {
		return $this->user;
	}

	/**
	 * @param User $user
	 *
	 * @return Solution
	 */
	public function setUser( ?User $user ): Solution {
		$this->user = $user;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @param string $name
	 *
	 * @return Solution
	 */
	public function setName( string $name ): Solution {
		$this->name = $name;

		return $this;
	}

	public function toAPIMap(): \stdClass {
		$data       = parent::toAPIMap();
		$data->user = $this->getUser()->toPublicAPIMap();

		return $data;
	}

	/**
	 * @return \Closure|null
	 */
	public static function getExampleResponse(): ?\Closure {
		return function () {
			return static::getExampleInstance()->toAPIMap();
		};
	}

	/**
	 * @return \Closure|null
	 */
	public static function getExampleRequest(): ?\Closure {
		return function () {
			return (object) [
				'name'                 => 'My Super Solution name',
				'open_source_location' => 'https://mylinktogithub.com/mycoolsolution'
			];
		};
	}

	/**
	 * @return Solution
	 */
	public static function getExampleInstance() {
		static $example;
		if ( ! isset( $example ) ) {
			$example = new static( 'solution' );
			$example
				->setProblemId( Problem::getExampleInstance()->getId() )
				->setUserId( User::getExampleInstance()->getId() )
				->setName( 'My Super Solution name' )
				->setOpenSourceLocation( 'https://mylinktogithub.com/mycoolsolution' )
				->setUser( User::getExampleInstance() );
		}

		return $example;
	}
}