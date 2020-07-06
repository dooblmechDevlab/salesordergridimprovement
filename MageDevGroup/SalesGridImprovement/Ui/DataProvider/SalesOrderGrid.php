<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MageDevGroup\SalesGridImprovement\Ui\DataProvider;

class SalesOrderGrid extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * Rewriting getMeta method
     *
     * @return mixed
     */
    public function getMeta()
    {
        $meta = parent::getMeta();
        $meta["sales_order_columns"]["children"]["signifyd_guarantee_status"]["arguments"]["data"]["config"]['componentDisabled'] = true;
        return $meta;
    }
}
