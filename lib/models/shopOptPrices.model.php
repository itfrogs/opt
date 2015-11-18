<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 9/1/15
 * Time: 10:32 PM
  */

class shopOptPricesModel extends waModel {

    protected $table = 'shop_opt_prices';

    public function save($data) {
        if (!isset($data['id']) || intval($data['id']) == 0) {
            $data['id'] = $this->insert($data);
        }
        else {
            $this->updateById($data['id'], $data);
        }
        return $data['id'];
    }

    public function getPricesByproductId($product_id) {
        //$results = $this->getByField('product_id', $product_id, true);
        $results = $this->query('SELECT o.*, c.name FROM '.$this->table.' o JOIN wa_contact_category c ON o.user_category_id = c.id ORDER BY c.id');
        $prices = array();
        foreach ($results as $price) {
            /*
             * Создаем двумерный массив с индексами 'sku_id', 'user_category_id', чтобы была возможность мгновенного перебора без запросов в базу.
             */
            $prices[$price['sku_id']][$price['user_category_id']] = $price;
        }
        return $prices;
    }

    public function getUserPriceBySkuId($sku_id) {
        $user = wa()->getUser();
        $cc = new waContactCategoriesModel();
        $skusModel = new shopProductSkusModel();
        $sku = $skusModel->getById($sku_id);
        $product = new shopProduct($sku['product_id']);
        $currency = $product->currency;

        if (!$user->getId()) return shop_currency($sku['price'], $currency, null, false);

        $avail_skus = $skusModel->getByField('product_id', $sku['product_id'], true);
        $skus_ids = array();
        foreach ($avail_skus as $s) {
            array_push($skus_ids, $s['id']);
        }
         $categories = $cc->getContactCategories($user->getId());

        $this->query(
            'DELETE FROM shop_opt_prices WHERE product_id = i:product_id AND sku_id NOT IN ('.implode(",", $skus_ids).')',
            array(
                'product_id'    => $sku['product_id'],
            )
        );

        if (!empty($categories)) {
            $price = $this->query(
                'SELECT MIN(price) AS price FROM shop_opt_prices WHERE sku_id = i:sku_id AND user_category_id IN ('.implode(",", array_keys($categories)).')',
                array(
                    'sku_id'        => $sku_id,
                )
            )->fetchField();
            if (empty($price) || floatval($price) == 0) {
                $price = $sku['price'];
            }
        }
        else {
            $price = $sku['price'];
        }

        if ($price > $sku['price']) $price = $sku['price'];

        return shop_currency($price, $currency, null, false);
    }

    public function isUserPriceBySkuId($sku_id) {
        $user = wa()->getUser();
        $cc = new waContactCategoriesModel();
        $categories = $cc->getContactCategories($user->getId());
        if (!empty($categories)) {
            $price = $this->query(
                'SELECT MIN(price) AS price FROM shop_opt_prices WHERE sku_id = i:sku_id AND user_category_id IN ('.implode(",", array_keys($categories)).')',
                array(
                    'sku_id'        => $sku_id,
                )
            )->fetchField();

            if (empty($price) || floatval($price) == 0) {
                return false;
            }
            else return true;
        }
        else {
            return false;
        }
    }
}