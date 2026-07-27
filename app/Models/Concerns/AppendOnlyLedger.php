<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait AppendOnlyLedger
{
    /**
     * Boot the trait.
     */
    protected static function bootAppendOnlyLedger(): void
    {
        static::addGlobalScope('appendOnly', function (Builder $builder) {
            $where = [];
            foreach (static::getAppendOnlyColumns() as $column) {
                $where[$column] = 0;
            }

            $builder->where($where);
        });
    }

    /**
     * Get the columns that should be considered for append-only behavior.
     *
     * @return array
     */
    public static function getAppendOnlyColumns(): array
    {
        return ['updated_at'];
    }

    /**
     * Prevent updates on the model.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \RuntimeException(static::class . ' is append-only and cannot be updated.');
    }

    /**
     * Prevent deletes on the model.
     */
    public function delete(): bool|null
    {
        throw new \RuntimeException(static::class . ' is append-only and cannot be deleted.');
    }
}