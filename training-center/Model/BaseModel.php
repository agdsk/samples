<?php

namespace AppBundle\Model;

use Serializable;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Validator\Constraints as Assert;

use Illuminate\Database\Capsule\Manager as Capsule;

class BaseModel extends Eloquent implements ContainerAwareInterface, Serializable
{
    use ContainerAwareTrait;

    public $timestamps = false;

    public function __set($key, $value)
    {
        $this->$key = $value;

        parent::__set($key, $value);
    }

    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = $this->newInstance([], true);

        $model->setRawAttributes((array)$attributes, true);

        $model->setConnection($connection ?: $this->connection);

        foreach ($attributes as $key => $value) {
            if ($key == 'password') {
                continue;
            }

            $model->$key = $value;
        }

        return $model;
    }

    public function serialize()
    {
        return serialize($this->attributes);
    }

    public function unserialize($attributes)
    {
        $this->attributes = unserialize($attributes);
    }

    public static function getTableName()
    {
        return with(new static)->getTable();
    }

    public static function dropdown($key = 'name')
    {
        $choices = [];

        foreach (Capsule::select('SELECT id,' . $key . ' FROM ' . self::getTableName()) as $Object) {
            $choices[$Object->{$key}] = $Object->id;
        }

        return $choices;
    }
}
