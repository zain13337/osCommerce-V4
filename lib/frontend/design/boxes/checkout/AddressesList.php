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

namespace frontend\design\boxes\checkout;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;

class AddressesList extends Widget
{

    public $file;
    public $params;
    public $settings;
    public $manager;
    public $type; //shipping or billing
    public $mode;
    public $ab_id;

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        if (!is_object($this->manager)) throw new \Exception ('order manager should be defined');
        if (!in_array($this->mode, ['single', 'select', 'edit'])) throw new \Exception ('mode type should be defined');
        
        $this->params['manager'] = $this->manager;
        $this->params['type'] = $this->type;
        $this->params['mode'] = $this->mode;
        
        if ($this->type == 'shipping'){
            $_selectedABid = $this->manager->getSendto();
        } else {
            $_selectedABid = $this->manager->getBillto();
        }
        $this->settings[0]['address_in'] = $this->settings[0]['address_in'] ?? null;
        if (!$this->settings[0]['address_in']) {
            if ($this->type == 'shipping'){
                $this->settings[0]['address_in'] = \frontend\design\Info::widgetSettings('checkout\ShippingAddress', 'address_in', 'checkout');
            } else {
                $this->settings[0]['address_in'] = \frontend\design\Info::widgetSettings('checkout\BillingAddress', 'address_in', 'checkout');
            }
        }
        $this->params['selected_ab_id'] = $_selectedABid;
        //if (!$this->ab_id && $_selectedABid) $this->ab_id = $_selectedABid;
        
        if ($this->mode == 'single'){
            $this->params['address'] = $this->manager->getCustomersAddress($_selectedABid, true, true);
            $this->params['addresses'] = $this->manager->getCustomersAddresses(true, true);
            $this->_defineForm();
            if (is_null($this->params['address']) || !$this->params['model']->customerAddressIsReady() || $this->params['model']->hasErrors()){
                $this->params['mode'] = 'edit';
            }
        } else if ($this->mode == 'select'){
            $this->ab_id = $_selectedABid;
            $this->_defineForm();
            if (!$this->params['model']->customerAddressIsReady()){
                $this->params['mode'] = 'edit';
            } else {
                $this->params['addresses'] = $this->manager->getCustomersAddresses(true, true);
            }
        } else {
            $this->_defineForm();
        }
        
        if ($this->manager->get('is_multi') == 1) {
            $this->params['address']['firstname'] = $this->manager->get('customer_first_name');
            $this->params['address']['lastname'] = $this->manager->get('customer_last_name');
        }
        
        if ($this->params['mode'] == 'edit'){
            $this->params['postcoder'] = ($ext = \common\helpers\Acl::checkExtensionAllowed('AddressLookup')) ? $ext::getTool() : null;
        }

        return IncludeTpl::widget(['file' => 'boxes/checkout/addresses-list.tpl', 'params' => array_merge($this->params, ['settings' => $this->settings])]);
    }
    
    private function _defineForm(){
        if ($this->type == 'shipping'){
                $this->params['model'] = $this->manager->getShippingForm($this->ab_id);
            } else {
                $this->params['model'] = $this->manager->getBillingForm($this->ab_id);
            }
    }
}