<?php


namespace Mephiztopheles\webf\Model;


class History extends Model {

    public static $tableName = "history";

    private $date;
    private $table;
    private $type;
    private $user;

    protected static $dates = [ "date" ];

    public static function getTable() {
        return self::$tableName;
    }
}