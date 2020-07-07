<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MageDevGroup\SalesGridImprovement\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;

class TmpTableProvider
{
    const FIRST_TEMPORARY_TABLE = 'tt';
    const SECOND_TEMPORARY_TABLE = 'tt2';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * TmpTableProvider constructor.
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * In tmp table we are keeping only IDs
     *
     * @param string $tableName
     * @return Table
     * @throws \Zend_Db_Exception
     */
    public function getIdentifiersTableDefinition($tableName)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->newTable($tableName)
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'primary' => false],
                'Order Id'
            );

        return $table;
    }
}
