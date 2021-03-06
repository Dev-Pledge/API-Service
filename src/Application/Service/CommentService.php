<?php

namespace DevPledge\Application\Service;


use DevPledge\Application\Events\CreatedCommentEvent;
use DevPledge\Application\Events\CreatedDomainEvent;
use DevPledge\Application\Factory\CommentFactory;
use DevPledge\Application\Repository\CommentRepository;
use DevPledge\Application\Repository\SubCommentRepository;
use DevPledge\Domain\AbstractDomain;
use DevPledge\Domain\Comment;
use DevPledge\Domain\Comments;
use DevPledge\Framework\Adapter\Where;
use DevPledge\Framework\Adapter\WhereNot;
use DevPledge\Framework\Adapter\WhereNull;
use DevPledge\Framework\Adapter\Wheres;
use DevPledge\Integrations\Cache\Cache;
use DevPledge\Integrations\Command\Dispatch;
use DevPledge\Uuid\Uuid;

/**
 * Class CommentService
 * @package DevPledge\Application\Service
 */
class CommentService {
	/**
	 * @var CommentRepository
	 */
	protected $repo;
	/**
	 * @var SubCommentRepository
	 */
	protected $subRepo;
	/**
	 * @var CommentFactory
	 */
	protected $factory;
	/**
	 * @var Cache
	 */
	protected $cacheService;
	/**
	 * @var EntityService
	 */
	protected $entityService;

	/**
	 * CommentService constructor.
	 *
	 * @param CommentRepository $repo
	 * @param SubCommentRepository $subRepo
	 * @param CommentFactory $factory
	 * @param Cache $cacheService
	 * @param EntityService $entityService
	 */
	public function __construct( CommentRepository $repo, SubCommentRepository $subRepo, CommentFactory $factory, Cache $cacheService, EntityService $entityService ) {
		$this->repo          = $repo;
		$this->factory       = $factory;
		$this->cacheService  = $cacheService;
		$this->entityService = $entityService;
		$this->subRepo       = $subRepo;
	}

	/**
	 * @param \stdClass $data
	 *
	 * @return Comment
	 * @throws \Exception
	 */
	public function create( \stdClass $data ): Comment {
		/**
		 * @var $comment Comment
		 */
		$comment = $this->factory->create( $data );

		$comment = $this->repo->createPersist( $comment );

		Dispatch::event( new CreatedDomainEvent( $comment, $comment->getEntityId() ) );

		return $comment;
	}

	/**
	 * @param Comment $comment
	 *
	 * @return AbstractDomain | null
	 */
	public function getCommentEntity( Comment $comment ): ?AbstractDomain {
		$domain = $this->entityService->read( $comment->getEntityId(), [
			'user',
			'pledge',
			'solution',
			'problem',
			'comment',
			'status'
		] );

		if ( $domain instanceof AbstractDomain ) {
			return $domain;
		}

		return null;
	}

	/**
	 * @param Comment $comment
	 * @param \stdClass $rawUpdateData
	 *
	 * @return Comment
	 * @throws \DevPledge\Application\Factory\FactoryException
	 */
	public function update( Comment $comment, \stdClass $rawUpdateData ): Comment {
		$comment = $this->factory->update( $comment, $rawUpdateData );

		$comment = $this->repo->update( $comment );

		Dispatch::event( new UpdatedDomainEvent( $comment, $comment->getEntityId() ) );

		return $comment;
	}

	/**
	 * @param string $commentId
	 *
	 * @return Comment
	 */
	public function read( string $commentId ): Comment {

		return $this->repo->read( $commentId );
	}

	/**
	 * @param string $commentId
	 *
	 * @return int|null
	 */
	public function delete( string $commentId ): ?int {
		$comment = $this->read( $commentId );
		$deleted = $this->repo->delete( $commentId );
		if ( $deleted ) {
			Dispatch::event( new DeletedDomainEvent( $comment, $comment->getEntityId() ) );
		}

		return $deleted;
	}

	/**
	 * @param string $entityId
	 *
	 * @return Comments
	 * @throws \DevPledge\Integrations\Cache\CacheException
	 */
	public function readAll( string $entityId ): Comments {

		$key                    = $this->getAllCommentsKey( $entityId );
		$allCacheEntityComments = $this->cacheService->get( $key );

		if ( $allCacheEntityComments ) {
			return unserialize( $allCacheEntityComments );
		}
		$comments = $this->subRepo->readAll( $entityId, 'created' );

		if ( $comments ) {
			$allEntityComments = new Comments( $comments );
			$this->cacheService->setEx( $key, serialize( $allEntityComments ), 300 );

			return $allEntityComments;
		}

		return new Comments( [] );

	}

	/**
	 * @param string $entityId
	 * @param int $page
	 *
	 * @return Comments
	 * @throws \Exception
	 */
	public function readCommentsPage( string $entityId, int $page = 1 ): Comments {
		$page     = $page - 1;
		$offset   = $page * 5;
		$comments = $this->repo->readAllWhere( $this->entityWheres( $entityId ), 'created', true, 5, $offset );
		if ( $comments ) {
			return new Comments( array_reverse( $comments ) );
		}

		return new Comments( [] );
	}

	/**
	 * @param string $commentId
	 * @param int $page
	 *
	 * @return Comments
	 * @throws \Exception
	 */
	public function readRepliesPage( string $commentId, int $page = 1 ): Comments {
		$page     = $page - 1;
		$offset   = $page * 5;
		$comments = $this->repo->readAllWhere( new Wheres( [ new Where( 'parent_comment_id', $commentId ) ] ), 'created', true, 5, $offset );
		if ( $comments ) {
			return new Comments( array_reverse( $comments ) );
		}

		return new Comments( [] );
	}

