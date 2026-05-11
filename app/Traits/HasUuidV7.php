<?php

namespace App\Traits;

use Ramsey\Uuid\Uuid;

trait HasUuidV7
{
    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }

    protected static function bootHasUuidV7()
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Uuid::uuid7()->toString();
            }
        });
    }
}
