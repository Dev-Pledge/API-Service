<?php

namespace DevPledge\Application\Service;

use DevPayment\Domain\Payments;
use DevPledge\Application\Factory\PaymentFactory;
use DevPledge\Application\Repository\PaymentRepository;
use DevPledge\Domain\AbstractDomain;
use DevPledge\Domain\CurrencyValue;
use DevPledge\Domain\Organisation;
use DevPledge\Domain\Payment;
use DevPledge\Domain\PaymentException;
use DevPledge\Domain\PaymentMethod;
use DevPledge\Domain\User;
use DevPledge\Framework\Adapter\Wheres;
use DevPledge\Integrations\Sentry;
use Omnipay\Common\AbstractGateway;
use Omnipay\Common\Message\ResponseInterface;
use Omnipay\Stripe\Gateway;

/**
 * Class PaymentService
 * @package DevPledge\Application\Service
 */
class PaymentService {
	/**
	 * @var Gateway
	 */
	protected $gateway;
	/**
	 * @var PaymentRepository
	 */
	protected $repo;
	/**
	 * @var PaymentFactory
	 */
	protected $factory;
	/**
	 * @var paymentMethodService
	 */
	protected $paymentMethodService;

	/**
	 * PaymentService constructor.
	 *
	 * @param PaymentRepository $repo
	 * @param PaymentFactory $factory
	 * @param PaymentMethodService $paymentMethodService
	 * @param AbstractGateway $gateway
	 */
	public function __construct( PaymentRepository $repo, PaymentFactory $factory, PaymentMethodService $paymentMethodService, AbstractGateway $gateway ) {
		$this->repo                 = $repo;
		$this->factory              = $factory;
		$this->gateway              = $gateway;
		$this->paymentMethodService = $paymentMethodService;
	}

	/**
	 * @param \stdClass $data
	 *
	 * @return Payment
	 * @throws \Exception
	 */
	public function create( \stdClass $data ): Payment {

		$payment = $this->factory->create( $data );

		$payment = $this->repo->createPersist( $payment );

		return $payment;
	}

	/**
	 * @param Payment $payment
	 * @param \stdClass $rawUpdateData
	 *
	 * @return Payment
	 * @throws \DevPledge\Application\Factory\FactoryException
	 */
	public function update( Payment $payment, \stdClass $rawUpdateData ): Payment {
		$payment = $this->factory->update( $payment, $rawUpdateData );

		return $this->repo->update( $payment );
	}

	/**
	 * @param string $paymentId
	 *
	 * @return Payment
	 */
	public function read( string $paymentId ): Payment {
		return $this->repo->read( $paymentId );
	}

	/**
	 * @param string $paymentId
	 *
	 * @return int|null
	 */
	public function delete( string $paymentId ): ?int {
		return $this->repo->delete( $paymentId );
	}

	/**
	 * @param string $userId
	 *
	 * @return Payments
	 * @throws \Exception
	 */
	public function getUserPayments( string $userId ): Payments {
		$payments = $this->repo->readAll( $userId, 'created' );
		if ( $payments ) {
			return new Payments( $payments );
		}

		return new Payments( [] );
	}

	/**
	 * @param string $organisationId
	 *
	 * @return Payments
	 * @throws \Exception
	 */
	public function getOrganisationPayments( string $organisationId ): Payments {
		$payments = $this->repo->readAllWhere( new Wheres( [ new Where( 'organisation_id', $organisationId ) ] ), 'created' );
		if ( $payments ) {
			return new Payments( $payments );
		}

		return new Payments( [] );
	}

	/**
	 * @param ResponseInterface $response
	 * @param \Closure|null $successfulFunction
	 * @param CurrencyValue|null $createPayment
	 *
	 * @return PaymentMethod | Payment | null
	 * @throws PaymentException
	 */
	protected function handleGatewayResponse( ResponseInterface $response, ?\Closure $successfulFunction = null, ?CurrencyValue $createPayment = null ): ?AbstractDomain {
		$payment = null;
		if ( $response->isRedirect() ) {
			throw new PaymentException( $response->getMessage(), $response->getRedirectUrl() );
		} elseif ( $response->isSuccessful() ) {
			if ( isset( $successfulFunction ) ) {

				if ( isset( $createPayment ) ) {
					$payment = $this->create( (object) [
						'gateway'   => $this->gateway->getShortName(),
						'reference' => $response->getTransactionReference(),
						'data'      => json_encode( $response->getData() ),
						'value'     => $createPayment->getValue(),
						'currency'  => $createPayment->getCurrency()
					] );
				}

				return call_user_func_array( $successfulFunction, [ $response, $payment ] );

			}
		} else {
			throw new PaymentException( $response->getMessage() );
		}

		return $payment;
	}

