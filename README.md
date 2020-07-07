# salesordergridimprovement
Sales order grid improvement

Problems I encountered:

- signyfid column can`t be hide from XML, need to write DataProvider to hide it
- product_sku column is dynamic, so there is no way to apply it on the fly, therefore 
derrived table was created in INNER JOIN and therefore addFieldToFilter was modified, to 
track this
- I`ve applied limits and offsets both to select that will insert data into temporary table
and when we are already pulling data from temporary table
- Filterable of all columns are working fine
- Sorting working as expected when pagin
gating

How I will approach this issue?

- sales_order_grid table is index table, so easier will be just to add new column with 
'product_sku' to sales_order_grid table and fill it during reindexation, it will be faster than custom query
