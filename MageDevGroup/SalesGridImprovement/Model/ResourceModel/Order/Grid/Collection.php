<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MageDevGroup\SalesGridImprovement\Model\ResourceModel\Order\Grid;

use MageDevGroup\SalesGridImprovement\Model\TmpTableProvider;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Order grid collection
 */
class Collection extends \Magento\Sales\Model\ResourceModel\Order\Grid\Collection
{
    /**
     * @var TmpTableProvider
     */
    private $tmpTableProvider;

    /**
     * @var string
     */
    private $mainTable;

    /**
     * @var null
     */
    private $productSkuFilter = null;

    /**
     * Collection constructor.
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param TmpTableProvider $tmpTableProvider
     * @param string $mainTable
     * @param string $resourceModel
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        TmpTableProvider $tmpTableProvider,
        $mainTable = 'sales_order_grid',
        $resourceModel = \Magento\Sales\Model\ResourceModel\Order::class
    ) {
        $this->tmpTableProvider = $tmpTableProvider;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
        $this->mainTable = $mainTable;
    }

    /**
     * In the final query there should be no orders, because order are slowing down the system
     * That`s why we created tmp tables, where data is already sorted
     */
    private function resetOrders()
    {
        //Remove orders from collection
        foreach ($this->_orders as $field => $order) {
            $this->unshiftOrder($field);
        }

        $this->_orders = [];
    }

    /**
     * We are filling tmp tables
     * Creating new optimized query here
     *
     * @return Collection
     */
    protected function _beforeLoad()
    {
        $this->fillTmpTables();
        $select = $this->getSelect();
        $select->join(
            TmpTableProvider::SECOND_TEMPORARY_TABLE,
            TmpTableProvider::SECOND_TEMPORARY_TABLE . '.entity_id=main_table.entity_id'
        );
        $subSelect = $this->getResource()->getConnection()->select();
        $subSelect->from(['tl' => 'sales_order_item'], [
            'order_id',
            new Expression('GROUP_CONCAT(DISTINCT sku, " / ", name) AS product_sku ')
        ]);
        $subSelect->join('tt', 'tt.entity_id=tl.order_id AND tl.product_type="simple"');
        $subSelect->group('order_id');

        $select->join(
            ['soi' => $subSelect],
            'main_table.entity_id = soi.order_id'
        );
        //Here we can apply productSkuFilter,
        //Because previously this column does not exist
        if ($this->productSkuFilter !== null) {
            parent::addFieldToFilter('product_sku', $this->productSkuFilter);
        }

        $this->resetOrders();
        return parent::_beforeLoad();
    }

    /**
     * We have 2 tables: tt - temporary table 1
     * and tt2 - temporary table 2
     *
     * We need to fill both tables with relevant data from sales_order_grid table.
     *
     *
     * @throws \Zend_Db_Exception
     */
    private function fillTmpTables()
    {
        //Fill temporary table 1
        $connection = $this->getResource()->getConnection();
        $connection->dropTemporaryTable(TmpTableProvider::FIRST_TEMPORARY_TABLE);
        $connection->createTemporaryTable($this->tmpTableProvider->getIdentifiersTableDefinition(TmpTableProvider::FIRST_TEMPORARY_TABLE));
        $connection->query(
            $connection->insertFromSelect($this->getOrderGridSelect(), TmpTableProvider::FIRST_TEMPORARY_TABLE)
        );
        // Fill temporary table 2
        $connection = $this->getResource()->getConnection();
        $tt2 = $connection->select()
            ->from(TmpTableProvider::FIRST_TEMPORARY_TABLE);
        $connection->dropTemporaryTable(TmpTableProvider::SECOND_TEMPORARY_TABLE);
        $connection->createTemporaryTable($this->tmpTableProvider->getIdentifiersTableDefinition(TmpTableProvider::SECOND_TEMPORARY_TABLE));
        $connection->query(
            $connection->insertFromSelect($tt2, TmpTableProvider::SECOND_TEMPORARY_TABLE)
        );
    }

    /**
     * As product_sku is virtual field, that will be created as a result of GROUP_CONCAT,
     * we need to do not apply it instantly, but better to apply it directly during before_load
     *
     * @param $field
     * @param null $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field !== 'product_sku') {
            return parent::addFieldToFilter($field, $condition); // TODO: Change the autogenerated stub
        }

        $this->productSkuFilter = $condition;
        return $this;
    }

    /**
     * Retrieve order grid select
     * We are taking origin select
     * Running renderFilters and renderOrders on it,
     * in order to apply correct order and correct where statements to select
     * Retrieve that select and return origin select, where nothing is rendered yet
     *
     * @return \Magento\Framework\DB\Select
     * @throws \Zend_Db_Select_Exception
     */
    private function getOrderGridSelect()
    {
        $mainSelect = clone $this->getSelect();
        $this->_renderFilters()
            ->_renderOrders()
            ->addFieldToSelect('entity_id');

        $select = $this->getSelect();
        $this->_select = $mainSelect;
        return $select;
    }
}
