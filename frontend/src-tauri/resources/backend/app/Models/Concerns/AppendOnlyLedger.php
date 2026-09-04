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
            // Allow models to opt-in to limited updates by defining
            // a `$appendOnlyAllowAttributes` array of attribute names.
            $allowed = $model->appendOnlyAllowAttributes ?? [];

            if (!is_array($allowed) || empty($allowed)) {
                throw new \RuntimeException(static::class.' is append-only and cannot be updated.');
            }

            $dirty = array_keys($model->getDirty());
            $disallowed = array_diff($dirty, $allowed);

            if (!empty($disallowed)) {
                throw new \RuntimeException(static::class.' is append-only. Attempted to update disallowed attributes: '.implode(',', $disallowed));
            }
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
        $allowed = $this->appendOnlyAllowAttributes ?? [];

        if (!is_array($allowed) || empty($allowed)) {
            throw new \RuntimeException(static::class.' is append-only and cannot be updated.');
        }

        $disallowed = array_diff(array_keys($attributes), $allowed);

        if (!empty($disallowed)) {
            throw new \RuntimeException(static::class.' is append-only. Attempted to update disallowed attributes: '.implode(',', $disallowed));
        }

        return parent::update($attributes, $options);
    }

    /**
     * Prevent deletes on the model.
     */
    public function delete(): ?bool
    {
        throw new \RuntimeException(static::class.' is append-only and cannot be deleted.');
    }
}
