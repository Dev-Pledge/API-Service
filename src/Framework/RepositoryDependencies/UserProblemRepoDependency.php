<?php


namespace DevPledge\Framework\RepositoryDependencies;


use DevPledge\Application\Repository\UserProblemRepository;
use DevPledge\Framework\Adapter\MysqlAdapter;
use DevPledge\Framework\FactoryDependencies\UserFactoryDependency;
use DevPledge\Integrations\RepositoryDependency\AbstractRepositoryDependency;
use DevPledge\Integrations\ServiceProvider\Services\ExtendedPDOServiceProvider;
use Slim\Container;

/**
 * Class UserProblemRepoDependency
 * @package DevPledge\Framework\RepositoryDependencies
 */
class UserProblemRepoDependency extends AbstractRepositoryDependency {

	public function __construct() {
		parent::__construct( UserProblemRepository::class );
	}

	/**
	 * @param Container $container
	 *
	 * @return mixed
	 */
	public function __invoke( Container $container ) {
		$factory = UserFactoryDependency::getFactory();
		$adaptor = new MysqlAdapter( ExtendedPDOServiceProvider::getService() );

		return new UserProblemRepository( $adaptor, $factory );
	}

	/**
	 * usually return static::getFromContainer();
	 * @return mixed
	 */
	static public function getRepository() {
		return static::getFromContainer();
	}
}