<?php

namespace solbianca\fias;

use Yii;

/**
 * Class Module
 * @package solbianca\fias
 *
 * @property string $directory
 */
class Module extends \yii\base\Module
{
    /**
     * Database
     *
     * @var \yii\db\Connection
     */
    public $db;
    /** @var \yii\db\Connection */
    static $dbStatic;

    /**
     * Directory for fias files
     *
     * @var string
     */
    private $directory;

    /**
     * @inherit
     */
    public function init()
    {
        parent::init();

        $this->directory = Yii::getAlias('@app/runtime/fias');
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param string $value
     */
    public function setDirectory($value)
    {
        $this->directory = $value;
    }

    /**
     * @return mixed|\yii\db\Connection
     */
    public function getDb()
    {
        if (!isset($this->db)) {
            $this->db = \Yii::$app->db;
        }

        return $this->db;
    }

    /**
     * @param string|\yii\db\Connection $value
     */
    public function setDb($value)
    {
        if (is_string($value)) {
            $this->db = \Yii::$app->$value;
        } else {
            $this->db = $value;
        }
        self::$dbStatic = $this->db;
    }

    /**
     * Для статических методов
     * @return mixed|\yii\db\Connection
     */
    public static function db()
    {
        if (empty(self::$dbStatic)) {
            if (!empty(\Yii::$app->getModule('fias')->db)) {
                self::$dbStatic = \Yii::$app->{\Yii::$app->getModule('fias')->db};
            } else {
                self::$dbStatic = \Yii::$app->db;
            }
        }

        return self::$dbStatic;
    }
}
