<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BaseModel extends Model
{
    public function toArray(): array
    {
        return $this->camelCaseArray(parent::toArray());
    }

    protected function camelCaseArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $newKey = Str::camel($key);

            $result[$newKey] = is_array($value)
                ? $this->camelCaseArray($value)
                : $value;
        }

        return $result;
    }
}