	/**
	 * @param string $entityId
	 *
	 * @return string
	 */
	public function getAllCommentsKey( string $entityId ): string {
		return 'all-cmt:' . $entityId;
	}

	/**
	 * @param string $entityId
	 *
	 * @return Comments
	 * @throws \DevPledge\Integrations\Cache\CacheException
	 */
	public function readLastFiveComments( string $entityId ): Comments {
		$key                    = $this->getLastFiveCommentKey( $entityId );
		$allCacheEntityComments = $this->cacheService->get( $key );

		if ( $allCacheEntityComments ) {
			return unserialize( $allCacheEntityComments );
		}

		$comments = $this->subRepo->readAllWhere( $this->entityWheres( $entityId ), 'created', true, 5 );

		if ( $comments ) {
			$allEntityComments = new Comments( array_reverse( $comments ) );
			$this->cacheService->setEx( $key, serialize( $allEntityComments ), 10 );

			return $allEntityComments;
		}

		return new Comments( [] );
	}

	/**
	 * @param string $entityId
	 *
	 * @return Wheres
	 * @throws \Exception
	 */
	protected function entityWheres( string $entityId ): Wheres {
		return new Wheres( [
			new Where( 'entity_id', $entityId ),
			( new WhereNot( 'user_id', 'entity_id' ) )->setValueAsColumn(),
			new WhereNull( 'parent_comment_id' )
		] );
	}

	/**
	 * @param string $entityId
	 *
	 * @return string
	 */
	public function getLastFiveCommentKey( string $entityId ): string {
		return 'l5-cmt:' . $entityId;
	}

	/**
	 * @param string $commentId
	 *
	 * @return Comments
	 * @throws \DevPledge\Integrations\Cache\CacheException
	 */
	public function readLastFiveReplies( string $commentId ): Comments {
		$key                    = $this->getLastFiveReplyKey( $commentId );
		$allCacheEntityComments = $this->cacheService->get( $key );

		if ( $allCacheEntityComments ) {
			return unserialize( $allCacheEntityComments );
		}
		$comments = $this->subRepo->readAllWhere( new Wheres( [ new Where( 'parent_comment_id', $commentId ) ] ), 'created', true, 5 );
		if ( $comments ) {

			$allEntityComments = new Comments( array_reverse( $comments ) );
			$this->cacheService->setEx( $key, serialize( $allEntityComments ), 300 );

			return $allEntityComments;
		}

		return new Comments( [] );
	}


	/**
	 * @param string $commentId
	 *
	 * @return string
	 */
	public function getLastFiveReplyKey( string $commentId ): string {
		return 'l5-rpl:' . $commentId;
	}

	/**
	 * @param string $commentId
	 *
	 * @return Comments
	 * @throws \DevPledge\Integrations\Cache\CacheException
	 */
	public function readAllReplies( string $commentId ): Comments {

		$key                    = $this->getAllRepliesKey( $commentId );
		$allCacheEntityComments = $this->cacheService->get( $key );

		if ( $allCacheEntityComments ) {
			return unserialize( $allCacheEntityComments );
		}
		$comments = $this->subRepo->readAllWhere(
			new Wheres( [ new Where( 'parent_comment_id', $commentId ) ] ),
			'created'
		);

		if ( $comments ) {
			$allEntityComments = new Comments( $comments );
			$this->cacheService->setEx( $key, serialize( $allEntityComments ), 300 );

			return $allEntityComments;
		}

		return new Comments( [] );

	}

	/**
	 * @param string $commentId
	 *
	 * @return string
	 */
	public function getAllRepliesKey( string $commentId ): string {
		return 'all-rpl:' . $commentId;
	}

	/**
	 * @param string $entityId
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function countComments( string $entityId ): int {

		return $this->repo->countAllWhere( $this->entityWheres( $entityId ) );

	}

	/**
	 * @param string $commentId
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function countReplies( string $commentId ): int {

		return $this->repo->countAllWhere( new Wheres( [ new Where( 'parent_comment_id', $commentId ) ] ) );

	}


	/**
	 * @param string $commentId
	 *
	 * @return Comments
	 * @throws \Exception
	 */
	public function getContextualComments( string $commentId ): Comments {
		$comment = $this->read( $commentId );
		if ( ( $parentCommentId = $comment->getParentCommentId() ) !== null ) {
			$comments   = $this->subRepo->readAllWhere(
				new Wheres( [
					new Where( 'parent_comment_id', $parentCommentId ),
					( new Where( 'created', $comment->getCreated()->format( 'Y-m-d H:i:s' ) ) )->lessThan()
				] ),
				'created',
				true,
				4
			);
			$comments   = array_reverse( $comments );
			$comments[] = $comment;

			return new Comments( $comments );
		} else if ( $comment->getEntityId() !== $comment->getUserId() ) {
			$comments   = $this->subRepo->readAllWhere(
				new Wheres( [
					new Where( 'entity_id', $comment->getEntityId() ),
					( new Where( 'created', $comment->getCreated()->format( 'Y-m-d H:i:s' ) ) )->lessThan()
				] ),
				'created',
				true,
				4
			);
			$comments   = array_reverse( $comments );
			$comments[] = $comment;

			return new Comments( $comments );
		}

		return new Comments( [ $comment ] );
	}


}