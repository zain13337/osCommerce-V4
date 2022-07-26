<?php

/**
 * This file is part of osCommerce ecommerce platform.
 * osCommerce the ecommerce
 * 
 * @link https://www.oscommerce.com
 * @copyright Copyright (c) 2000-2022 osCommerce LTD
 * 
 * Released under the GNU General Public License
 * For the full copyright and license information, please view the LICENSE.TXT file that was distributed with this source code.
 */

namespace common\components\google\widgets;

use Yii;
use common\components\GoogleTools;
use frontend\design\Info;
use common\helpers\Product;
use common\helpers\Manufacturers;
use common\classes\platform;

class GoogleTagmanger {

    public $module = FALSE;

    CONST EVENT_NAME = 'GTMevent';

    public function __construct(GoogleTools $tool) {
        $module = $tool->getModulesProvider()->getActiveByCode('tagmanger', platform::currentId());
        if ($module){
            $this->module = $module;
        }
    }
    
    private static $instance = null;
    public static function instance(){
        if (!is_object(self::$instance)){
            self::$instance = new self(GoogleTools::instance());
        }
        return self::$instance;
    }

    public static function headTag() {
        $Gt = self::instance();
        if (Info::isAdmin() || ($Gt->module === FALSE))
            return '';
        return $Gt->module->getSelectedCode($Gt->module->config[$Gt->module->code]['fields'][0]['value'], 1, $Gt);
    }

    public static function bodyTag() {
        $Gt = self::instance();
        if (Info::isAdmin() || ($Gt->module === FALSE))
            return '';
        return $Gt->module->getSelectedCode($Gt->module->config[$Gt->module->code]['fields'][0]['value'], 2);
    }

    public static function getEvent() {
      if (self::checkAPI()) return;
        $session = Yii::$app->session;
        if ($session->has(self::EVENT_NAME)) {
            return $session->get(self::EVENT_NAME);
        } elseif (isset($_COOKIE['tagAction']) && !empty($_COOKIE['tagAction'])) {
            return $_COOKIE['tagAction'];
        }
        return false;
    }

    /*
     * use setEvent after some action you want perform, would be checkecd at trigger() 
     */

    public static function setEvent($value) {
      if (self::checkAPI()) return;
        $session = Yii::$app->session;
        $session->set(self::EVENT_NAME, $value);
    }

    public static function clearEvent() {
      if (self::checkAPI()) return;
        $session = Yii::$app->session;
        $session->remove(self::EVENT_NAME);
        self::removeTagAction();
    }

    /*
     * render Js to catch events, 
     * params is array [ 'class' => '.name of class or another selector', 'action' => 'some js action', 'php_action' => 'action would be fired to collect data', 'page' => 'cuurent or name of page' ]
     */

    public static function getJsEvents($params = []) {
        return false; // deprecated
        if (is_array($params) && count($params)) {
            $code = '';
            if (Yii::$app->request->getPathInfo() == 'checkout/success')
                return;
            foreach ($params as $element) {
                if ($element['page'] == 'current') {
                    $page = "window.localStorage['tagPage'] = window.location.pathname";
                } else {
                    $page = "window.localStorage['tagPage'] = '{$element['page']}'";
                }

                if (isset($element['immidiately']) && $element['immidiately']) {
                    $response = self::trigger(false, $element['php_action']);
                    $code .= <<<EOD
                      tl(function(){
                        $('body').on("{$element['action']}", "{$element['class']}", function(e){
                            {$response}
                        });
                       })
EOD;
                } else {
                    $code .= <<<EOD
                tl(function(){
                    $('body').on("{$element['action']}", "{$element['class']}", function(e){
                        {$page};
                        $.cookie('tagAction', "{$element['php_action']}", cookieConfig || {});
                    });
                });
EOD;
                }
            }
            return "<script>" . $code . "</script>";
        } else {
            return;
        }
    }

    /*
     * used on layout to perform isset event, can be called forced
     */

