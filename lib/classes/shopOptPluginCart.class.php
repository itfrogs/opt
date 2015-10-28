<?php

class shopOptPluginCart extends shopCart
{

    /**
     * @var shopOptCartItemsModel
     */
    protected $model;

    /**
     * Constructor
     * @param string $code Cart unique ID
     */
    public function __construct($code='')
    {
        $this->model = new shopOptCartItemsModel();
        $this->code = waRequest::cookie(self::COOKIE_KEY, $code);
        if (!$this->code && wa()->getUser()->isAuth()) {
            $code = $this->model->getLastCode(wa()->getUser()->getId());
            if ($code) {
                $this->code = $code;
                // set cookie
                wa()->getResponse()->setCookie(self::COOKIE_KEY, $code, time() + 30 * 86400, null, '', false, true);
                $this->clearSessionData();
            }
        }
    }

    /**
     * Returns total cost of current shopping cart's items, expressed in default currency.
     *
     * @param bool $discount Whether applicable discounts must be taken into account
     * @return int
     */
    public function total($discount = true)
    {
        if (!$discount) {
            return (float) $this->model->total($this->code);
        }
        $total = $this->model->total($this->code);
        $order = array(
            'total' => $total,
            'items' => $this->items(false)
        );
        $discount = shopDiscounts::calculate($order);
        $total = $total - $discount;
        $this->setSessionData('total', $total);
        return (float) $total;
    }

    /**
     * Returns discount applicable to current customer's shopping cart contents, expressed in default currency.
     *
     * @param $order
     * @return float
     */
    public function discount(&$order = array())
    {
        $total = $this->model->total($this->code);
        $order = array(
            'total' => $total,
            'items' => $this->items(false)
        );
        return shopDiscounts::calculate($order);
    }

    /**
     * Returns total cost of current shopping cart's item with specified id, expressed in default currency.
     *
     * @param int|array $item_id Item id or item data array.
     * @return float
     */
    public function getItemTotal($item_id)
    {
        if (is_array($item_id)) {
            $item_id = $item_id['id'];
//            $item = $this->getItem($item_id);
        }

        // this gives price already rounded for frontend
        $item = $this->getItem($item_id);

        /*************************************************************************
         * Oбъявляем модель и узнаем свою цену
         *************************************************************************/
        $opm = new shopOptPricesModel();
        $price = $opm->getUserPriceBySkuId($item['sku_id']);

        //************************************************************************

        $cart_items_model = new shopCartItemsModel();
        $items = $cart_items_model->getByField('parent_id', $item['id'], true);

        if (!$items) {
            return $price;
        }

        $variants = array();
        foreach ($items as $s) {
            $variants[] = $s['service_variant_id'];
        }

        $product_services_model = new shopProductServicesModel();
        $sql = "SELECT v.id, s.currency, ps.sku_id, ps.price, v.price base_price
                    FROM shop_service_variants v
                        LEFT JOIN shop_product_services ps
                            ON v.id = ps.service_variant_id
                                AND ps.product_id = i:0
                                AND (ps.sku_id = i:1 OR ps.sku_id IS NULL)
                        JOIN shop_service s
                            ON v.service_id = s.id
                WHERE v.id IN (i:2)
                ORDER BY ps.sku_id";
        $rows = $product_services_model->query($sql, $item['product_id'], $item['sku_id'], $variants)->fetchAll();
        $prices = array();
        foreach ($rows as $row) {
            if (!isset($prices[$row['id']]) || $row['price']) {
                if ($row['price'] === null) {
                    $row['price'] = $row['base_price'];
                }
                $prices[$row['id']] = $row;
            }
        }

        $rounding_enabled = shopRounding::isEnabled();
        $frontend_currency = wa('shop')->getConfig()->getCurrency();

        foreach ($items as $s) {
            $v = $prices[$s['service_variant_id']];
            if ($v['currency'] == '%') {
                $v['price'] = $v['price'] * $item['price'] / 100;
                $v['currency'] = $item['currency'];
            }

            $service_price = shop_currency($v['price'], $v['currency'], $frontend_currency, false);
            if ($rounding_enabled && $v['currency'] != $frontend_currency) {
                $service_price = shopRounding::roundCurrency($service_price, $frontend_currency);
            }

            $price += $service_price * $item['quantity'];
        }
        return $price;
    }

    /**
     * Adds a new entry to table 'shop_cart_items'
     *
     * @param array $item Cart item data array
     * @param array $services
     * @return int New cart item id
     */
    public function addItem($item, $services = array(), $optPrice = 0)
    {
        if (!isset($item['create_datetime'])) {
            $item['create_datetime'] = date('Y-m-d H:i:s');
        }
        $item['code'] = $this->code;
        $item['contact_id'] = wa()->getUser()->getId();
        $item['id'] = $this->model->insert($item);

        // add services
        if (($item['type'] == 'product') && $services) {
            foreach ($services as $s) {
                $s['parent_id'] = $item['id'];
                $s['type'] = 'service';
                foreach (array('code', 'contact_id', 'product_id', 'sku_id', 'create_datetime') as $k) {
                    $s[$k] = $item[$k];
                }
                $s['id'] = $this->model->insert($s);
                $item['services'][] = $s;
            }
        }
        // clear session cache
        $this->clearSessionData();

        /**
         * @event cart_add
         * @param array $item
         */
        wa()->event('cart_add', $item);
        return $item['id'];
    }

    /**
     * Returns information about current shopping cart's items.
     *
     * @param bool $hierarchy Whether selected services must be included as 'services' sub-array for applicable items.
     *     If false, services are included as separate array items.
     * @return array
     */
    public function items($hierarchy = true)
    {
        return $this->model->getByCode($this->code, true, $hierarchy);
    }
}
