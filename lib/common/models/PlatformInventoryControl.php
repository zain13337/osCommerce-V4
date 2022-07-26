<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "platform_inventory_control".
 *
 * @property string $products_id
 * @property integer $platform_id
 * @property integer $current_quantity
 * @property integer $manual_quantity
 */
class PlatformInventoryControl extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'platform_inventory_control';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['products_id', 'platform_id'], 'required'],
            [['platform_id', 'current_quantity', 'manual_quantity'], 'integer'],
            [['products_id'], 'string', 'max' => 160]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'products_id' => 'Products ID',
            'platform_id' => 'Platform ID',
            'current_quantity' => 'Current Quantity',
            'manual_quantity' => 'Manual Quantity',
        ];
    }
}
