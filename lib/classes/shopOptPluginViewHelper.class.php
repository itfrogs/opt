<?php

/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 14.09.14
 * Time: 23:27
 */
class shopOptPluginViewHelper extends waViewHelper
{
    /**
     * @var shopOptPlugin $plugin
     */
    private static $plugin;

    private static function getPlugin()
    {
        if (!empty(self::$plugin)) {
            $plugin = self::$plugin;
        } else {
            $plugin = wa()->getPlugin('opt');
        }
        return $plugin;
    }

    public static function getCartAddUrl()
    {
        return wa()->getRouteUrl('shop/frontend') . 'opt/cartadd/';
    }

    public static function getCartUrl()
    {
        return wa()->getRouteUrl('shop/frontend') . 'opt/cart/';
    }

    public static function getCheckoutUrl()
    {
        return wa()->getRouteUrl('shop/frontend') . 'opt/checkout';
    }

    public static function getCartTotal()
    {
        $cart = new shopOptPluginCart();
        return $cart->total();
    }

    public static function getThemePath()
    {
        $theme = waRequest::param('theme', 'default');
        $theme_path = wa()->getDataPath('themes', true) . '/' . $theme;
        if (!file_exists($theme_path) || !file_exists($theme_path . '/theme.xml')) {
            $theme_path = wa()->getAppPath() . '/themes/' . $theme;
        }
        return $theme_path;
    }

    public static function cartGetItemTotal($item_id) {
        $cartClass = new shopOptPluginCart();
        return $cartClass->getItemTotal($item_id);
    }

    public static function getUserPrice($sku_id) {
        $opm = new shopOptPricesModel();
        $price = $opm->getUserPriceBySkuId($sku_id);
        return $price;
    }

    public static function getUserPriceHtml($sku_id) {

    }

    public static function isUserPrice($sku_id) {
        $opm = new shopOptPricesModel();
        return $opm->isUserPriceBySkuId($sku_id);
    }

    public static function isStockEnable($stock_id) {
        $current_settlement = rtrim(wa()->getRouting()->getDomain() . '/' . wa()->getRouting()->getRoute('url'), '/*');
        $plugin = self::getPlugin();
        $settings = $plugin->getSettings();
        if (isset($settings['stocks'][$current_settlement])) {
            $stocks = $settings['stocks'][$current_settlement];
        }
        else $stocks = array();

        foreach ($stocks as $key => $stock) {
            $id = ltrim($key, 'id-');
            if ($id == $stock_id) return true;
        }
        return false;
    }

    public static function getPricesByproductId($product_id) {
        $opm = new shopOptPricesModel();
        return $opm->getPricesByproductId($product_id);
    }

    public static function calculateQuantity($sku) {
        /*********************************************************************
         * Определяем текущее поселение и настройки складов для него
         */
        $current_settlement = rtrim(wa()->getRouting()->getDomain() . '/' . wa()->getRouting()->getRoute('url'), '/*');
        /**
         * @var shopOptPlugin $plugin
         */
        $plugin = wa()->getPlugin('opt');
        $settings = $plugin->getSettings();
        if (isset($settings['stocks'][$current_settlement])) {
            $stocks = $settings['stocks'][$current_settlement];
        }
        else $stocks = array();

        // check quantity
        if (!wa()->getSetting('ignore_stock_count')) {
            // limit by main stock
            if (!empty($stocks)) {
                $product_stocks_model = new shopProductStocksModel();
                $sku['count'] = 0;
                foreach ($stocks as $key => $stock) {
                    $stock_id = ltrim($key, 'id-');
                    $row = $product_stocks_model->getByField(array(
                        'sku_id' => $sku['id'],
                        'stock_id' => $stock_id
                    ));
                    $sku['count'] += $row['count'];
                }
            }
        }
        return $sku;
    }
}