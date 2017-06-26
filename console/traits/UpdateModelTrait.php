<?php


namespace solbianca\fias\console\traits;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Console;
use solbianca\fias\Module as Fias;
use solbianca\fias\console\base\XmlReader;
use solbianca\fias\models\FiasModelInterface;

/**
 * @mixin ActiveRecord
 * @mixin FiasModelInterface
 */
trait UpdateModelTrait
{
    /**
     * @param XmlReader $reader
     * @param array|null $attributes
     */
    public static function updateRecords(XmlReader $reader, $attributes = null)
    {
        if (is_null($attributes)) {
            $attributes = static::getXmlAttributes();
        }
        static::processUpdateRows($reader, $attributes);
        static::updateCallback();
    }

    public static function processUpdateRows(XmlReader $reader, $attributes)
    {
        $tTableName = static::temporaryTableName();
        $tableName = static::tableName();

        Fias::db()->createCommand("DROP TABLE IF EXISTS {$tTableName};")->execute();
        Fias::db()->createCommand("CREATE TABLE {$tTableName} SELECT * FROM {$tableName} LIMIT 0;")->execute();
        Fias::db()->createCommand()->addColumn($tTableName, 'previous_id', 'char(36)')->execute();

        $count = 0;

        while ($data = $reader->getRows()) {
            $rows = [];
            foreach ($data as $row) {
                $rows[] = array_values($row);
            }
            if ($rows) {
                $count += Fias::db()->createCommand()->batchInsert($tTableName, array_values($attributes),
                    $rows)->execute();
                Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  "Inserted {$count} rows into tmp table.");
            }
        }

        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  "{$tableName}: Удаление устаревших записей ...");
        $count = Fias::db()->createCommand("DELETE old FROM {$tableName} AS old INNER JOIN {$tTableName} AS tmp
          ON (old.id = tmp.previous_id OR old.address_id = tmp.address_id OR old.id = tmp.id)")->execute();
        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  "Удалено: {$count}");

        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  "{$tableName}: Добавление измененных/новых записей ...");
        Fias::db()->createCommand()->dropColumn($tTableName, 'previous_id')->execute();
        $count = Fias::db()->createCommand("INSERT INTO {$tableName} SELECT tmp.* FROM {$tTableName} AS tmp")->execute();
        Console::output(Yii::$app->formatter->asDateTime(time(), 'php:Y-m-d H:i:s').' '.  "Добавлено: {$count}");
    }

    /**
     * After update callback
     */
    public static function updateCallback()
    {
        $tTableName = static::temporaryTableName();
        // подчищаем за собой
        Fias::db()->createCommand("DROP TABLE IF EXISTS {$tTableName}")->execute();
    }
}
