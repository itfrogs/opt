<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 11/11/15
 * Time: 12:28 AM
 */

class shopOptPluginBackendGetpriceController extends waJsonController
{
    /**
     * @var shopOptPlugin $plugin
     */
    private static $plugin;
    private static function getPlugin()
    {
        if (!isset(self::$plugin)) {
            self::$plugin = wa()->getPlugin('opt');
        }
        return self::$plugin;
    }

    public function execute()
    {
        $plugin = self::getPlugin();
        $settings = $plugin->getSettings();
        $data = waRequest::post('data');
        $product = new shopProduct($data['product_id']);
        $ccm = new waContactCategoryModel();
        $categories = $ccm->getAll();

        $pm = new shopOptPricesModel();
        $prices = $pm->getPricesByproductId($product['id']);

        $settings_cats = array();
        if (!empty($settings['categories'])) {
            foreach ($settings['categories'] as $key => $cat) {
                $id = ltrim($key, 'id-');
                array_push($settings_cats, $id);
            }
        }
        $skus = array();
        foreach ($product['skus'] as $i => $sku) {
            $skus[$sku['id']] = $sku;
            $cats = array();
            foreach ($categories as $key => $category) {
                if (!in_array($category['id'], $settings_cats)) {
                    unset($categories[$key]);
                }
                else {
                    $cats[$category['id']] = $category;

                    if (isset($prices[$sku['id']][$category['id']])) {
                        $price = $prices[$sku['id']][$category['id']];
                        $cats[$category['id']]['price'] = round(shop_currency($price['price'], $product['currency'], $product['currency'], false), 4);
                        $cats[$category['id']]['price_id'] = $price['id'];
                    }
                    else {
                        $cats[$category['id']]['price'] = '';
                        $cats[$category['id']]['price_id'] = 0;
                    }
                }
            }
            $skus[$sku['id']]['categories'] = $cats;
        }

        $this->response = array(
            'skus' => $skus,
            'categories' => $categories,
        );
    }
}