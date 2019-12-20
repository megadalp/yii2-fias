<?php

use yii\db\Schema;
use yii\db\Migration;

/**
 * Class m160711_062538_fias_tables
 */
class m160711_062538_fias_tables extends Migration
{
    public function init()
    {
        $this->db = \solbianca\fias\Module::db();
        parent::init();
    }

    /**
     * @param $tableName
     * @param $db string as config option of a database connection
     * @return bool table exists in schema
     */
    private $tableNames;
    private function tableExists($tableName)
    {
        $dbConnect = $this->db;

        if (!($dbConnect instanceof \yii\db\Connection)) {
            throw new \yii\base\InvalidParamException;
        }

        if (empty($this->tableNames)) {
            $this->tableNames = $dbConnect->schema->getTableNames();
        }

        return in_array($tableName, $this->tableNames);
    }

    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

/** выпиливание номеров домов */
//if (!$this->tableExists('fias_house')) {
//    $this->createTable(
//        '{{%fias_house}}', [
//        'id'          => $this->char(36)->notNull()->comment('Идентификационный код записи'),
//        'house_id'    => $this->char(36)->notNull()->comment('Идентификационный код дома'),
//        'address_id'  => $this->char(36)->comment('Идентификационный код адресного объекта'),
//        'number'      => $this->string()->comment('Номер дома'),
//        'building'    => $this->string()->comment('Корпус'),
//        'structure'   => $this->string()->comment('Строение'),
//        'postal_code' => $this->string()->comment('Индекс'),
//        'oktmo'       => $this->string()->comment('Код по справочнику ОКТМО'),
//        'okato'       => $this->string()->comment('Код по справочнику ОКАТО'),
//        'ifnsul'      => $this->integer()->comment('Код ИФНС ЮЛ'),
//        'ifnsfl'      => $this->integer()->comment('Код ИФНС ФЛ'),
//    ], $tableOptions
//    );
//
//    $this->addPrimaryKey('pk', '{{%fias_house}}', 'id');
//    $this->createIndex('house_address_id_fkey_idx', '{{%fias_house}}', 'address_id');
//}

