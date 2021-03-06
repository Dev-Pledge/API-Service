<?php

namespace DevPledge\Application\CommandHandlers;

use DevPledge\Application\Commands\CreatePledgeCommand;
use DevPledge\Domain\CommandPermissionException;
use DevPledge\Domain\Fetcher\FetchProblem;
use DevPledge\Domain\InvalidArgumentException;
use DevPledge\Framework\ServiceProviders\PledgeServiceProvider;
use DevPledge\Integrations\Command\AbstractCommandHandler;


/**
 * Class CreatePledgeHandler
 * @package DevPledge\Application\CommandHandlers
 */
class CreatePledgeHandler extends AbstractCommandHandler {
	/**
	 * CreateProblemHandler constructor.
	 */
	public function __construct() {
		parent::__construct( CreatePledgeCommand::class );
	}

	/**
	 * @param $command CreatePledgeCommand
	 *
	 * @throws \Exception
	 * @return \DevPledge\Domain\Pledge
	 */
	protected function handle( $command ) {

		$data             = $command->getData();
		$data->user_id    = $command->getUser()->getId();
		$data->problem_id = $command->getProblemId();
		$pledgeService    = PledgeServiceProvider::getService();

		if ( ! ( isset( $data->problem_id ) && is_string( $data->problem_id ) ) ) {
			throw new InvalidArgumentException( 'Problem ID is Required', 'problem_id' );
		}
		$problem = new FetchProblem( $data->problem_id );
		if ( ! $problem->isPersistedDataFound() ) {
			throw new InvalidArgumentException( 'Problem ID is not Valid', 'problem_id' );
		}

		if ( ! ( isset( $data->value ) && is_numeric( $data->value ) && $data->value > 0 ) ) {
			throw new InvalidArgumentException( 'Please give your Pledge with a value greater that 0.00', 'value' );
		}


		$checkFields = [ 'comment' ];

		foreach ( $checkFields as $field ) {
			if ( ! ( isset( $data->{$field} ) && strlen( $data->{$field} ) > 3 ) ) {
				throw new InvalidArgumentException( 'Please ensure you have completed ' . $field, $field );
			}
		}


		$currencies = [ 'GBP', 'USD', 'EUR' ];
		if ( ! ( isset( $data->currency ) && in_array( $data->currency, $currencies ) ) ) {
			throw new InvalidArgumentException( 'Please ensure you use ' . join( ' ', $currencies ) . ' as currency', 'currency' );
		}
		$unSets = [ 'payment_gateway', 'payment_reference', 'solution_id' ];
		foreach ( $unSets as $unset ) {
			if ( isset( $data->{$unset} ) ) {
				unset( $data->{$unset} );
			}
		}

		if ( isset( $data->organisation_id ) ) {
			CommandPermissionException::tryOrganisationPermission( $command->getUser(), $data->organisation_id, 'create' );
			$data->user_id = null;
		}


		return $pledgeService->create(
			$data
		);
	}
}