<?php

class shopOptCartItemsModel extends shopCartItemsModel
{
    protected $table = 'shop_cart_items';

    public function total($code)
    {
        $products_total = $this->getProductsTotal($code);
        $services_total = $this->getServicesTotal($code);
        return (float) ($products_total + $services_total);
    }

    // Helper for total()
    // Products total in frontend currency
    protected function getProductsTotal($code)
    {
        $sql = "SELECT c.quantity, s.*
                FROM ".$this->table." c
                    JOIN shop_product_skus s ON c.sku_id = s.id
                WHERE c.code = s:code
                    AND type = 'product'";

        $skus = $this->query($sql, array('code' => $code))->fetchAll('id');
        shopRounding::roundSkus($skus);
        $products_total = 0.0;

        foreach($skus as $sku_id => $s) {
            $item = $this->getSingleItem($code, $s['product_id'], $sku_id);
            if (isset($item['price']) &&    floatval($item['price']) > 0) {
                $products_total += floatval($item['price']) * $s['quantity'];
            }
            else {
                $products_total += $s['frontend_price'] * $s['quantity'];
            }

        }
        return $products_total;
    }



    public function getSingleItem($code, $product_id, $sku_id)
    {
        $sql = "SELECT c1.* FROM ".$this->table." c1
                LEFT JOIN ".$this->table." c2 ON c1.id = c2.parent_id
                WHERE c1.code = s:0 AND c1.type = 'product' AND c1.product_id = i:1 AND c1.sku_id = i:2 AND c2.id IS NULL LIMIT 1";
        $item = $this->query($sql, $code, $product_id, $sku_id)->fetch();

        /*************************************************************************
         * Объявляем модель и заменяем цену
         *************************************************************************/
        $opm = new shopOptPricesModel();
        $item['price'] = $opm->getUserPriceBySkuId($sku_id);
        //************************************************************************
        return $item;
    }

