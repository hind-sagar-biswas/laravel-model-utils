<?php

namespace HindBiswas\ModelUtils\Traits;

trait Archivable
{
    public function initializeArchivable(): void
    {
        $this->casts['archived_at'] = 'datetime';
    }

    public function getObservableEvents(): array
    {
        return array_merge(
            parent::getObservableEvents(),
            ['archiving', 'archived', 'restoring', 'restored']
        );
    }

    protected static function bootArchivable(): void
    {
        static::addGlobalScope('_hb_archived', function ($builder) {
            $builder->whereNull('archived_at');
        });
    }

    public function archive(): bool
    {
        if ($this->archived_at || $this->fireModelEvent('archiving') === false) {
            return false;
        }

        $this->archived_at = now();
        $this->save();

        $this->fireModelEvent('archived', false);

        return true;
    }

    public function restoreArchive(): bool
    {
        if (! $this->archived_at || $this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->archived_at = null;
        $this->save();

        $this->fireModelEvent('restored', false);

        return true;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeOnlyArchived($query)
    {
        return $query->withoutGlobalScope('_hb_archived')->whereNotNull('archived_at');
    }

    public function scopeWithArchived($query)
    {
        return $query->withoutGlobalScope('_hb_archived');
    }
}
