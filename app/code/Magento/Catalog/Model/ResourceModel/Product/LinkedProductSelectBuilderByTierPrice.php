<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\DB\Select;

class LinkedProductSelectBuilderByTierPrice implements LinkedProductSelectBuilderInterface
{
    /**
     * Default website id
     */
    const DEFAULT_WEBSITE_ID = 0;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    private $catalogHelper;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Catalog\Helper\Data $catalogHelper
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Helper\Data $catalogHelper,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool
    ) {
        $this->storeManager = $storeManager;
        $this->resource = $resourceConnection;
        $this->customerSession = $customerSession;
        $this->catalogHelper = $catalogHelper;
        $this->metadataPool = $metadataPool;
    }

    /**
     * {@inheritdoc}
     */
    public function build($productId)
    {
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $priceSelect = $this->resource->getConnection()->select()
                ->from(['t' => $this->resource->getTableName('catalog_product_entity_tier_price')], $linkField)
                ->joinInner(
                    ['link' => $this->resource->getTableName('catalog_product_relation')],
                    "link.child_id = t.{$linkField}",
                    []
                )->where('link.parent_id = ? ', $productId)
                ->where('t.all_groups = 1 OR customer_group_id = ?', $this->customerSession->getCustomerGroupId())
                ->where('t.qty = ?', 1)
                ->order('t.value ' . Select::SQL_ASC)
                ->limit(1);

        $priceSelectDefault = clone $priceSelect;
        $priceSelectDefault->where('t.website_id = ?', self::DEFAULT_WEBSITE_ID);
        $select[] = $priceSelectDefault;

        if (!$this->catalogHelper->isPriceGlobal()) {
            $priceSelect->where('t.website_id = ?', $this->storeManager->getStore()->getWebsiteId());
            $select[] = $priceSelect;
        }

        return $select;
    }
}
