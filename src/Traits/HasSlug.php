<?php

namespace HindBiswas\ModelUtils\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (! $model->{$model->slugField()} && ! empty($model->slugSource())) {
                $model->{$model->slugField()} = $model->generateSlug();
            }
        });

        static::updating(function ($model) {
            if (empty($model->slugSource())) {
                return;
            }
            if (! $model->updateSlugOnUpdate()) {
                return;
            }
            if (! $model->slugSourceIsDirty()) {
                return;
            }

            $model->{$model->slugField()} = $model->generateSlug();
        });

    }

    /**
     * Get if the slug must be unique across the entire table or just within a certain scope.
     * If true or empty array: the slug will be unique across the entire table.
     * If false: no uniqueness will be enforced.
     * If array of attribute names: the slug will be unique within the scope defined by those attributes.
     */
    protected function slugUniqueScope(): array|bool
    {
        return false;
    }

    /**
     * Get the name of the "slug" attribute.
     */
    protected function slugField(): string
    {
        return 'slug';
    }

    /**
     * Get the slug source value attribute.
     * This should return an array of attribute names that will be used to generate the slug.
     * The values of these attributes will be concatenated and used as the source for slug generation.
     */
    protected function slugSource(): array
    {
        return [];
    }

    /**
     * Get the maximum length for the slug field.
     */
    protected function maxSlugLength(): int
    {
        return 255;
    }

    /**
     * Get if the slugs updates on model updates.
     */
    protected function updateSlugOnUpdate(): bool
    {
        return false;
    }

    /**
     * Check if any of the slug source attributes have been modified.
     */
    protected function slugSourceIsDirty(): bool
    {
        $dirty = $this->getDirty();
        foreach ($this->slugSource() as $attribute) {
            if (isset($dirty[$attribute])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a slug already exists in the database.
     */
    protected function slugExists(string $slug): bool
    {
        $query = static::where($this->slugField(), $slug);

        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        $scope = $this->slugUniqueScope();
        if (is_array($scope)) {
            foreach ($scope as $attribute) {
                $query->where($attribute, $this->{$attribute});
            }
        }

        return $query->exists();
    }

    /**
     * Generate a unique slug based on the defined source attributes and uniqueness scope.
     */
    protected function generateSlug(): string
    {
        if (empty($this->slugSource())) {
            throw new \LogicException('Slug source attributes must be defined to generate a slug.');
        }

        $sourceValues = array_map(fn ($attr) => $this->{$attr}, $this->slugSource());
        $baseSlug = Str::slug(implode(' ', $sourceValues));
        $slug = Str::limit($baseSlug, $this->maxSlugLength(), '');

        $scope = $this->slugUniqueScope();
        if (! $scope) {
            return $slug;
        }

        $originalSlug = $slug;
        $counter = 1;
        while ($this->slugExists($slug)) {
            $suffix = '-'.$counter++;
            $slug = Str::limit($originalSlug, $this->maxSlugLength() - strlen($suffix), '').$suffix;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return $this->slugField();
    }
}