	/**
	 * @param int $cardNumber
	 * @param int $expiryMonth
	 * @param int $expiryYear
	 * @param int $cvv
	 * @param CurrencyValue $currencyValue
	 * @param \Closure|null $successFunction
	 *
	 * @return bool
	 * @throws PaymentException
	 */
	public function cardPay( int $cardNumber, int $expiryMonth, int $expiryYear, int $cvv, CurrencyValue $currencyValue, ?\Closure $successFunction = null ): bool {
		$formData = [
			'number'      => $cardNumber,
			'expiryMonth' => $expiryMonth,
			'expiryYear'  => $expiryYear,
			'cvv'         => $cvv
		];
		try {
			return $this->handleGatewayResponse(
				$this->gateway->purchase(
					[
						'amount'   => $currencyValue->getValue(),
						'currency' => $currencyValue->getCurrency(),
						'card'     => $formData
					]
				)->send(),
				$successFunction,
				$currencyValue
			);
		} catch ( \TypeError  | \Exception $exception ) {
			Sentry::get()->captureException( $exception );
			throw new PaymentException( 'Data being used is incorrect!' );
		}
	}

	/**
	 * @param string $token
	 * @param CurrencyValue $currencyValue
	 * @param \Closure|null $successFunction
	 *
	 * @return Payment | null
	 * @throws PaymentException
	 */
	public function stripePayWithToken( string $token, CurrencyValue $currencyValue, ?\Closure $successFunction = null ): ?Payment {

		try {
			return $this->handleGatewayResponse(
				$this->gateway->purchase( [
					'amount'   => $currencyValue->getValue(),
					'currency' => $currencyValue->getCurrency(),
					'token'    => $token,
				] )->send(), $successFunction, $currencyValue );
		} catch ( \TypeError  | \Exception  $exception ) {
			Sentry::get()->captureException( $exception );
			throw new PaymentException( 'Data being used is incorrect!' );
		}
	}

	/**
	 * @param AbstractDomain|User|Organisation $domain
	 * @param string $token
	 * @param string $name
	 *
	 * @return PaymentMethod
	 * @throws PaymentException
	 */
	public function createPaymentMethodFromStripeToken( AbstractDomain $domain, string $token, string $name = 'default card' ): PaymentMethod {
		return $this->createPaymentMethod( $name, $domain, [ 'token' => $token ], $name );
	}

	/**
	 * @param AbstractDomain|User|Organisation $domain
	 * @param array $createCardParameters
	 * @param string $name
	 *
	 * @return PaymentMethod
	 * @throws PaymentException
	 */
	public function createPaymentMethod( AbstractDomain $domain, array $createCardParameters = [], string $name = 'default card' ): PaymentMethod {

		if ( ! ( ( $domain instanceof User ) || ( $domain instanceof Organisation ) ) ) {
			throw new PaymentException( 'No User or Organisation Specified' );
		}

		return $this->handleGatewayResponse(
			$this->gateway->createCard( $createCardParameters )->send(),
			function ( ResponseInterface $response ) use ( $domain, $name ) {
				//$cardReference = $response->getCardReference();
				$dataArray = [
					'gateway' => $this->gateway->getShortName(),
					'data'    => \json_encode( $response->getData() ),
					'name'    => $name
				];
				switch ( $domain->getUuid()->getEntity() ) {
					case 'user':
						$dataArray['user_id'] = $domain->getId();
						break;
					case 'organisation':
						$dataArray['organisation_id'] = $domain->getId();
						break;
				}

				return $this->paymentMethodService->create( (object) $dataArray );
			}
		);
	}

	/**
	 * @param string $paymentMethodId
	 * @param CurrencyValue $currencyValue
	 * @param \Closure|null $successFunction
	 *
	 * @return Payment|null
	 * @throws PaymentException
	 */
	public function payWithStripePaymentMethod( string $paymentMethodId, CurrencyValue $currencyValue, ?\Closure $successFunction = null ): ?Payment {

		$paymentMethod = $this->paymentMethodService->read( $paymentMethodId );

		return $this->handleGatewayResponse(
			$this->gateway->purchase( [
				'amount'        => $currencyValue->getValue(),
				'currency'      => $currencyValue->getCurrency(),
				'cardReference' => $paymentMethod->getCardReference(),
				//		'customerReference'=> $paymentMethod->getCardReference(),
			] ),
			$successFunction,
			$currencyValue
		);

	}


}