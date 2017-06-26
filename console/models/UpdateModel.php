<?php

/**
 * Обновление данных адресов в базе
 */

namespace solbianca\fias\console\models;

use Yii;
use yii\helpers\Console;
use solbianca\fias\console\base\XmlReader;
use solbianca\fias\Module as Fias;
use solbianca\fias\models\FiasUpdateLog;
use solbianca\fias\models\FiasHouse;
use solbianca\fias\models\FiasAddressObject;

class UpdateModel extends BaseModel
{
    /**
     * Download and unpack fias delta file
     *
     * @param $file
     * @param \solbianca\fias\console\base\Loader $loader
     * @param \solbianca\fias\console\base\SoapResultWrapper $fileInfo
     * @return \solbianca\fias\console\base\Directory
     */
    protected function getDirectory($file, $loader, $fileInfo)
    {
        if (null !== $file) {
            $directory = $loader->wrapDirectory(Yii::getAlias($file));
        } else {
            Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  "Загрузка последнего обновления (delta_file) БД ФИАС");
            $directory = $loader->loadUpdateFile($fileInfo);
        }

        return $directory;
    }

    /**
     * @throws \Exception
     */
    public function run()
    {
        return $this->update();
    }

    /**
     * Update fias data in base
     */
    public function update()
    {
        /** @var FiasUpdateLog $currentVersion */
        $currentVersion = FiasUpdateLog::find()->orderBy('id desc')->limit(1)->one();

        if (!$currentVersion) {
            Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'База не инициализирована, выполните команду: php yii fias/install');
            return;
        }

        //if (false === $this->loader->isUpdateRequired($currentVersion->version_id)) {
        /** Запрашиваем список данных обо всех обновлениях, выпущенных после той версии, что у нас */
        $updates = $this->loader->getAllFilesInfo($currentVersion->version_id);
        if (empty($updates)) {
            Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'База в актуальном состоянии');
            return;
        }

        /** идем по всем полученным обновлениям, применяем */
        foreach ($updates as $update) {
            $this->fileInfo = $update;
            $this->directory = $this->getDirectory($this->file, $this->loader, $this->fileInfo);
            $this->versionId = $this->getVersion($this->directory);

            Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  "Обновление с версии {$currentVersion->version_id} до {$this->fileInfo->getVersionId()}");
            $this->deleteFiasData();

            $transaction = Fias::db()->beginTransaction();

            try {
                Fias::db()->createCommand('SET foreign_key_checks = 0;')->execute();

                $this->updateAddressObject();

/** выпиливание номеров домов */
// таблица 50 млн записей, часами обновляется. Когда уже наверняка понадобится, тогда и включу
//                $this->updateHouse();

                $this->saveLog();

                Fias::db()->createCommand('SET foreign_key_checks = 1;')->execute();
                $transaction->commit();
                // меняем текущую версию для отображения
                $currentVersion->version_id = $this->fileInfo->getVersionId();
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }
    }

    /** в обновлении могут присутствовать списки удаляемых записей */
    private function deleteFiasData()
    {
        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'Удаление данных.');

        $deletedHouseFile = $this->directory->getDeletedHouseFile();
        if ($deletedHouseFile) {
            Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  "Удаление записей из таблицы " . FiasHouse::tableName() . ".");
            FiasHouse::remove(new XmlReader(
                $deletedHouseFile,
                FiasHouse::XML_OBJECT_KEY,
                array_keys(FiasHouse::getXmlAttributes()),
                FiasHouse::getXmlFilters()
            ));
        }

        $deletedAddressObjectsFile = $this->directory->getDeletedAddressObjectFile();
        if ($deletedAddressObjectsFile) {
            Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  "Удаление записей из таблицы " . FiasAddressObject::tableName() . ".");
            FiasAddressObject::remove(new XmlReader(
                $deletedAddressObjectsFile,
                FiasAddressObject::XML_OBJECT_KEY,
                array_keys(FiasAddressObject::getXmlAttributes()),
                FiasAddressObject::getXmlFilters()
            ));
        }
    }

    private function updateAddressObject()
    {
        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'Обновление адресов объектов');

        $attributes = FiasAddressObject::getXmlAttributes();
        $attributes['PREVID'] = 'previous_id';

        FiasAddressObject::updateRecords(new XmlReader(
            $this->directory->getAddressObjectFile(),
            FiasAddressObject::XML_OBJECT_KEY,
            array_keys($attributes),
            FiasAddressObject::getXmlFilters()
        ), $attributes);
    }

    private function updateHouse()
    {
        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  'Обновление домов');

        $attributes = FiasHouse::getXmlAttributes();
        $attributes['PREVID'] = 'previous_id';

        FiasHouse::updateRecords(new XmlReader(
            $this->directory->getHouseFile(),
            FiasHouse::XML_OBJECT_KEY,
            array_keys($attributes),
            FiasHouse::getXmlFilters()
        ), $attributes);
    }
}
