<?php

namespace solbianca\fias\models;

use solbianca\fias\console\traits\DeleteModelTrait;
use solbianca\fias\console\traits\UpdateModelTrait;
use yii\db\ActiveRecord;
use solbianca\fias\console\traits\ImportModelTrait;

/**
 * This is the model class for table "{{%fias_address_object}}".
 *
 * @property string $address_id
 * @property integer $address_level
 * @property integer $cent_status
 * @property string $prefix
 * @property string $title
 * <Классификационные коды>
 *     @property string $region_code
 *     @property string $auto_code
 *     @property string $area_code
 *     @property string $city_code
 *     @property string $ctar_code
 *     @property string $place_code
 *     @property string $street_code
 *     @property string $extr_code
 *     @property string $sext_code
 *     @property integer $postal_code
 * </Классификационные коды>
 * @property string $parent_id
 * @property string $plain_code
 * @property string $off_name
 * @property string $id
 *
 * @property FiasAddressObjectLevel $addressLevel
 * @property FiasAddressObject $parent
 * @property FiasAddressObject[] $fiasAddressObjects
 * @property FiasHouse[] $fiasHouses
 */
class FiasAddressObject extends ActiveRecord implements FiasModelInterface
{
    CONST XML_OBJECT_KEY = 'Object';

    use ImportModelTrait;
    use UpdateModelTrait;
    use DeleteModelTrait;

    /**
     * @return mixed|\yii\db\Connection
     */
    public static function getDb()
    {
        return \solbianca\fias\Module::db();
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'fias_address_object';
    }

    /**
     * @return string
     */
    public static function temporaryTableName()
    {
        return 'tmp_fias_address_object';
    }

    /**
     * @return array
     */
    public static function getXmlAttributes()
    {
        return [
            'AOGUID'     => 'address_id',
            'AOLEVEL'    => 'address_level',
            'CENTSTATUS' => 'cent_status',
            'SHORTNAME'  => 'prefix',
            'FORMALNAME' => 'title',
            // <Классификационные коды>
            'REGIONCODE' => 'region_code',
            'AREACODE'   => 'area_code',
            'AUTOCODE'   => 'auto_code',
            'CITYCODE'   => 'city_code',
            'CTARCODE'   => 'ctar_code',
            'PLACECODE'  => 'place_code',
            'STREETCODE' => 'street_code',
            'EXTRCODE'   => 'extr_code',
            'SEXTCODE'   => 'sext_code',
            // </Классификационные коды>
            'POSTALCODE' => 'postal_code',
            'PARENTGUID' => 'parent_id',
            // остальные нужны для очень редких специфических надобностей
            'OFFNAME'    => 'off_name',
            'PLAINCODE'  => 'plain_code',
            // "По этому коду отслеживается вся история изменений по адресному объекту." Зачем он здесь-то нужен? Нам aoguid нужен.
            'AOID'       => 'id',
        ];
    }

    /**
     * @return array
     */
    public static function getXmlFilters()
    {
        return [
            ['field' => 'ACTSTATUS', 'type' => 'eq', 'value' => 1],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['address_id', 'id', 'parent_id'], 'required'],
            [['address_level', 'postal_code', 'cent_status'], 'integer'],
            [['address_id', 'id', 'parent_id'], 'string', 'max' => 36],
            [
                [
                    'title',
                    'region_code',
                    'prefix',
                    'area_code',
                    'auto_code',
                    'city_code',
                    'ctar_code',
                    'place_code',
                    'street_code',
                    'extr_code',
                    'sext_code',
                    'plain_code',
                    'off_name',
                ],
                'string',
                'max' => 255
            ],
            [['address_id'], 'unique'],
            [
                ['address_level'],
                'exist',
                'skipOnError' => true,
                'targetClass' => FiasAddressObjectLevel::className(),
                'targetAttribute' => ['address_level' => 'id']
            ],
            [
                ['parent_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => FiasAddressObject::className(),
                'targetAttribute' => ['parent_id' => 'address_id']
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'address_id' => 'Address ID',
            'parent_id' => 'Parent ID',
            'address_level' => 'Address Level',
            'title' => 'Title',
            'postal_code' => 'Postal Code',
            'region_code' => 'Region',
            'prefix' => 'Prefix',
            'cent_status' => 'Статус центра',
            'off_name' => 'Off name',
        ];
    }

    /**
     * @return null|string
     */
    public function getBaseAddressLevel()
    {
        return FiasAddressObjectLevel::getBaseLevel($this->level);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddressLevel()
    {
        return $this->hasOne(FiasAddressObjectLevel::className(),
            ['level' => 'address_level', 'short_title' => 'prefix']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(FiasAddressObject::className(), ['address_id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFiasAddressObjects()
    {
        return $this->hasMany(FiasAddressObject::className(), ['parent_id' => 'address_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFiasHouses()
    {
        return $this->hasMany(FiasHouse::className(), ['address_id' => 'address_id']);
    }

    /**
     * Get full address for adres objecr
     *
     * @return string
     */
    public function getFullAddress()
    {
        $address = $this->getAddressRecursive();
        $addresses = explode(';', $address);
        $addresses = array_reverse($addresses);
        return implode(', ', $addresses);
    }

    /**
     * @return string
     */
    protected function getAddressRecursive()
    {
        $address = $this->replaceTitle();
        if (!empty($this->parent_id)) {
            $address .= ';' . $this->parent->getAddressRecursive();
        }
        return $address;
    }

    /**
     * Добавить отформатированный префикс к тайтлу
     *
     * @return string
     */
    protected function replaceTitle()
    {
        switch ($this->prefix) {
            case 'обл':
                return $this->title . ' область';
            case 'р-н':
                return $this->title . ' район';
            case 'проезд':
                return $this->title . ' проезд';
            case 'б-р':
                return $this->title . ' бульвар';
            case 'пер':
                return $this->title . ' переулок';
            case 'ал':
                return $this->title . ' аллея';
            case 'ш':
                return $this->title . ' шоссе';
            case 'г':
                return 'г. ' . $this->title;
            case 'линия':
                return 'линия ' . $this->title;
            case 'ул':
                return 'ул. ' . $this->title;
            case 'пр-кт':
                return $this->title . ' проспект';
            default:
                return trim($this->prefix . '. ' . $this->title);
        }
    }
}
