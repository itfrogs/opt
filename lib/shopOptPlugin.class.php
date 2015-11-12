<?php

class shopOptPlugin extends shopPlugin
{
    /**
     * @var waView $view
     */
    private static $view;
    private static function getView()
    {
        if (!isset(self::$view)) {
            self::$view = waSystem::getInstance()->getView();
        }
        return self::$view;
    }

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

    public function orderCreate($data){
        $code = waRequest::cookie('shop_cart');
        if (!empty($code)) {
            $oim = new shopOrderItemsModel();
            $om = new shopOrderModel();
            $order = $om->getById($data['order_id']);
            $opm = new shopOptPricesModel();
            $items = $oim->getItems($data['order_id']);
            $order_total = 0;
            foreach ($items as $item) {
                $item['price'] = $opm->getUserPriceBySkuId($item['sku_id']);
                $oim->updateById($item['id'], $item);
                $order_total += $item['price'] * $item['quantity'];
            }
            $order['total'] = $order_total;
            $om->updateById($data['order_id'], $order);
            return true;
        }
        else return false;
    }

    /**
     * @var shopProduct $product
     */
    public function backendProduct($product) {
        $view = self::getView();
        $settings = $this->getSettings();
        $product = $product->getData();

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

        $view->assign('categories', json_encode($categories));
        $view->assign('skus', json_encode($skus));
        $view->assign('product', json_encode($product));

        return array(
            'edit_section_li' => $view->fetch($this->path . '/templates/SettingsJs.html')
        );
    }

    public static function getUserCategories()
    {
        $ccm = new waContactCategoryModel();
        $categories = $ccm->getAll();
        $options = array();

        foreach ($categories as $category) {
            $option = array(
                array(
                    'title' => $category['name'],
                    'value' => 'id-'.$category['id'],
                )
            );
            $options = array_merge($options, $option);
        }
        return $options;
    }

    public static function stocksControl() {
        $view = self::getView();
        $plugin = self::getPlugin();
        $settlements = self::getSettlements();
        $settings = $plugin->getSettings();
        $stocks_model = new shopStockModel();
        $stocks = $stocks_model->getAll();

        $controls = array();
        foreach ($settlements as $key => $settlement) {
            $params = array();
            $options = array();
            foreach ($stocks as $stock) {
                $option = array(
                    array(
                        'title' => $stock['name'],
                        'value' => 'id-'.$stock['id'],
                    )
                );
                $options = array_merge($options, $option);
            }
            $params['options'] = $options;
            $params['title'] = $settlement;
            $params['title_wrapper'] = '%s<br />';
            $params['class'] = 'field';
            $params['value'] = $settings['stocks'][$settlement];
            $controls[$key] = waHtmlControl::getControl('groupbox','shop_opt[stocks][' . $settlement . ']', $params);
        }

        $view->assign('controls', $controls);
        $view->assign('settings', $settings);
        $view->assign('settlements', $settlements);
        return $view->fetch($plugin->path . '/templates/stocksControl.html');
    }

    /**
     * Возвращает массив URL поселений магазина в виде строк типа 'domain.com/shop/*'
     *
     * @return array
     */
    public static function getSettlements()
    {
        $settlements = array();
        $routing = wa()->getRouting();
        $domain_routes = $routing->getByApp('shop');
        foreach ($domain_routes as $domain => $routes) {
            foreach ($routes as $route) {
                $settlement = $domain.'/'.$route['url'];
                $settlement = rtrim($settlement, '/*');
                $settlement = ltrim($settlement, 'http://');
                $settlement = ltrim($settlement, 'https://');
                $settlements[] = $settlement;
            }
        }
        return $settlements;
    }

    public function productSave($product) {
        $opm = new shopOptPricesModel();
        foreach ($product['data']['skus'] as $sku) {
            if (isset($sku['optprice'])) {
                foreach ($sku['optprice'] as $key => $value) {
                    $data = array(
                        'id' => $value['id'],
                        'user_category_id' => $key,
                        'product_id' => $product['data']['id'],
                        'sku_id' => $sku['id'],
                        'price' => $value['price'],
                    );
                    if ($value['id'] > 0) {
                        unset($data['id']);
                    }
                    $opm->save($data);
                }
            }
        }
    }

    public function frontendProduct($product) {
        $view = self::getView();
        $plugin = self::getPlugin();
        $settings = $plugin->getSettings();

        if ($settings['enable_product_template']) {
            $user = wa()->getUser();
            $cc = new waContactCategoriesModel();
            if (!$user->getId()) {
                $categories = array();
            }
            else {
                $categories = $cc->getContactCategories($user->getId());
            }

            $cats = array();
            foreach ($categories as $i => $category) {
                $cats[] =  $category['id'];
            }

            $view->assign('cat_ids', $cats);
            $view->assign('categories', $categories);
            $view->assign('product', $product);
            $view->assign('settings', $settings);
            return array(
                'block' => $view->fetch($plugin->path . '/templates/Product.html'),
            );
        }
    }
}
