<?php
return array(
    'shop_opt_prices' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'user_category_id' => array('int', 11, 'null' => 0),
        'product_id' => array('int', 11),
        'sku_id' => array('int', 11),
        'service_id' => array('int', 11),
        'price' => array('decimal', "15,4", 'null' => 0, 'default' => '0.0000'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
