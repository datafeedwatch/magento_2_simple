<?php
/**
 * Created by Q-Solutions Studio
 * Date: 01.07.19
 *
 * @category    Dfw
 * @package     Dfw_Connector
 * @author      Maciej Buchert <maciej@qsolutionsstudio.com>
 */

namespace Dfw\Connector\Model\Api;

use Dfw\Connector\Helper\Data;
use Exception;
use Magento\Authorization\Model\Role;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\RulesFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\App\ConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Validator\DataObjectFactory;
use Magento\Integration\Model\AdminTokenService;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\Token\RequestThrottler;
use Magento\Integration\Model\ResourceModel\Oauth\Token\RequestLog;
use Magento\Store\Model\StoreManagerInterface;
use Magento\User\Model\User as MagentoUser;
use Magento\User\Model\UserValidationRules;

/**
 * Class User
 * @package Dfw\Connector\Model\Api
 */
class User extends MagentoUser
{
    const API_KEY_SHUFFLE_STRING = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const API_KEY_LENGTH         = 32;
    const USER_NAME              = 'datafeedwatch';
    const USER_FIRST_NAME        = 'Api Access';
    const USER_LAST_NAME         = 'DataFeedWatch';
    const USER_EMAIL             = 'magento@datafeedwatch.com';
    const USER_IS_ACTIVE         = 1;
    const ROLE_NAME              = 'DataFeedWatch';
    const ROLE_TYPE              = 'G';
    const ROLE_PID               = false;
    const RULE_PRIVILEGES        = '';
    const RULE_PERMISSION        = 'allow';

    /**
     * @var Data
     */
    public $dataHelper;

    /**
     * @var RulesFactory
     */
    public $rulesFactory;

    /**
     * @var string
     */
    private $decodedApiKey;

    /**
     * @var RequestLog
     */
    private $oauthToken;

    /**
     * @var AdminTokenService
     */
    private $adminTokenService;

