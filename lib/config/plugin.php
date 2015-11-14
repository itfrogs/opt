<?php
return array (
    'name' => _wp('Wholesale prices'),
    'description' => _wp('Allows you to assign products wholesale price.'),
    'icon' => 'img/opt16.png',
    'img' => 'img/opt16.png',
    'version' => '1.0.4',
    'vendor' => '964801',
    'frontend' => true,
    'handlers' =>
        array (
            'cart_delete'           => 'cartDelete',
            'backend_product'       => 'backendProduct',
            'product_save'          => 'productSave',
            'frontend_product'      => 'frontendProduct',
        ),
);
