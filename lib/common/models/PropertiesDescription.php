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
namespace common\models;

use Yii;

/**
 * This is the model class for table "properties_description".
 */
class PropertiesDescription extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'properties_description';
    }


    public function getPropertiesUnit() {
      return $this->hasOne(PropertiesUnits::className(), ['properties_units_id' => 'properties_units_id'])
          ;
    }

}
