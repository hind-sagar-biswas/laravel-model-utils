<?php

namespace HindBiswas\ModelUtils\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Optionable
{
    /**
     * The column name to use as the option value.
     */
    protected static function optionValue(): string
    {
        return 'id';
    }

    /**
     * The column name to use as the option label.
     */
    protected static function optionLabel(): string
    {
        return 'name';
    }

    public function scopeGetOptions(Builder $query): array
    {
        $value = static::optionValue();
        $label = static::optionLabel();

        return $query->select([$value, $label])->get()->map(fn ($item) => [
            'value' => $item->{$value},
            'label' => $item->{$label},
        ])->values()->toArray();
    }

    public static function options(): array
    {
        return static::query()->getOptions();
    }
}