    /**
     * @var Token
     */
    private $tokenModel;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * User constructor.
     * @param Context $context
     * @param Registry $registry
     * @param \Magento\User\Helper\Data $userData
     * @param ConfigInterface $config
     * @param RoleFactory $roleFactory
     * @param DataObjectFactory $validatorObjectFactory
     * @param RulesFactory $rulesFactory
     * @param TransportBuilder $transportBuilder
     * @param EncryptorInterface $encryptor
     * @param StoreManagerInterface $storeManager
     * @param UserValidationRules $validationRules
     * @param Data $dataHelper
     * @param RequestLog $oauthToken
     * @param Token $tokenModel
     * @param AdminTokenService $adminTokenService
     * @param Curl $curl
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        \Magento\User\Helper\Data $userData,
        ConfigInterface $config,
        RoleFactory $roleFactory,
        DataObjectFactory $validatorObjectFactory,
        RulesFactory $rulesFactory,
        TransportBuilder $transportBuilder,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        UserValidationRules $validationRules,
        Data $dataHelper,
        RequestLog $oauthToken,
        Token $tokenModel,
        AdminTokenService $adminTokenService,
        Curl $curl,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {

        $this->curl                 = $curl;
        $this->dataHelper           = $dataHelper;
        $this->rulesFactory         = $rulesFactory;
        $this->oauthToken           = $oauthToken;
        $this->adminTokenService    = $adminTokenService;
        $this->tokenModel           = $tokenModel;
        parent::__construct(
            $context,
            $registry,
            $userData,
            $config,
            $validatorObjectFactory,
            $roleFactory,
            $transportBuilder,
            $encryptor,
            $storeManager,
            $validationRules,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function createDfwUser()
    {
        $role = $this->createDfwUserRole();
        $this->generateApiKey();
        $this->addUserData();
        $this->setRoleId($role->getId());
        $this->save();

        $resource = [
            'Magento_Catalog::catalog',
            'Magento_Catalog::config_catalog',
            'Magento_Backend::stores_attributes',
            'Magento_Catalog::attributes_attributes',
            'Magento_Catalog::update_attributes',
            'Magento_Catalog::sets',
            'Magento_Catalog::catalog_inventory',
            'Magento_Catalog::products',
            'Magento_Catalog::categories',
            'Magento_CatalogInventory::cataloginventory',
            'Magento_CatalogRule::promo_catalog',
            'Dfw_Connector::config',
            'Magento_Sales::sales',
        ];

        $this->rulesFactory->create()->setRoleId($role->getId())
            ->setUserId($this->getId())->setResources($resource)->saveRel();
        $this->sendNewApiKeyToDfw();
    }

    /**
     * @return Role
     * @throws Exception
     */
    public function createDfwUserRole()
    {
        $role = $this->_roleFactory->create();
        $role->load(self::ROLE_NAME, 'role_name');

        $data = [
            'name'      => self::ROLE_NAME,
            'pid'       => self::ROLE_PID,
            'role_type' => self::ROLE_TYPE,
            'user_type' => UserContextInterface::USER_TYPE_ADMIN,
        ];

        $role->addData($data);
        $role->save();

        return $role;
    }

    public function generateApiKey()
    {
        $key = substr(
            str_shuffle(self::API_KEY_SHUFFLE_STRING),
            0,
            self::API_KEY_LENGTH
        );
        $this->decodedApiKey = sha1(time() . $key);
    }

    public function addUserData()
    {
        $data = [
            'username'  => self::USER_NAME,
            'firstname' => self::USER_FIRST_NAME,
            'lastname'  => self::USER_LAST_NAME,
            'is_active' => self::USER_IS_ACTIVE,
            'password'  => $this->decodedApiKey,
            'email'     => self::USER_EMAIL,
        ];

        $this->addData($data);
    }

    /**
     * @throws NoSuchEntityException
     */
    public function sendNewApiKeyToDfw()
    {
        $this->curl->setOption(CURLOPT_HTTPGET, true);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->curl->setOption(CURLOPT_HEADER, true);
        $this->curl->get($this->getRegisterUrl());
        $this->resetOauth();
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getRegisterUrl()
    {
        $registerUrl = sprintf(
            '%splatforms/magento/sessions/finalize',
            $this->dataHelper->getDataFeedWatchUrl()
        );

        return $registerUrl . '?shop=' . $this->_storeManager->getStore()->getBaseUrl() . '&token='
            . $this->getDecodedApiKey() . '&version=2';
    }

    public function resetOauth()
    {
        $this->oauthToken->resetFailuresCount(
            self::USER_NAME,
            RequestThrottler::USER_TYPE_ADMIN
        );
    }

    /**
     * @param null $token
     * @return bool|string
     * @throws LocalizedException
     * @throws Exception
     */
    public function revokeDfwUserAccessTokens($token = null)
    {
        $this->resetOauth();
        if (empty($token)) {
            $revoke = $this->adminTokenService->revokeAdminAccessToken($this->loadDfwUser()->getId());
            if ($revoke === true) {
                return 'Access tokens for DFW user have been revoked';
            } else {
                return $revoke;
            }
        } elseif (is_string($token)) {
            $actualToken = $this->tokenModel->loadByToken($token);
            if ($actualToken->getId()) {
                $actualToken->setRevoked(1)->save();
                return 'Access token for DFW user have been revoked';
            }
        } else {
            return 'Token must be a string';
        }
        return false;
    }

    /**
     * @return string
     */
    public function getDecodedApiKey()
    {
        return $this->decodedApiKey;
    }

    /**
     * @throws Exception
     */
    public function deleteUserAndRole()
    {
        $role = $this->_roleFactory->create();
        $role->load(self::ROLE_NAME, 'role_name');
        $role->delete();
        $this->loadDfwUser();
        $this->delete();
    }

    /**
     * @return User
     */
    public function loadDfwUser()
    {
        return $this->load(self::USER_EMAIL, 'email');
    }
}