<?php

/**
 * Модель для импорта данных из базы fias в mysql базу
 */

namespace solbianca\fias\console\models;

use Yii;
use yii\helpers\Console;
use solbianca\fias\console\base\XmlReader;
use solbianca\fias\Module as Fias;
use solbianca\fias\models\FiasAddressObject;
use solbianca\fias\models\FiasAddressObjectLevel;
use solbianca\fias\models\FiasHouse;

class ImportModel extends BaseModel
{
    /**
     * @throws \Exception
     */
    public function run()
    {
        return $this->import();
    }

    /**
     * Import fias data in base
     *
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function import()
    {
        try {
            Fias::db()->createCommand('SET foreign_key_checks = 0;')->execute();

            $this->dropIndexes();

            $this->importAddressObjectLevel();

            $this->importAddressObject();

/** выпиливание номеров домов */
// таблица 50 млн записей, часами обновляется. Когда уже наверняка понадобится, тогда и включу
// пока пусть клиенты номер дома руками вводят.
//            $this->importHouse();

            $this->addIndexes();

            $this->saveLog();

            Fias::db()->createCommand('SET foreign_key_checks = 1;')->execute();

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Import fias address object
     */
    private function importAddressObject()
    {
        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'Импорт адресов объектов');
        FiasAddressObject::import(
            new XmlReader(
                $this->directory->getAddressObjectFile(),
                FiasAddressObject::XML_OBJECT_KEY,
                array_keys(FiasAddressObject::getXmlAttributes()),
                FiasAddressObject::getXmlFilters()
            )
        );
    }

    /**
     * Import fias house
     */
    private function importHouse()
    {
        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'Импорт домов');
        FiasHouse::import(new XmlReader(
            $this->directory->getHouseFile(),
            FiasHouse::XML_OBJECT_KEY,
            array_keys(FiasHouse::getXmlAttributes()),
            FiasHouse::getXmlFilters()
        ));
    }

    /**
     * Import fias address object levels
     */
    private function importAddressObjectLevel()
    {
        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'Импорт типов адресных объектов (условные сокращения и уровни подчинения)');
        FiasAddressObjectLevel::import(
            new XmlReader(
                $this->directory->getAddressObjectLevelFile(),
                FiasAddressObjectLevel::XML_OBJECT_KEY,
                array_keys(FiasAddressObjectLevel::getXmlAttributes()),
                FiasAddressObjectLevel::getXmlFilters()
            )
        );
    }

    /**
     * Get fias base version
     *
     * @param $directory \solbianca\fias\console\base\Directory
     * @return string
     */
    protected function getVersion($directory)
    {
        return $this->fileInfo->getVersionId();
    }

    /**
     * Сбрасываем индексы для ускорения заливки данных
     */
    protected function dropIndexes()
    {
        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'Сбрасываем индексы и ключи.');

        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'Сбрасываем внешние ключи.');

        Fias::db()->createCommand()->dropForeignKey('address_object_parent_id_fkey',
            '{{%fias_address_object}}')->execute();
        Fias::db()->createCommand()->dropForeignKey('fk_region_code_ref_fias_region',
            '{{%fias_address_object}}')->execute();
/** выпиливание номеров домов */
//Fias::db()->createCommand()->dropForeignKey('houses_parent_id_fkey', '{{%fias_house}}')->execute();

        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'Сбрасываем индексы.');
        Fias::db()->createCommand()->dropIndex('region_code', '{{%fias_address_object}}')->execute();
        Fias::db()->createCommand()->dropIndex('address_object_parent_id_fkey_idx', '{{%fias_address_object}}')->execute();
        Fias::db()->createCommand()->dropIndex('address_object_title_lower_idx', '{{%fias_address_object}}')->execute();
/** выпиливание номеров домов */
//Fias::db()->createCommand()->dropIndex('house_address_id_fkey_idx', '{{%fias_house}}')->execute();

        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'Сбрасываем основные ключи.');
        Fias::db()->createCommand()->dropPrimaryKey('pk', '{{%fias_address_object}}')->execute();
        Fias::db()->createCommand()->dropPrimaryKey('pk', '{{%fias_address_object_level}}')->execute();
/** выпиливание номеров домов */
//Fias::db()->createCommand()->dropPrimaryKey('pk', '{{%fias_house}}')->execute();

    }

    /**
     * Восстанавливаем индексы
     */
    protected function addIndexes()
    {
        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'Добавляем к данным индексы и ключи.');

        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'Создаем основные ключи.');

/** выпиливание номеров домов */
//Fias::db()->createCommand()->addPrimaryKey('pk', '{{%fias_house}}', 'id')->execute();

        Fias::db()->createCommand()->addPrimaryKey('pk', '{{%fias_address_object}}', 'address_id')->execute();
        Fias::db()->createCommand()->addPrimaryKey('pk', '{{%fias_address_object_level}}', ['title', 'code'])->execute();

        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'Добавляем индексы.');
        Fias::db()->createCommand()->createIndex('region_code', '{{%fias_address_object}}', 'region_code')->execute();

/** выпиливание номеров домов */
//Fias::db()->createCommand()->createIndex('house_address_id_fkey_idx', '{{%fias_house}}', 'address_id')->execute();

        Fias::db()->createCommand()->createIndex('address_object_parent_id_fkey_idx',
            '{{%fias_address_object}}',
            'parent_id')->execute();
        Fias::db()->createCommand()->createIndex('address_object_title_lower_idx', '{{%fias_address_object}}', 'title')->execute();

        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'Добавляем внешние ключи');

/** выпиливание номеров домов */
//Fias::db()->createCommand()->addForeignKey('houses_parent_id_fkey', '{{%fias_house}}', 'address_id', '{{%fias_address_object}}', 'address_id', 'CASCADE', 'CASCADE')->execute();

        Fias::db()->createCommand()->addForeignKey('address_object_parent_id_fkey', '{{%fias_address_object}}',
            'parent_id',
            '{{%fias_address_object}}', 'address_id', 'CASCADE', 'CASCADE')->execute();
        Fias::db()->createCommand()->addForeignKey('fk_region_code_ref_fias_region', '{{%fias_address_object}}',
            'region_code',
            '{{%fias_region}}', 'code', 'NO ACTION', 'NO ACTION')->execute();
    }
}