        if (!$this->tableExists('fias_address_object')) {
            $this->createTable(
                '{{%fias_address_object}}', [
                'address_id'    => $this->char(36)->notNull()->comment('Уникальный идентификационный код адресного объекта (aoguid)'),
                /**
                 * Уровень адресного объекта" содержит номер уровня классификации адресных объектов.
                 * Перечень уровней адресных объектов и соответствующих им типов адресных объектов
                 * определен в таблице SOCRBASE ФИАС.
                 * Условно выделены следующие уровни адресных объектов:
                 *      1 – уровень региона
                 *      2 – уровень автономного округа
                 *      3 – уровень района
                 *      4 – уровень города
                 *      5 – уровень внутригородской территории
                 *      6 – уровень населенного пункта
                 *      7 – уровень улицы
                 *     90 – уровень дополнительных территорий
                 *     91 – уровень подчиненных дополнительным территориям объектов
                 *     (остальные см. в таблице fias_address_object_level)
                 */
                'address_level' => $this->integer()->comment('Уровень объекта по ФИАС (aolevel)'),
                /**
                 * Статус центра (по состоянию на 07.2017)
                 *     0 Объект не является центром административно-территориального образования
                 *     1 Объект является центром района
                 *     2 Объект является центром (столицей) региона
                 *     3 Объект является одновременно и центром района и центром региона
                 *     4 Центральный район, т.е. район, в котором находится центр региона (только для объектов 2-го уровня)
                 */
                'cent_status'   => $this->integer()->notNull()->defaultValue('0')->comment('Статус центра (centstatus)'),
                'prefix'        => $this->char(10)->comment('г, д, тер, ул и так далее (shortname)'),
                'title'         => $this->string(120)->comment('Наименование объекта (formalname)'),
                // <Классификационные коды>
                /**
                 * Классификационный код адресного объекта отражает иерархию его подчиненности
                 * и выделяет его среди объектов данного уровня, подчиненных одному и тому же старшему объекту.
                 *
                 * Классификационный код любого адресного объекта, начиная от регионов
                 * и заканчивая элементами улично-дорожной сети, планировочной структуры дополнительного адресного элемента,
                 * представляется в следующем виде:
                 *     СС А РРР ГГГ ВВВ ППП УУУУ ЭЭЭЭ ЦЦЦ
                 *     где
                 *         СС   - region     – код субъекта Российской Федерации (региона) [1];
                 *         А    - areacode   – код округа в составе субъекта Российской Федерации(региона);
                 *         РРР  - autocode   – код района;
                 *         ГГГ  - citycode   – код города (код сельского поселения [2]);
                 *         ВВВ  - ctarcode   – код внутригородского района;
                 *         ППП  - placecode  – код населенного пункта;
                 *         УУУУ - streetcode – код улицы, планировочной единицы территории);
                 *         ЭЭЭЭ - extrcode   – код дополнительного адресообразующего элемента;
                 *         ЦЦЦ  - sextcode   – код подчиненного адресного объекта дополнительного адресообразующего элемента;
                 *
                 * Таким образом, каждому уровню классификации соответствует фасет кода.
                 * Для объектов классификации верхних уровней фасеты кода объектов 5 нижних уровней будут иметь нулевые значения.
                 * В случае подчинённости адресного объекта старшему объекту через несколько уровней иерархии
                 * фасеты кода объектов, соответствующих промежуточным уровням, должны быть нулевыми.
                 * Например, улица может быть привязана непосредственно к субъекту Российской Федерации
                 * (для городов Москва и Санкт-Петербург), при этом фасеты кода, соответствующие уровням
                 * округов, районов, городов и населенных пунктов, будут содержать нули.
                 *
                 *     [1] Коды регионов представлены в Приложении 3 к составу сведений
                 *     [2] Указание сельского поселения при адресации объектов применяется только в случае совпадения
                 *       наименования и типа населенного пункта в пределах одного административного района.
                 */
                'region'        => $this->char(2)->notNull()->defaultValue('00')->comment('Регион'),
                'area_code'     => $this->char(1)->notNull()->defaultValue('0')->comment('Код района'),
                'auto_code'     => $this->char(3)->notNull()->defaultValue('000')->comment('Код автономии'),
                'city_code'     => $this->char(3)->notNull()->defaultValue('000')->comment('Код города'),
                'ctar_code'     => $this->char(3)->notNull()->defaultValue('000')->comment('Код внутригородского района'),
                'place_code'    => $this->char(3)->notNull()->defaultValue('000')->comment('Код населённого пункта'),
                'street_code'   => $this->char(4)->notNull()->defaultValue('0000')->comment('Код улицы'),
                'extr_code'     => $this->char(4)->notNull()->defaultValue('0000')->comment('Код дополнительного адресообразующего элемента'),
                'sext_code'     => $this->char(3)->notNull()->defaultValue('000')->comment('Код подчиненного дополнительного адресообразующего элемента'),
                // </Классификационные коды>
                'postal_code'   => $this->char(6)->defaultValue('')->comment('Почтовый индекс'),
                'parent_id'     => $this->char(36)->notNull()->comment('Идентификационный код родительского адресного объекта'),
                // остальные нужны для очень редких специфических надобностей
                'off_name'      => $this->string(120)->comment('Официальное наименование объекта (offname)'),
                'plain_code'    => $this->char(15)->comment('Код адресного объекта из КЛАДР 4.0 одной строкой без признака актуальности'),
                // "По этому коду отслеживается вся история изменений по адресному объекту." Зачем он здесь-то нужен? Нам aoguid нужен.
                'id'            => $this->char(36)->notNull()->unique()->comment('Идентификационный код записи (aoid)'),
            ], $tableOptions
            );

/** Изменение колонок, причина - ФИАС изменила свои данные. */
# 1. У всех перечисленных убран NOT NULL
# 2. Увеличена длина:
#     area_code - 3
#     auto_code - 3
$this->execute( "
    ALTER TABLE fias_address_object
      MODIFY `cent_status` int(11) DEFAULT 0 COMMENT 'Статус центра (centstatus)',
      MODIFY `region` char(2) DEFAULT '00' COMMENT 'Регион',
      MODIFY `area_code` char(3) DEFAULT '000' COMMENT 'Код района',
      MODIFY `auto_code` char(3) DEFAULT '000' COMMENT 'Код автономии',
      MODIFY `city_code` char(3) DEFAULT '000' COMMENT 'Код города',
      MODIFY `ctar_code` char(3) DEFAULT '000' COMMENT 'Код внутригородского района',
      MODIFY `place_code` char(3) DEFAULT '000' COMMENT 'Код населённого пункта',
      MODIFY `street_code` char(4) DEFAULT '0000' COMMENT 'Код улицы',
      MODIFY `extr_code` char(4) DEFAULT '0000' COMMENT 'Код дополнительного адресообразующего элемента',
      MODIFY `sext_code` char(3) DEFAULT '000' COMMENT 'Код подчиненного дополнительного адресообразующего элемента',
      MODIFY `parent_id` char(36) DEFAULT NULL COMMENT 'Идентификационный код родительского адресного объекта'
    ;
" );

            $this->addPrimaryKey('pk', '{{%fias_address_object}}', 'address_id');
            $this->createIndex('address_object_address_id_fkey_idx', '{{%fias_address_object}}', 'address_id');
            $this->createIndex('address_object_parent_id_fkey_idx', '{{%fias_address_object}}', 'parent_id');
            $this->createIndex('address_object_title_lower_idx', '{{%fias_address_object}}', 'title');
            $this->createIndex('address_object_level_regioncode_title_idx', '{{%fias_address_object}}', ['address_level', 'region', 'title']);
            // вроде не надо? $this->createIndex('address_object_id_idx', '{{%fias_address_object}}', 'id');
        }

        if (!$this->tableExists('fias_address_object_level')) {
            $this->createTable(
                '{{%fias_address_object_level}}', [
                'title' => $this->string()->comment('Описание уровня'),
                'code'  => $this->string()->comment('Код уровня'),
            ], $tableOptions
            );

            $this->addPrimaryKey('pk', '{{%fias_address_object_level}}', ['title', 'code']);
        }

        if (!$this->tableExists('fias_update_log')) {
            $this->createTable(
                '{{%fias_update_log}}', [
                'id'         => $this->primaryKey(),
                'version_id' => $this->integer()->unique()->notNull()->comment('ID версии, полученной от базы ФИАС'),
                'created_at' => $this->integer()->notNull(),
            ], $tableOptions
            );
        }

        if (!$this->tableExists('fias_region')) {
            $this->createTable(
                '{{%fias_region}}', [
                'id'    => $this->string()->comment('Номер региона'),
                'title' => $this->string()->comment('Название региона'),
            ], $tableOptions
            );

            $this->addPrimaryKey('pk', '{{%fias_region}}', 'id');
        }

/** выпиливание номеров домов */
//$this->addForeignKey('houses_parent_id_fkey', '{{%fias_house}}', 'address_id', '{{%fias_address_object}}',
//    'address_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('address_object_parent_id_fkey', '{{%fias_address_object}}', 'parent_id',
            '{{%fias_address_object}}', 'address_id', 'CASCADE', 'CASCADE');
    }

    public function down()
    {
        $this->execute( 'SET FOREIGN_KEY_CHECKS = 0' );
/** выпиливание номеров домов */
//$this->dropTable('{{%fias_house}}');
        $this->dropTable('{{%fias_address_object}}');
        $this->dropTable('{{%fias_address_object_level}}');
        $this->dropTable('{{%fias_update_log}}');
        $this->dropTable('{{%fias_region}}');
        $this->execute( 'SET FOREIGN_KEY_CHECKS = 1' );
    }
}