    public static function trigger($wrap = true, $fEvent = '') {
        if (Yii::$app->request->isAjax) return;
        $Gt = self::instance();
        if (Info::isAdmin() || ($Gt->module === FALSE))
            return '';
        $response = "";

        $event = (empty($fEvent) ? self::getEvent()  : $fEvent);
        if (!$event)
            return $response;
        
        if (!isset($Gt->module->config['tagmanger']['fields'][1]) || (isset($Gt->module->config['tagmanger']['fields'][1]) && $Gt->module->config['tagmanger']['fields'][1]['value'])) {
            switch ($event) {
                case "addToCart":
                    $response = self::addToCart();
                    break;
                case "removeFromCart":
                    $response = self::removeFromCart();
                    break;
                case "productClick":
                    $response = self::productClick();
                    break;
                case "checkout":
                    $response = self::checkout($wrap);
                    break;
                case "promotionClick":
                    $response = self::promotionClick();
                    break;
                case "indexPage":
                    $response = self::indexPage();
                    break;
                case "productListing":
                    $response = self::productListing();
                    break;
                case "productPage":
                    $response = self::productPage();
                    break;
                case "shoppingCart":
                    $response = self::orderStep(1, 'cart');
                    break;
                case "orderStep1":
                    $response = self::orderStep(1);
                    break;
                case "orderStep2":
                    $response = self::orderStep(2);
                    break;
                case "orderStep3":
                    $response = self::orderStep(3);
                    break;
                case "orderStep4":
                    $response = self::orderStep(4);
                    break;
                case "orderSuccess":
                    $response = self::orderSuccess();
                    break;
            }
            if ($wrap) {
                $response = "<script>" . $response . "</script>";
            }
        }
        self::clearEvent();
        return $response;
    }

    public static function addToCart() {
      if (self::checkAPI()) return;
        global $new_products_id_in_cart;
        global $cart;
        $currencies = \Yii::$container->get('currencies');
        $currency = \Yii::$app->settings->get('currency');
        $products = $cart->get_products($new_products_id_in_cart);
        if (is_array($products)) {
            $list = [];
            foreach ($products as $product) {
                $brand = self::helperBrand($product['id']);
                $attributes = "";
                if (is_array($product['attributes']) && count($product['attributes']) && false) { //shopping cart hasn't names
                    $map = [
                        'options' => \yii\helpers\ArrayHelper::getColumn($product['attributes'], 'option'),
                        'values' => \yii\helpers\ArrayHelper::getColumn($product['attributes'], 'value'),
                    ];
                    foreach ($map['options'] as $key => $value) {
                        $attributes .= $value . ": " . $map['values'][$key] . ", ";
                    }
                    if (strlen($attributes) > 0) {
                        $attributes = substr($attributes, 0, -2);
                    }
                }
                $products_tax = \common\helpers\Tax::get_tax_rate($product['products_tax_class_id']);
                $price = $currencies->calculate_price($product['final_price'], $products_tax);

                $category_name = self::helperCategory($product['id']);

                $list[] = [
                    "id" => "{$product['model']}",
                    "reference" => "{$product['model']}",
                    "name" => "{$product['name']}",
                    "price" => "{$price}",
                    "brand" => "{$brand}",
                    "category" => "{$category_name}",
                    "variant" => "{$attributes}",
                    "position" => 0,
                    "quantity" => "{$product['quantity']}"
                ];
            }

            if (count($list)) {
                $list = json_encode($list);
                return self::eventDatalayerPush('{
                    "event": "addToCart",
                    "ecommerce": {
                        "currencyCode": "' . $currency . '",
                        "add": {
                            "products": ' . $list . '
                        }
                    }
                }');
/*
                return <<<EOD
            if (typeof dataLayer == 'object'){
                dataLayer.push({
                    "event": "addToCart",
                    "ecommerce": {
                        "currencyCode": "{$currency}",
                        "add": {
                            "products": {$list}
                            }
                    }
                });
                
                }
EOD;
*/
                /* var _e = new Event('addToCart'); window.addEventListener('addToCart', function(){}); window.dispatchEvent(_e); */
            }
        }
        return;
    }

