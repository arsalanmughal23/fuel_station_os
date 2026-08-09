<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait AppendOnlyLedger
{
    /**
     * Boot the trait.
     */
    protected static function bootAppendOnlyLedger(): void
    {
        static::updating(function (Model $model) {
            throw new \RuntimeException(static::class.' is append-only and cannot be updated.');
        });

        static::deleting(function (Model $model) {
            throw new \RuntimeException(static::class.' is append-only and cannot be deleted.');
        });
    }

    /**
     * Prevent updates on the model.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \RuntimeException(static::class.' is append-only and cannot be updated.');
    }

    /**
     * Prevent deletes on the model.
     */
    public function delete(): ?bool
    {
        throw new \RuntimeException(static::class.' is append-only and cannot be deleted.');
    }
}
