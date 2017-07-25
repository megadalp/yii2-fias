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
    private static $directoryStatic;

    public $unrarCommand = '/usr/local/bin/unrar';
    static $unrarCommandStatic;

    /**
     * @inherit
     */
    public function init()
    {
        parent::init();
        if (!isset($this->directory)) {
            $this->directory = Yii::getAlias('@app/runtime/fias');
        }
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        if (!isset($this->directory)) {
            $this->directory = Yii::getAlias('@app/runtime/fias');
        }
        return $this->directory;
    }

    /**
     * @param string $value
     */
    public function setDirectory($value)
    {
        $this->directory = $value;
        self::$directoryStatic = $this->directory;
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
     * @return mixed|\yii\db\Connection
     */
    public function getUnrarCommand()
    {
        return $this->unrarCommand;
    }

    /**
     * @param string|\yii\db\Connection $value
     */
    public function setUnrarCommand($value)
    {
        $this->unrarCommand = $value;
        self::$unrarCommandStatic = $this->unrarCommand;
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
    /**
     * Для статических методов
     * @return string
     */
    public static function directory()
    {
        if (empty(self::$directoryStatic)) {
            if (!empty(Yii::$app->getModule('fias')->directory)) {
                self::$directoryStatic = Yii::$app->getModule('fias')->directory;
            } else {
                self::$directoryStatic = Yii::getAlias('@app/runtime/fias');
            }
        }

        return self::$directoryStatic;
    }
    /**
     * Для статических методов
     * @return string
     */
    public static function unrarCommand()
    {
        if (empty(self::$unrarCommandStatic)) {
            if (!empty(Yii::$app->getModule('fias')->unrarCommand)) {
                self::$unrarCommandStatic = Yii::$app->getModule('fias')->unrarCommand;
            } else {
                self::$unrarCommandStatic = Yii::getAlias('@app/runtime/fias');
            }
        }

        return self::$unrarCommandStatic;
    }
}
