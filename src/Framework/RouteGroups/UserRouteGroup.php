<?php

namespace DevPledge\Framework\RouteGroups;


use DevPledge\Domain\Pledge;
use DevPledge\Domain\TokenString;
use DevPledge\Domain\User;
use DevPledge\Framework\Controller\Auth\PayController;

use DevPledge\Framework\Controller\Pledge\PledgeController;
use DevPledge\Framework\Controller\User\UserCreateController;
use DevPledge\Framework\Controller\User\UserUpdateController;
use DevPledge\Framework\Middleware\OriginPermission;
use DevPledge\Framework\Middleware\UserPermission;

use DevPledge\Integrations\Route\AbstractRouteGroup;
use DevPledge\Integrations\ServiceProvider\Services\JWTServiceProvider;

/**
 * Class UserRouteGroup
 * @package DevPledge\Framework\RouteGroups
 */
class UserRouteGroup extends AbstractRouteGroup {

	public function __construct() {
		parent::__construct( '/user', [ new OriginPermission() ] );
	}


	protected function callableInGroup() {

		$userCreatedExampleResponse = function () {
			static $token;
			$user = User::getExampleInstance();
			if ( ! isset( $token ) ) {
				$token = new TokenString( $user, JWTServiceProvider::getService() );
			}

			return (object) [
				'user_id'  => $user->getId(),
				'username' => $user->getUsername(),
				'token'    => $token->getTokenString()
			];
		};
		$userUpdatedExampleResponse = function () {
			static $token;
			$user = User::getExampleInstance();
			if ( ! isset( $token ) ) {
				$token = new TokenString( $user, JWTServiceProvider::getService() );
			}

			return (object) [
				'user_id'      => $user->getId(),
				'username'     => $user->getUsername(),
				'updated_user' => $user->toAPIMap(),
				'token'        => $token->getTokenString()
			];
		};
		$this->post(
			'/createFromEmailPassword',
			UserCreateController::class . ':createUserFromEmailPassword', function () {
			return (object) [
				'email'    => User::getExampleInstance()->getEmail(),
				'password' => 'MyPrettyBloodyAmazing!Password',
				'username' => User::getExampleInstance()->getUsername()
			];
		}, $userCreatedExampleResponse
		);
		$this->post(
			'/createFromGitHub',
			UserCreateController::class . ':createUserFromGitHub', function () {
			return (object) [
				'code'     => '0987ygb2n3edieowkms23wqss2',
				'state'    => 'bd7892hdbkn1212asdasd',
				'username' => User::getExampleInstance()->getUsername()
			];
		}, $userCreatedExampleResponse
		);
		$this->post(
			'/checkUsernameAvailability',
			UserCreateController::class . ':checkUsernameAvailability'
		);
		$this->post(
			'/updatePassword/{user_id}',
			UserUpdateController::class . ':updatePassword', function () {
			return (object) [
				'old_password' => 'myveryoldpassWord!',
				'new_password' => 'MyVeryNewPassword33!z00'
			];
		},
			$userUpdatedExampleResponse,
			new UserPermission()
		);
		$this->post(
			'/updateGitHub/{user_id}',
			UserUpdateController::class . ':updateGithub', function () {
			return (object) [
				'code'  => '098ygbn23ebrhduiksmndmc',
				'state' => 'dihndfjndhjdsms0987',
			];
		}, $userUpdatedExampleResponse, new UserPermission()
		);
		$this->patch(
			'/{user_id}',
			UserUpdateController::class . ':update', User::getExampleRequest(), $userUpdatedExampleResponse, new UserPermission()
		);
		$this->post(
			'/createStripePaymentMethod/{user_id}',
			PayController::class . ':createUserStripePaymentMethod'
			, null, null, new UserPermission() );

		$this->get(
			'/paymentMethods/{user_id}',
			PayController::class . ':getUserPaymentMethods', null, new UserPermission()
		);

		$this->get(
			'/pledges/{user_id}',
			PledgeController::class . ':getUserPledges', null, new UserPermission()
		);


	}
}