<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Vault\Test\Unit\Model\Method;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerPoolInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\Method\Vault;
use Magento\Vault\Model\VaultPaymentInterface;

/**
 * Class VaultTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VaultTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function setUp()
    {
        $this->objectManager = new ObjectManager($this);
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessage Not implemented
     */
    public function testAuthorizeNotOrderPayment()
    {
        $paymentModel = $this->getMock(InfoInterface::class);

        /** @var Vault $model */
        $model = $this->objectManager->getObject(Vault::class);
        $model->authorize($paymentModel, 0);
    }

    /**
     * @param array $additionalInfo
     * @expectedException \LogicException
     * @expectedExceptionMessage Public hash should be defined
     * @dataProvider additionalInfoDataProvider
     */
    public function testAuthorizeNoTokenMetadata(array $additionalInfo)
    {
        $paymentModel = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentModel->expects(static::once())
            ->method('getAdditionalInformation')
            ->willReturn($additionalInfo);

        /** @var Vault $model */
        $model = $this->objectManager->getObject(Vault::class);
        $model->authorize($paymentModel, 0);
    }

    /**
     * Get list of additional information variations
     * @return array
     */
    public function additionalInfoDataProvider()
    {
        return [
            ['additionalInfo' => []],
            ['additionalInfo' => ['customer_id' => 1]],
            ['additionalInfo' => ['public_hash' => null]],
        ];
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No token found
     */
    public function testAuthorizeNoToken()
    {
        $customerId = 1;
        $publicHash = 'token_public_hash';

        $paymentModel = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenManagement = $this->getMock(PaymentTokenManagementInterface::class);

        $paymentModel->expects(static::once())
            ->method('getAdditionalInformation')
            ->willReturn(
                [
                    PaymentTokenInterface::CUSTOMER_ID => $customerId,
                    PaymentTokenInterface::PUBLIC_HASH => $publicHash
                ]
            );
        $tokenManagement->expects(static::once())
            ->method('getByPublicHash')
            ->with($publicHash, $customerId)
            ->willReturn(null);

        /** @var Vault $model */
        $model = $this->objectManager->getObject(
            Vault::class,
            [
                'tokenManagement' => $tokenManagement
            ]
        );
        $model->authorize($paymentModel, 0);
    }

    public function testAuthorize()
    {
        $customerId = 1;
        $publicHash = 'token_public_hash';
        $vaultProviderCode = 'vault_provider_code';
        $amount = 10.01;

        $paymentModel = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $extensionAttributes = $this->getMockBuilder(OrderPaymentExtensionInterface::class)
            ->setMethods(['setVaultPaymentToken'])
            ->getMock();

        $commandManagerPool = $this->getMock(CommandManagerPoolInterface::class);
        $commandManager = $this->getMock(CommandManagerInterface::class);

        $vaultProvider = $this->getMock(MethodInterface::class);

        $tokenManagement = $this->getMock(PaymentTokenManagementInterface::class);
        $token = $this->getMock(PaymentTokenInterface::class);

        $paymentModel->expects(static::once())
            ->method('getAdditionalInformation')
            ->willReturn(
                [
                    PaymentTokenInterface::CUSTOMER_ID => $customerId,
                    PaymentTokenInterface::PUBLIC_HASH => $publicHash
                ]
            );
        $tokenManagement->expects(static::once())
            ->method('getByPublicHash')
            ->with($publicHash, $customerId)
            ->willReturn($token);
        $paymentModel->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $extensionAttributes->expects(static::once())
            ->method('setVaultPaymentToken')
            ->with($token);

        $vaultProvider->expects(static::atLeastOnce())
            ->method('getCode')
            ->willReturn($vaultProviderCode);
        $commandManagerPool->expects(static::once())
            ->method('get')
            ->with($vaultProviderCode)
            ->willReturn($commandManager);
        $commandManager->expects(static::once())
            ->method('executeByCode')
            ->with(
                VaultPaymentInterface::VAULT_AUTHORIZE_COMMAND,
                $paymentModel,
                [
                    'amount' => $amount
                ]
            );

        $paymentModel->expects(static::once())
            ->method('setMethod')
            ->with($vaultProviderCode);

        /** @var Vault $model */
        $model = $this->objectManager->getObject(
            Vault::class,
            [
                'tokenManagement' => $tokenManagement,
                'commandManagerPool' => $commandManagerPool,
                'vaultProvider' => $vaultProvider
            ]
        );
        $model->authorize($paymentModel, $amount);
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessage Not implemented
     */
    public function testCaptureNotOrderPayment()
    {
        $paymentModel = $this->getMock(InfoInterface::class);

        /** @var Vault $model */
        $model = $this->objectManager->getObject(Vault::class);
        $model->capture($paymentModel, 0);
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessage Capture can not be performed through vault
     */
    public function testCapture()
    {
        $paymentModel = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $authorizationTransaction = $this->getMock(TransactionInterface::class);
        $paymentModel->expects(static::once())
            ->method('getAuthorizationTransaction')
            ->willReturn($authorizationTransaction);

        /** @var Vault $model */
        $model = $this->objectManager->getObject(Vault::class);
        $model->capture($paymentModel, 0);
    }

    /**
     * @covers \Magento\Vault\Model\Method\Vault::isAvailable
     * @dataProvider isAvailableDataProvider
     */
    public function testIsAvailable($isAvailableProvider, $isActive, $expected)
    {
        $storeId = 1;
        $quote = $this->getMockForAbstractClass(CartInterface::class);
        $vaultProvider = $this->getMockForAbstractClass(MethodInterface::class);
        $config = $this->getMockForAbstractClass(ConfigInterface::class);

        $vaultProvider->expects(static::once())
            ->method('isAvailable')
            ->with($quote)
            ->willReturn($isAvailableProvider);

        $config->expects(static::any())
            ->method('getValue')
            ->with('active', $storeId)
            ->willReturn($isActive);

        $quote->expects(static::any())
            ->method('getStoreId')
            ->willReturn($storeId);

        /** @var Vault $model */
        $model = $this->objectManager->getObject(Vault::class, [
            'config' => $config,
            'vaultProvider' => $vaultProvider
        ]);
        $actual = $model->isAvailable($quote);
        static::assertEquals($expected, $actual);
    }

    /**
     * List of variations for testing isAvailable method
     * @return array
     */
    public function isAvailableDataProvider()
    {
        return [
            ['isAvailableProvider' => true, 'isActiveVault' => false, 'expected' => false],
            ['isAvailableProvider' => false, 'isActiveVault' => false, 'expected' => false],
            ['isAvailableProvider' => false, 'isActiveVault' => true, 'expected' => false],
            ['isAvailableProvider' => true, 'isActiveVault' => true, 'expected' => true],
        ];
    }

    /**
     * @covers \Magento\Vault\Model\Method\Vault::isAvailable
     */
    public function testIsAvailableWithoutQuote()
    {
        $quote = null;

        $vaultProvider = $this->getMockForAbstractClass(MethodInterface::class);
        $config = $this->getMockForAbstractClass(ConfigInterface::class);

        $vaultProvider->expects(static::once())
            ->method('isAvailable')
            ->with($quote)
            ->willReturn(true);

        $config->expects(static::once())
            ->method('getValue')
            ->with('active', $quote)
            ->willReturn(false);

        /** @var Vault $model */
        $model = $this->objectManager->getObject(Vault::class, [
            'config' => $config,
            'vaultProvider' => $vaultProvider
        ]);
        static::assertFalse($model->isAvailable($quote));
    }
}
