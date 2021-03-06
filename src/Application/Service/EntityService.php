<?php


namespace DevPledge\Application\Service;


use DevPledge\Application\Mapper\PersistMappable;
use DevPledge\Domain\CommentsTrait;
use DevPledge\Domain\FeedEntity;
use DevPledge\Domain\InvalidArgumentException;
use DevPledge\Framework\ServiceProviders\CommentServiceProvider;
use DevPledge\Framework\ServiceProviders\OrganisationServiceProvider;
use DevPledge\Framework\ServiceProviders\PaymentMethodServiceProvider;
use DevPledge\Framework\ServiceProviders\PaymentServiceProvider;
use DevPledge\Framework\ServiceProviders\PledgeServiceProvider;
use DevPledge\Framework\ServiceProviders\ProblemServiceProvider;
use DevPledge\Framework\ServiceProviders\SolutionServiceProvider;
use DevPledge\Framework\ServiceProviders\StatusCommentServiceProvider;
use DevPledge\Framework\ServiceProviders\TopicServiceProvider;
use DevPledge\Framework\ServiceProviders\UserServiceProvider;
use DevPledge\Integrations\Cache\Cache;
use DevPledge\Integrations\Sentry;
use DevPledge\Uuid\TopicUuid;
use DevPledge\Uuid\Uuid;

/**
 * Class EntityService
 * @package DevPledge\Application\Service
 */
class EntityService {
	/**
	 * @var Cache
	 */
	protected $cache;

	/**
	 * EntityService constructor.
	 *
	 * @param Cache $cache
	 */
	public function __construct( Cache $cache ) {
		$this->cache = $cache;
	}

	/**
	 * @param string $entityId
	 * @param array $allowedEntities
	 *
	 * @return PersistMappable
	 * @throws InvalidArgumentException
	 */
	public function read(
		string $entityId,
		$allowedEntities = [
			'user',
			'problem',
			'pledge',
			'solution',
			'comment',
			'status',
            'topic'
		]
	): PersistMappable {

		try {
			$uuid = new Uuid( $entityId );
		} catch ( \InvalidArgumentException $exception ) {
			$uuid = new TopicUuid( $entityId );
		} catch ( \Exception $exception ) {
			throw new InvalidArgumentException( 'Error Getting Entity Domain', 'entity_id' );
		}

		$entity = $uuid->getEntity();
		$domain = null;
		try {

			if ( ! in_array( $entity, $allowedEntities ) ) {
				throw new \Exception( 'Entity not Allowed:' . $entity );
			}
			$serializeKey = 'entity:' . $entityId;
			switch ( $entity ) {
				case 'user':
					$domain = UserServiceProvider::getService()->getUserFromCache( $entityId );
					break;
				case 'problem':
					if ( $serializedDomain = $this->cache->get( $serializeKey ) ) {
						$domain = unserialize( $serializedDomain );
					} else {
						$domain = ProblemServiceProvider::getService()->read( $entityId );
						$this->cache->setEx( $serializeKey, serialize( $domain ), 30 );
					}
					break;
				case 'topic':
					$domain = TopicServiceProvider::getService()->read( $entityId );
					break;
				case 'organisation':
					$domain = OrganisationServiceProvider::getService()->read( $entityId );
					break;
				case 'pledge':
					$domain = PledgeServiceProvider::getService()->read( $entityId );
					break;
				case 'solution':
					$domain = SolutionServiceProvider::getService()->read( $entityId );
					break;
				case 'payment':
					$domain = PaymentServiceProvider::getService()->read( $entityId );
					break;
				case 'payment_method':
					$domain = PaymentMethodServiceProvider::getService()->read( $entityId );
					break;
				case 'comment':
					$domain = CommentServiceProvider::getService()->read( $entityId );
					break;
				case 'status':
					$domain = StatusCommentServiceProvider::getService()->read( $entityId );
					break;
			}
			if ( $domain === null ) {
				throw new \Exception( 'Entity Not Found' );
			}
		} catch ( \Exception | \TypeError | \InvalidArgumentException $exception ) {
			throw new InvalidArgumentException( 'Error Getting ' . $entity . ' Entity Domain '.$exception->getMessage() , 'entity_id ' );
		}


		return $domain;
	}

	/**
	 * @param string $function
	 * @param string $entityId
	 * @param null|string $entityParentId
	 *
	 * @return FeedEntity|null
	 */
	public function getFeedEntity( string $function, string $entityId, ?string $entityParentId = null ): ?FeedEntity {
		try {
			$domain       = $this->read( $entityId );
			$parentDomain = null;
			if ( ! is_null( $entityParentId ) ) {
				$parentDomain = $this->read( $entityParentId );
			}

			return new FeedEntity( $function, $domain, $parentDomain );
		} catch ( \TypeError | InvalidArgumentException $exception ) {
			//entity not found - ignore
		}

		return null;
	}

	/**
	 * @param \stdClass $data
	 *
	 * @return \stdClass
	 */
	public function getFeedEntities( \stdClass $data ): \stdClass {
		$returnArray = [];
		if ( isset( $data->entities ) && is_array( $data->entities ) ) {
			foreach ( $data->entities as $entity ) {
				try {
					$function       = $entity['function'] ?? 'no-function';
					$entityId       = $entity['id'] ?? null;
					$entityParentId = $entity['parent_id'] ?? null;
					$feedEntity     = $this->getFeedEntity( $function, $entityId, $entityParentId );
					if ( ! is_null( $feedEntity ) ) {
						$returnArray[] = $feedEntity->toAPIMap();
					}
				} catch ( \Exception | \TypeError $exception ) {
					Sentry::get()->captureException( $exception );

				}
			}
		}

		$return           = new \stdClass();
		$return->entities = $returnArray;

		return $return;
	}


}