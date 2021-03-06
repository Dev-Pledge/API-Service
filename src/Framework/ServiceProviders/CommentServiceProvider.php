<?php

namespace DevPledge\Framework\ServiceProviders;


use DevPledge\Application\Service\CommentService;
use DevPledge\Framework\FactoryDependencies\CommentFactoryDependency;
use DevPledge\Framework\RepositoryDependencies\Comment\CommentRepositoryDependency;
use DevPledge\Framework\RepositoryDependencies\Comment\SubCommentRepoDependency;
use DevPledge\Integrations\ServiceProvider\AbstractServiceProvider;
use DevPledge\Integrations\ServiceProvider\Services\CacheServiceProvider;
use Slim\Container;

/**
 * Class CommentServiceProvider
 * @package DevPledge\Framework\ServiceProviders
 */
class CommentServiceProvider extends AbstractServiceProvider {
	/**
	 * CommentServiceProvider constructor.
	 */
	public function __construct() {
		parent::__construct( CommentService::class );
	}

	/**
	 * @param Container $container
	 *
	 * @return CommentService
	 */
	public function __invoke( Container $container ) {
		return new CommentService(
			CommentRepositoryDependency::getRepository(),
			SubCommentRepoDependency::getRepository(),
			CommentFactoryDependency::getFactory(),
			CacheServiceProvider::getService(),
			EntityServiceProvider::getService()
		);
	}

	/**
	 * usually return static::getFromContainer();
	 * @return CommentService
	 */
	static public function getService() {
		return static::getFromContainer();
	}
}