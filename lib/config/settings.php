<?php

return array(
    'in_cart_limit' => array(
        'title' => _wp('If the buyer put in the cart more than is available.'),
        'value' => 'maximum',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options' => array(
            array('value' => 'maximum', 'title' => _wp('Send to Cart maximum available'), 'description' => ''),
            array('value' => 'error', 'title' => _wp('Report an error'), 'description' => ''),
        )
    ),
    'categories'        => array(
        'title'         => _wp('Buyers categories'),
        'description'   => _wp('Select a user categories for plugin use.'),
        'control_type'  => waHtmlControl::GROUPBOX,
        'options_callback' => array('shopOptPlugin', 'getUserCategories'),
    ),
    'stocks'            => array(
        'title'         => _wp('Binding stocks to the settlements'),
        'description'   => _wp('Set the binding stocks to the settlements.'),
        'control_type' => waHtmlControl::CUSTOM . ' ' . 'shopOptPlugin::stocksControl',
    ),
    'enable_product_template'   => array(
        'title'         => _wp('Include an additional template in the product'),
        'description'   => _wp('Check to include an additional product template.'),
        'control_type'  => waHtmlControl::CHECKBOX,
    ),
);
