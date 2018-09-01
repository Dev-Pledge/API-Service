<?php

namespace DevPledge\Domain;


class Comment extends AbstractDomain {
	/**
	 * @var UserDefinedContent
	 */
	protected $comment;
	/**
	 * @var string
	 */
	protected $entityId;
	/**
	 * @var string | null
	 */
	protected $userId;
	/**
	 * @var string | null
	 */
	protected $organisationId;
	/**
	 * @return \stdClass
	 */
	function toPersistMap(): \stdClass {
		return (object) [
			'comment_id'      => $this->getId(),
			'comment'         => $this->getComment()->getContent(),
			'user_id'         => $this->getUserId(),
			'organisation_id' => $this->getOrganisationId(),
			'entity_id'       => $this->getEntityId(),
			'modified'        => $this->getModified()->format( 'Y-m-d H:i:s' ),
			'created'         => $this->getCreated()->format( 'Y-m-d H:i:s' )
		];
	}

	/**
	 * @param UserDefinedContent $comment
	 *
	 * @return Comment
	 */
	public function setComment( UserDefinedContent $comment ): Comment {
		$this->comment = $comment;

		return $this;
	}

	/**
	 * @return UserDefinedContent
	 */
	public function getComment(): UserDefinedContent {
		return $this->comment;
	}

	/**
	 * @param string $entityId
	 *
	 * @return Comment
	 */
	public function setEntityId( string $entityId ): Comment {
		$this->entityId = $entityId;

		return $this;
}

	/**
	 * @return string
	 */
	public function getEntityId(): string {
		return $this->entityId;
	}

	/**
	 * @param null|string $userId
	 *
	 * @return Comment
	 */
	public function setUserId( ?string $userId ): Comment {
		$this->userId = $userId;

		return $this;
}

	/**
	 * @return null|string
	 */
	public function getUserId(): ?string {
		return $this->userId;
	}

	/**
	 * @param null|string $organisationId
	 *
	 * @return Comment
	 */
	public function setOrganisationId( ?string $organisationId ): Comment {
		$this->organisationId = $organisationId;

		return $this;
}

	/**
	 * @return null|string
	 */
	public function getOrganisationId(): ?string {
		return $this->organisationId;
	}
}