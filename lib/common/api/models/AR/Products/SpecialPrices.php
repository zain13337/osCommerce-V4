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

namespace common\api\models\AR\Products;


use common\api\models\AR\EPMap;

class SpecialPrices extends EPMap
{

    protected $hideFields = [
        'specials_id',
        'groups_id',
        'currencies_id',
    ];

    /**
     * @var Special
     */
    protected $parentObject;

    public static function tableName()
    {
        return 'specials_prices';
    }

    public static function primaryKey()
    {
        return ['specials_id','currencies_id','groups_id'];
    }

    public static function getAllKeyCodes()
    {
        $keyCodes = [];
        if (defined('USE_MARKET_PRICES') && USE_MARKET_PRICES == 'True') {
            foreach (\common\helpers\Currencies::get_currencies() as $currency){
                $keyCode = $currency['code'] . '_0';
                $keyCodes[$keyCode] = [
                    'specials_id' => null,
                    'groups_id' => 0,
                    'currencies_id' => $currency['currencies_id'],
                ];
                if ( defined('CUSTOMERS_GROUPS_ENABLE') && CUSTOMERS_GROUPS_ENABLE=='True' ) {
                    foreach (\common\helpers\Group::get_customer_groups() as $groupInfo) {
                        $keyCode = $currency['code'] . '_' . $groupInfo['groups_id'];
                        $keyCodes[$keyCode] = [
                            'specials_id' => null,
                            'groups_id' => $groupInfo['groups_id'],
                            'currencies_id' => $currency['currencies_id'],
                        ];
                    }
                }
            }
        }else{
            if ( defined('CUSTOMERS_GROUPS_ENABLE') && CUSTOMERS_GROUPS_ENABLE=='True' ) {
                $keyCodes[\common\helpers\Currencies::systemCurrencyCode().'_0'] = [
                    'specials_id' => null,
                    'groups_id' => 0,
                    'currencies_id' => 0,
                ];
                foreach (\common\helpers\Group::get_customer_groups() as $groupInfo) {
                    $keyCode = \common\helpers\Currencies::systemCurrencyCode() . '_' . $groupInfo['groups_id'];
                    $keyCodes[$keyCode] = [
                        'specials_id' => null,
                        'groups_id' => $groupInfo['groups_id'],
                        'currencies_id' => 0,
                    ];
                }
            }
        }
        return $keyCodes;
    }

    public function parentEPMap(EPMap $parentObject)
    {
        $this->specials_id = $parentObject->specials_id;
        $this->parentObject = $parentObject;
    }

    public function beforeSave($insert)
    {
        if ( $insert ) {
            if (is_null($this->specials_new_products_price)) {
                $this->specials_new_products_price = -2;
                if ($this->groups_id==0 && $this->currencies_id==0 && is_object($this->parentObject)) {
                    $this->specials_new_products_price = $this->parentObject->specials_new_products_price;
                }
            }
        }else{
            if ($this->groups_id==0 && $this->currencies_id==0 && is_object($this->parentObject)) {
                $this->specials_new_products_price = $this->parentObject->specials_new_products_price;
            }
        }
        return parent::beforeSave($insert);
    }

}