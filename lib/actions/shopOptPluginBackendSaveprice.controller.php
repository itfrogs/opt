<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 7/23/15
 * Time: 10:44 PM
  */

class shopOptPluginBackendSavepriceController extends waJsonController
{
    public function execute()
    {
        $data = waRequest::post('data');

        if (floatval($data['price']) >= 0) {
            $pm = new shopOptPricesModel();
            $id = $pm->save($data);
            $this->response = array(
                'id' => $id,
            );
        }
        else {
            $this->setError(_wp('Price is not a number'));
        }
    }
}