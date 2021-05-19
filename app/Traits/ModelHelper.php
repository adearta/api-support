<?php
namespace App\Traits;

use Exception;

trait ModelHelper
{
    public static function tableName()
    {
        return (new static)->table;
    }
}
