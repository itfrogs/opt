<?php

try {
    $mstm = new shopOptPricesModel();
    $mstm->exec('DELETE FROM shop_opt_prices');
}
catch (waException $e) {
}