    public function getByCode($code, $full_info = false, $hierarchy = true)
    {
        if (!$code) {
            return array();
        }
        $sql = "SELECT * FROM ".$this->table." WHERE code = s:0 ORDER BY parent_id";
        $items = $this->query($sql, $code)->fetchAll('id');

        if ($full_info) {
            $rounding_enabled = shopRounding::isEnabled();

            $product_ids = $sku_ids = $service_ids = $variant_ids = array();
            foreach ($items as $item) {
                $product_ids[] = $item['product_id'];
                $sku_ids[] = $item['sku_id'];
                if ($item['type'] == 'service') {
                    $service_ids[] = $item['service_id'];
                    if ($item['service_variant_id']) {
                        $variant_ids[] = $item['service_variant_id'];
                    }
                }
            }

            $product_model = new shopProductModel();
            if (waRequest::param('url_type') == 2) {
                $products = $product_model->getWithCategoryUrl($product_ids);
            } else {
                $products = $product_model->getById($product_ids);
            }
            $rounding_enabled && shopRounding::roundProducts($products);

            $sku_model = new shopProductSkusModel();
            $skus = $sku_model->getByField('id', $sku_ids, 'id');
            $rounding_enabled && shopRounding::roundSkus($skus, $products);

            $service_model = new shopServiceModel();
            $services = $service_model->getByField('id', $service_ids, 'id');
            $rounding_enabled && shopRounding::roundServices($services);

            $service_variants_model = new shopServiceVariantsModel();
            $variants = $service_variants_model->getByField('id', $variant_ids, 'id');
            $rounding_enabled && shopRounding::roundServiceVariants($variants, $services);

            $product_services_model = new shopProductServicesModel();
            $rows = $product_services_model->getByProducts($product_ids);
            $rounding_enabled && shopRounding::roundServiceVariants($rows, $services);

            $product_services = $sku_services = array();
            foreach ($rows as $row) {
                if ($row['sku_id'] && !in_array($row['sku_id'], $sku_ids)) {
                    continue;
                }
                $service_ids[] = $row['service_id'];
                if (!$row['sku_id']) {
                    $product_services[$row['product_id']][$row['service_variant_id']] = $row;
                }
                if ($row['sku_id']) {
                    $sku_services[$row['sku_id']][$row['service_variant_id']] = $row;
                }
            }

            $image_model = null;
            foreach ($items as $item_key => &$item) {
                if ($item['type'] == 'product' && isset($products[$item['product_id']])) {
                    $item['product'] = $products[$item['product_id']];
                    if (!isset($skus[$item['sku_id']])) {
                        unset($items[$item_key]);
                        continue;
                    }
                    $sku = $skus[$item['sku_id']];

                    /*************************************************************************
                     * Заменяем цену
                     *************************************************************************/
                    $sku['price'] = shopOptPluginViewHelper::getUserPrice($item['sku_id']);
                    //************************************************************************

                    $item['product']['price'] = $sku['price'];

                    // Use SKU image instead of product image if specified
                    if ($sku['image_id'] && $sku['image_id'] != $item['product']['image_id']) {
                        $image_model || ($image_model = new shopProductImagesModel());
                        $img = $image_model->getById($sku['image_id']);
                        if ($img) {
                            $item['product']['image_id'] = $sku['image_id'];
                            $item['product']['ext'] = $img['ext'];
                        }
                    }

                    $item['sku_code'] = $sku['sku'];
                    $item['purchase_price'] = $sku['purchase_price'];
                    $item['sku_name'] = $sku['name'];
                    $item['currency'] = $item['product']['currency'];
                    $item['price'] = $sku['price'];
                    $item['name'] = $item['product']['name'];
                    if ($item['sku_name']) {
                        $item['name'] .= ' ('.$item['sku_name'].')';
                    }
                    // Fix for purchase price when rounding is enabled
                    if (!empty($item['product']['unconverted_currency']) && $item['product']['currency'] != $item['product']['unconverted_currency']) {
                        $item['purchase_price'] = shop_currency($item['purchase_price'], $item['product']['unconverted_currency'], $item['product']['currency'], false);
                    }
                } elseif ($item['type'] == 'service' && isset($services[$item['service_id']])) {
                    $item['name'] = $item['service_name'] = $services[$item['service_id']]['name'];
                    $item['currency'] = $services[$item['service_id']]['currency'];
                    $item['service'] = $services[$item['service_id']];
                    $item['variant_name'] = $variants[$item['service_variant_id']]['name'];
                    if ($item['variant_name']) {
                        $item['name'] .= ' ('.$item['variant_name'].')';
                    }
                    $item['price'] = $variants[$item['service_variant_id']]['price'];
                    if (isset($product_services[$item['product_id']][$item['service_variant_id']])) {
                        if ($product_services[$item['product_id']][$item['service_variant_id']]['price'] !== null) {
                            $item['price'] = $product_services[$item['product_id']][$item['service_variant_id']]['price'];
                        }
                    }
                    if (isset($sku_services[$item['sku_id']][$item['service_variant_id']])) {
                        if ($sku_services[$item['sku_id']][$item['service_variant_id']]['price'] !== null) {
                            $item['price'] = $sku_services[$item['sku_id']][$item['service_variant_id']]['price'];
                        }
                    }
                    if ($item['currency'] == '%') {
                        $p = $items[$item['parent_id']];
                        $item['price'] = $item['price'] * $p['price'] / 100;
                        $item['currency'] = $p['currency'];
                    }
                }
            }
            unset($item);
        }

        // sort
        foreach ($items as $item_id => $item) {
            if ($item['parent_id']) {
                $items[$item['parent_id']]['services'][] = $item;
                unset($items[$item_id]);
            }
        }
        if (!$hierarchy) {
            $result = array();
            foreach ($items as $item_id => $item) {
                if (isset($item['services'])) {
                    $i = $item;
                    unset($i['services']);
                    $result[$item_id] = $i;
                    foreach ($item['services'] as $s) {
                        $result[$s['id']] = $s;
                    }
                } else {
                    $result[$item_id] = $item;
                }
            }
            $items = $result;
        }
        return $items;
    }

}