    public static function removeFromCart() {
      if (self::checkAPI()) return;
        global $last_removed;
        $currencies = \Yii::$container->get('currencies');
        $restored_data = unserialize(base64_decode($last_removed));
        
        if (is_array($restored_data) && count($restored_data)) {
            $uprid = $restored_data['products_id'];
            $last_removed_data = $restored_data['data'];
        }
        
        tep_session_unregister('last_removed');

        if ($uprid) {
            $languages_id = \Yii::$app->settings->get('languages_id');
            $list = [];
            $atts = [];
            $uprid = \common\helpers\Inventory::normalize_id($uprid, $atts);
            $currency = \Yii::$app->settings->get('currency');
            $_products = tep_db_fetch_array(tep_db_query("select p.products_id, if(length(pd1.products_name), pd1.products_name, pd.products_name) as products_name, p.products_model, p.products_price, p.products_price_full, p.products_weight, p.products_tax_class_id "
                            . "from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_DESCRIPTION . " pd1 on pd1.products_id = p.products_id and pd1.language_id='" . (int) $languages_id . "' and pd1.platform_id = '".(int)Yii::$app->get('platform')->config()->getPlatformToDescription()."' "
                            . "where p.products_id = '" . (int) $uprid . "' and pd.platform_id = '".intval(\common\classes\platform::defaultId())."' and pd.products_id = p.products_id "
                            . " and pd.language_id = '" . (int) $languages_id . "'"));
            if ($_products){
                $prid = $_products['products_id'];

                if ($ext = \common\helpers\Acl::checkExtensionAllowed('Inventory', 'allowed')) {
                    $_products = array_replace($_products, $ext::getInventorySettings($prid, $uprid));
                }

                $configurator_koeff = 1;
                if ($last_removed_data['parent'] != '') {
                    $products_price = \common\helpers\Configurator::get_products_price_configurator($prid, $last_removed_data['qty']);
                    if (($regular_price = \common\helpers\Product::get_products_price($prid, $last_removed_data['qty'])) > 0) {
                        $configurator_koeff = $products_price / $regular_price;
                    }
                } else {
                    $products_price = \common\helpers\Product::get_products_price($prid, $last_removed_data['qty'], $_products['products_price']);
                }

                $products_price_old = $products_price;
                $special_price = \common\helpers\Product::get_products_special_price($prid, $last_removed_data['qty']);
                if ($special_price !== false) {
                    $products_price = $special_price;
                }

                $brand = self::helperBrand($prid);
                $attributes = "";
                $products_tax = \common\helpers\Tax::get_tax_rate(Product::get_products_info($prid, 'products_tax_class_id'));
                $price = $currencies->calculate_price($products_price, $products_tax);

                $category_name = self::helperCategory($prid);

                $list[] = [
                    "id" => "{$_products['products_model']}",
                    "reference" => "{$_products['products_model']}",
                    "name" => "{$_products['products_name']}",
                    "price" => "{$price}",
                    "brand" => "{$brand}",
                    "category" => "{$category_name}",
                    "variant" => "{$attributes}",
                    "position" => 0,
                    "quantity" => "{$last_removed_data['qty']}"
                ];
            }

            if (count($list)) {
                $list = json_encode($list);
                return self::eventDatalayerPush('{
                        "event": "removeFromCart",
                        "ecommerce": {
                            "currencyCode": "' . $currency . '",
                            "remove": {
                                "products": ' . $list . '
                            }
                    }');
/*
                return <<<EOD
                if (typeof dataLayer == 'object'){
                    dataLayer.push({
                        "event": "removeFromCart",
                        "ecommerce": {
                            "currencyCode": "{$currency}",
                            "remove": {
                                "products": {$list}
                                }
                        }
                    });
                   
                } 
EOD;
*/
                /* var _e = new Event('removeFromCart');window.addEventListener('removeFromCart', function(){});window.dispatchEvent(_e); */
            }
        }
        return;
    }

    public static function removeTagAction() {
      if (self::checkAPI()) return;
        if (isset($_COOKIE['tagAction'])) {
            unset($_COOKIE['tagAction']);
        }
    }

    public static function productClick() {
        global $products_id;
        if ($products_id) { //not ready
            $currencies = \Yii::$container->get('currencies');
            $currency = \Yii::$app->settings->get('currency');
            $product = Yii::$container->get('products')->getProduct($products_id);
            //$products_tax = \common\helpers\Tax::get_tax_rate($product['products_tax_class_id']);
            $price = ($product['special_price']?$product['special_price']:($product['current_price']?$product['current_price']:$product['products_price']));
            $price = $currencies->calculate_price($product['products_price'], $product['tax_rate']);
            $list = [
                [
                    'id' => $product['model'],
                    'reference' => $product['model'],
                    'name' => $product['products_name'],
                    'price' => $price,
                    'brand' => self::helperBrand($products_id),
                    'category' => self::helperCategory($products_id),
                    'position' => 1
                ]
            ];
            self::removeTagAction();
            $list = json_encode($list);
            $main_js = Info::themeFile('/js/main.js');

            $tagAction = <<<EOD
tl("{$main_js}", function(){ jQuery.cookie('tagAction','', cookieConfig || {}); });
EOD;
            return self::eventDatalayerPush('{
                "event": "productClick",
                "ecommerce": {
                    "currencyCode": "' . $currency . '",
                    "click": {
                        "actionField": {
                          "list": localStorage.tagPage
                        },
                        "products": ' . $list . '
                    }
                }
            }') . "\n" . $tagAction;
/*
            return <<<EOD
            if (typeof dataLayer == 'object'){
                    dataLayer.push({
                        "event": "productClick",
                        "ecommerce": {
                            "currencyCode": "{$currency}",
                            "click": {
                                "actionField": {
                                  "list": localStorage.tagPage
                                },
                                "products": {$list}
                            }
                        }
                    });
                }
tl("{$main_js}", function(){ jQuery.cookie('tagAction','', cookieConfig || {}); });

EOD;
*/
            /* var _e = new Event('productClick');window.addEventListener('productClick', function(){}); window.dispatchEvent(_e); */
        }
    }
    
    public static function promotionClick(){
        $code = <<<EOD
                if (typeof dataLayer == 'object'){
                    var cF = function(id){
                        var promoBan = {};
                        banners.forEach(function(ban){
                            if (ban.id == id ){
                                dataLayer.push({
                                    "event": "promotionClick",
                                    "ecommerce": {
                                        "promoClick": {
                                            'promotions': [
                                              ban
                                             ]
                                          }
                                    }
                                  });
                            }
                        })
                        
                    }
                    cF($(e.target).data('id'));
                }
EOD;
            return $code;
    }

    public static function checkout($wrap = true) {
      return false; // deprecated
      if (self::checkAPI()) return;
        $manager = \common\services\OrderManager::loadManager();
        try{
            $order = $manager->getOrderInstance();
        } catch (\Exception $ex) {
            return '';
        }

        if (Yii::$app->request->isAjax || !is_array($order->products) || count($order->products) == 0)
            return;
        $currencies = \Yii::$container->get('currencies');
        $list = [];
        foreach ($order->products as $product) {

            $brand = self::helperBrand($product['id']);
            $attributes = "";
            if (is_array($product['attributes']) && count($product['attributes']) && false) { //shopping cart hasn't names
                $map = [
                    'options' => \yii\helpers\ArrayHelper::getColumn($product['attributes'], 'option'),
                    'values' => \yii\helpers\ArrayHelper::getColumn($product['attributes'], 'value'),
                ];
                foreach ($map['options'] as $key => $value) {
                    $attributes .= $value . ": " . $map['values'][$key] . ", ";
                }
                if (strlen($attributes) > 0) {
                    $attributes = substr($attributes, 0, -2);
                }
            }
            $price = $currencies->calculate_price($product['final_price'], $product['tax']);

            $category_name = self::helperCategory($product['id']);

            $list[] = [
                "id" => "{$product['model']}",
                "reference" => "{$product['model']}",
                "name" => "{$product['name']}",
                "price" => "{$price}",
                "brand" => "{$brand}",
                "category" => "{$category_name}",
                "variant" => "{$attributes}",
                "position" => 0,
                "quantity" => "{$product['quantity']}"
            ];
        }
        if (count($list)) {
            $list = json_encode($list);
            $step = self::getStepNumber();

            if (!$step) {
                $step = "sts[id]";
            }

            $code = <<<EOD
                if (typeof dataLayer == 'object'){
                    var cF = function(){
                        var sts = {'shopping-cart':1, 'shipping-step':2, 'payment-step': 3, 'confirmation-step':4, 'success':5}, id = 'shopping-cart';
                        if (document.querySelector('.checkout-step.active') != null){id = document.querySelector('.checkout-step.active').id;}
                        if (window.localStorage['tCS'] == 4 && id == 'shopping-cart'){id = 'success';}
                        var _s = {$step}, _p = {$list};if (_s == 5){_p = [];}
                        if (window.localStorage['tCS'] != _s ){
                            window.localStorage['tCS'] = _s;
                            dataLayer.push({
                            "event": "checkout",
                            "ecommerce": {
                              "checkout": {
                                "actionField": { "step": _s },
                                "products": _p
                              },
                            }
                          });
                        
                        }
                    }
                    cF();$('body').on('click', '.btn-next', function(){cF();});
                }
EOD;
            /* var _e = new Event('checkout');window.addEventListener('checkout',function(){});window.dispatchEvent(_e); */
            if ($wrap) {
                return "window.onload = function(){ " . $code . " }";
            } else {
                return $code;
            }
        }
        return;
    }

    private static function getStepNumber() {
        if (!\frontend\design\Info::themeSetting('checkout_view')) { //!multypages
            switch (\Yii::$app->request->getPathInfo()) {
                case 'shopping-cart':case 'shopping-cart/index': return '1';
                    break;
                case 'checkout/index':case 'checkout': return '2';
                    break;
                case 'checkout/payment': return '3';
                    break;
                case 'checkout/confirmation': return '4';
                    break;
                case 'checkout/success': return '5';
                    break;
            }
        }
        return false;
    }

    private static function helperBrand($products_id) {
        $manufacturers_id = \common\helpers\Product::get_products_info((int) $products_id, 'manufacturers_id');
        $brand = Manufacturers::get_manufacturer_info('manufacturers_name', $manufacturers_id);
        if ( empty($brand) ) $brand = '';
        return $brand;
    }

    private static function helperCategory($products_id) {
        $p2cModel = \common\models\Products2Categories::findOne(['products_id' => (int)$products_id]);
        return $p2cModel ? str_replace('"', '\"', \common\helpers\Categories::get_categories_name($p2cModel->categories_id)) : '';
    }

    /**
     * check route - don't use if called from console (no session and cookies)
     */
    private static function checkAPI(){
      $ret = false;
      if ( Yii::$app->id=='app-console' ) {
        $ret = true;
      }
      return $ret;
    }

    public static function indexPage() {
        if (self::checkAPI()) return;
        $currency = \Yii::$app->settings->get('currency');
        return self::eventDatalayerPush('{
            "pageCategory":"index",
            "ecommerce": {
                "currencyCode":"' . $currency . '",
            },
            "google_tag_params": {
                "ecomm_pagetype":"home",
            }
        }');
    }

    public static function productListing() {
        if (self::checkAPI()) return;
        $currencies = \Yii::$container->get('currencies');
        $currency = \Yii::$app->settings->get('currency');
        $products = \frontend\design\Info::$jsGlobalData['products'];
        if (is_array($products) && \frontend\design\Info::$jsGlobalData['page_title']) {
            $list = [];
            $position = 1;
            foreach ($products as $prod) {
                $products_id = \common\helpers\Inventory::get_prid($prod['products_id']);
                $price = $prod['calculated_price'];
                $category_name = self::helperCategory($products_id);
                $brand = self::helperBrand($products_id);

                $list[] = [
                    "id" => "{$prod['products_model']}",
                    "reference" => "{$prod['products_model']}",
                    "name" => "{$prod['products_name']}",
                    "price" => "{$prod['calculated_price']}",
                    "price_tax_exc" => "{$prod['calculated_price_exc']}",
                    "brand" => "{$brand}",
                    "category" => "{$category_name}",
                    "position" => $position++,
                ];
            }
            if (count($list)) {
                $list = json_encode($list);
                return self::eventDatalayerPush('{
                    "pageCategory":"category",
                    "ecommerce": {
                        "currencyCode":"' . $currency . '",
                        "impressions" :' . $list . '
                    },
                    "google_tag_params": {
                        "ecomm_pagetype":"category",
                        "ecomm_category":"' . \frontend\design\Info::$jsGlobalData['page_title'] . '"
                    }
                }');
            }
        }
        return;
    }

    public static function productPage() {
        if (self::checkAPI()) return;
        global $products_id;
        if ($products_id) { //not ready
            $currencies = \Yii::$container->get('currencies');
            $currency = \Yii::$app->settings->get('currency');
            $product = Yii::$container->get('products')->getProduct($products_id);
            $category = self::helperCategory($products_id);
            $price = ($product['special_price']?$product['special_price']:($product['current_price']?$product['current_price']:$product['products_price']));
            $price_inc = $currencies->calculate_price($price, $product['tax_rate']);
            $price_exc = $currencies->calculate_price($price, 0);
            $list = [
                [
                    'id' => $product['model'],
                    'reference' => $product['model'],
                    'name' => $product['products_name'],
                    'price' => $price_inc,
                    'price_tax_exc' => $price_exc,
                    'brand' => self::helperBrand($products_id),
                    'category' => $category,
                    //'position' => 1
                ]
            ];
            $list = json_encode($list);
            return self::eventDatalayerPush('{
                "pageCategory":"product",
                "ecommerce": {
                    "currencyCode":"' . $currency . '",
                    "detail":{
                        "products":' . $list . '
                    }
                },
                "google_tag_params":{
                    "ecomm_pagetype":"product",
                    "ecomm_prodid":"' . $product['model'] . '",
                    "ecomm_totalvalue":' . number_format($price_inc, 2, ".", "") . ',
                    "ecomm_category":"' . $category . '",
                    "ecomm_totalvalue_tax_exc":' . number_format($price_exc, 2, ".", "") . '
                }
            }');
        }
    }

    public static function orderStep($step, $pageCategory = 'order') {
        if (self::checkAPI()) return;
        $currencies = \Yii::$container->get('currencies');
        $currency = \Yii::$app->settings->get('currency');
        try {
            $manager = \common\services\OrderManager::loadManager();
            $order = $manager->getOrderInstance();
            $products = $order->products;
        } catch (\Exception $ex) {
            global $cart;
            $products = $cart->get_products();
        }
        if (is_array($products)) {
            $group_properties_list = \common\models\Properties::find()
                ->alias('p')
                ->join('inner join',\common\models\PropertiesDescription::tableName()." pd", "pd.properties_id=p.properties_id AND pd.language_id='".(int)\Yii::$app->settings->get('languages_id')."'")
                ->where(['p.products_groups'=>1])
                ->select(['pd.properties_name','p.properties_id'])
                ->orderBy(['p.sort_order'=>SORT_ASC,'pd.properties_id'=>SORT_ASC,])
                ->asArray()
                ->all();
            $group_properties_list = \yii\helpers\ArrayHelper::map($group_properties_list,'properties_id', 'properties_name');

            $list = [];
            $position = 1;
            $totalvalue = 0;
            $totalvalue_exc = 0;
            $models_array = array();

            foreach ($products as $product) {
                $brand = self::helperBrand($product['id']);
                $category_name = self::helperCategory($product['id']);
                $attributes = '';
                if (is_array($product['attributes']) && count($product['attributes'])) {
                    $map = [
                        'options' => \yii\helpers\ArrayHelper::getColumn($product['attributes'], 'option'),
                        'values' => \yii\helpers\ArrayHelper::getColumn($product['attributes'], 'value'),
                    ];
                    foreach ($map['options'] as $key => $value) {
                        if ($value) {
                            $attributes .= $value . ": " . $map['values'][$key] . ", ";
                        }
                    }
                    if (strlen($attributes) > 0) {
                        $attributes = substr($attributes, 0, -2);
                    }
                }
                if ( count($group_properties_list)>0 ) {
                    $_prop_values = [];
                    foreach ( \common\models\Properties2Propducts::find()
                                  ->where(['products_id'=>(int)$product['id']])
                                  ->andWhere(['IN','properties_id',array_keys($group_properties_list)])
                                  ->select(['properties_id','values_id'])
                                  ->asArray()
                                  ->all() as $group_property){
                        $_prop_values[$group_property['properties_id']] = \common\helpers\Properties::get_properties_value($group_property['values_id'], (int)\Yii::$app->settings->get('languages_id'));
                    }
                    foreach ($group_properties_list as $_propId=>$_propName){
                        if ( !isset($_prop_values[$_propId]) || !is_object($_prop_values[$_propId]) ) continue;
                        if ( !empty($attributes) ) $attributes.=', ';
                        $attributes = $_propName .": ". $_prop_values[$_propId]->values_text;
                    }
                }
                if (isset($product['tax'])) {
                    $products_tax = $product['tax'];
                } else {
                    $products_tax = \common\helpers\Tax::get_tax_rate($product['tax_class_id']);
                }
                $price = $currencies->calculate_price($product['final_price'], $products_tax);
                $price_tax_exc = $currencies->calculate_price($product['final_price'], 0);
                $quantity = $product['quantity'] ? $product['quantity'] : $product['qty'];

                $totalvalue += $price * $quantity;
                $totalvalue_exc += $price_tax_exc * $quantity;
                $models_array[] = $product['model'];

                $list[] = [
                    "id" => "{$product['model']}",
                    "reference" => "{$product['model']}",
                    "name" => "{$product['name']}",
                    "price" => "{$price}",
                    "price_tax_exc" => "{$price_tax_exc}",
                    "brand" => "{$brand}",
                    "category" => "{$category_name}",
                    "variant" => "{$attributes}",
                    //"position" => $position++,
                    "quantity" => "{$quantity}"
                ];
            }
            if (count($list)) {
                $list = json_encode($list);
                return self::eventDatalayerPush('{
                    "pageCategory":"' . $pageCategory . '",
                    "ecommerce": {
                        "currencyCode":"' . $currency . '",
                        "checkout": {
                            "actionField":{
                                "step":' . $step . '
                            },
                            "products":' . $list . '
                        }
                    },
                    "event":"checkout",
                    "google_tag_params":{
                        "ecomm_pagetype":"cart",
                        "ecomm_prodid":["' . implode('","', $models_array) . '"],
                        "ecomm_totalvalue":' . number_format($totalvalue, 2, ".", "") . ',
                        "ecomm_totalvalue_tax_exc":' . number_format($totalvalue_exc, 2, ".", "") . '
                    }
                }');
            }
        }
        return;
    }

    public static function orderSuccess() {
        if (self::checkAPI())
            return;
        $currencies = \Yii::$container->get('currencies');
        $currency = \Yii::$app->settings->get('currency');
        try {
            $manager = \common\services\OrderManager::loadManager();
            $order = $manager->getOrderInstance();
            $products = $order->products;
        } catch (\Exception $ex) {
            return;
        }

        $_tax = $_total = $_shipping = $_coupon = 0;
        foreach ($order->totals as $totals) {
            if ($totals['class'] == 'ot_total') {
                $_total = number_format($totals['value_inc_tax'], 2, ".", "");
            } else if ($totals['class'] == 'ot_tax') {
                $_tax = number_format($totals['value'], 2, ".", "");
            } else if ($totals['class'] == 'ot_shipping') {
                $_shipping = number_format($totals['value_exc_vat'], 2, ".", "");
            } else if ($totals['class'] == 'ot_coupon') {
                $ex = explode(":", $totals['text']);
                if (isset($ex[1])) {
                    $_coupon = trim($ex[1]);
                }
            }
            $actionField = json_encode([
                'id' => $order->info['order_id'],
                'affiliation' => \common\classes\platform::name($order->info['platform_id']),
                'revenue' => $_total,
                'shipping' => $_shipping,
                'tax' => $_tax,
                    //'coupon' => ($_coupon ? $_coupon : ''),
            ]);
        }

        if (is_array($products)) {
            $group_properties_list = \common\models\Properties::find()
                ->alias('p')
                ->join('inner join',\common\models\PropertiesDescription::tableName()." pd", "pd.properties_id=p.properties_id AND pd.language_id='".(int)$order->info['language_id']."'")
                ->where(['p.products_groups'=>1])
                ->select(['pd.properties_name','p.properties_id'])
                ->orderBy(['p.sort_order'=>SORT_ASC,'pd.properties_id'=>SORT_ASC,])
                ->asArray()
                ->all();
            $group_properties_list = \yii\helpers\ArrayHelper::map($group_properties_list,'properties_id', 'properties_name');


            $list = [];
            $position = 1;
            $totalvalue = 0;
            $totalvalue_exc = 0;
            $models_array = array();
            foreach ($products as $product) {
                $brand = self::helperBrand($product['id']);
                $category_name = self::helperCategory($product['id']);
                $attributes = '';
                if (is_array($product['attributes']) && count($product['attributes'])) {
                    $map = [
                        'options' => \yii\helpers\ArrayHelper::getColumn($product['attributes'], 'option'),
                        'values' => \yii\helpers\ArrayHelper::getColumn($product['attributes'], 'value'),
                    ];
                    foreach ($map['options'] as $key => $value) {
                        $attributes .= $value . ": " . $map['values'][$key] . ", ";
                    }
                    if (strlen($attributes) > 0) {
                        $attributes = substr($attributes, 0, -2);
                    }
                }
                if ( count($group_properties_list)>0 ) {
                    $_prop_values = [];
                    foreach ( \common\models\Properties2Propducts::find()
                                  ->where(['products_id'=>(int)$product['id']])
                                  ->andWhere(['IN','properties_id',array_keys($group_properties_list)])
                                  ->select(['properties_id','values_id'])
                                  ->asArray()
                                  ->all() as $group_property){
                        $_prop_values[$group_property['properties_id']] = \common\helpers\Properties::get_properties_value($group_property['values_id'], (int)$order->info['language_id']);
                    }
                    foreach ($group_properties_list as $_propId=>$_propName){
                        if ( !isset($_prop_values[$_propId]) || !is_object($_prop_values[$_propId]) ) continue;
                        if ( !empty($attributes) ) $attributes.=', ';
                        $attributes = $_propName .": ". $_prop_values[$_propId]->values_text;
                    }
                }

                if (isset($product['tax'])) {
                    $products_tax = $product['tax'];
                } else {
                    $products_tax = \common\helpers\Tax::get_tax_rate($product['tax_class_id']);
                }
                $price = $currencies->calculate_price($product['final_price'], $products_tax);
                $price_tax_exc = $currencies->calculate_price($product['final_price'], 0);
                $quantity = $product['quantity'] ? $product['quantity'] : $product['qty'];

                $totalvalue += $price * $quantity;
                $totalvalue_exc += $price_tax_exc * $quantity;
                $models_array[] = $product['model'];

                $list[] = [
                    "id" => "{$product['model']}",
                    //"reference" => "{$product['model']}",
                    "name" => "{$product['name']}",
                    "price" => "{$price}",
                    //"price_tax_exc" => "{$price_tax_exc}",
                    "brand" => "{$brand}",
                    "category" => "{$category_name}",
                    "variant" => "{$attributes}",
                    //"position" => $position++,
                    "quantity" => "{$quantity}"
                ];
            }
            if (count($list)) {
                $list = json_encode($list);
                return self::eventDatalayerPush('{
                    "ecommerce":{
                        "currencyCode":"' . $order->info['currency'] . '",
                        "purchase":{
                            "actionField":' . $actionField . ',
                            "products":' . $list . '
                        }
                    }
                }');
            }
        }
        return;
    }

    static function onclickAddToCart ($products_id, $qty_js_elem = '') {
        if (self::checkAPI())
            return;
        $currencies = \Yii::$container->get('currencies');
        $currency = \Yii::$app->settings->get('currency');
        $product = Yii::$container->get('products')->getProduct($products_id);
        $brand = self::helperBrand($products_id);
        $category = self::helperCategory($products_id);
        $price = (isset($product['special_price']) && $product['special_price']?$product['special_price']:(isset($product['current_price']) && $product['current_price']?$product['current_price']:$product['products_price']));
        $products_tax = \common\helpers\Tax::get_tax_rate($product['products_tax_class_id']);
        $price_inc = $currencies->calculate_price($price, $products_tax);
        $price_exc = $currencies->calculate_price($price, 0);
        $event_datalayer = [
            'event' => 'addToCart',
            'ecommerce' => [
                'currencyCode' => $currency,
                'add' => [
                    'products' => [[
                        'name' => $product['products_name'],
                        'id' => $product['products_model'],
                        'price' => $price_inc,
                        'brand' => $brand,
                        'category' => $category,
                        //'variant' => '',
                        'quantity' => 1
                    ]]
                ]
            ]
        ];
        $event_datalayer_ready = htmlspecialchars(json_encode($event_datalayer));
        if (strlen($qty_js_elem) > 0) {
            $qty = htmlspecialchars('(parseInt(' . $qty_js_elem . ') > 0 ? parseInt(' . $qty_js_elem . ') : 1)');
        } else {
            $qty = '1';
        }
        return <<<EOD
    onclick="if (typeof dataLayer == 'object') { var addToCartLayer = {$event_datalayer_ready}; addToCartLayer.ecommerce.add.products[0].quantity = {$qty}; dataLayer.push(addToCartLayer); }"
EOD;
    }

    static function eventDatalayerPush($event_datalayer_ready) {
        return <<<EOD
        window.dataLayer = window.dataLayer || [];
        if (typeof dataLayer == 'object') {
            dataLayer.push({$event_datalayer_ready});
        }
EOD;
    }
}
