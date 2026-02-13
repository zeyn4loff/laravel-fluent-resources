<?php

namespace Zeyn4loff\FluentResources\Traits;

use Closure;

trait HasFluentScopes
{
    public function hide(array $fields): static
    {
        foreach ($fields as $field) {
            if (str_contains($field, '.')) {
                [$relation, $rest] = explode('.', $field, 2);
                $this->scope($relation, fn($instance) => $instance->hide([$rest]));
            } else {
                $this->modifiers['hiddenFields'][] = $field;
            }
        }
        return $this;
    }

    public function show(array $fields): static
    {
        foreach ($fields as $field) {
            if (str_contains($field, '.')) {
                [$relation, $rest] = explode('.', $field, 2);
                $this->scope($relation, fn($instance) => $instance->show([$rest]));
            } else {
                $this->modifiers['showFields'][] = $field;
            }
        }
        return $this;
    }

    public function scope(string $relation, Closure $callback): static
    {
        $existing = $this->modifiers['scopes'][$relation] ?? null;

        $this->modifiers['scopes'][$relation] = function ($instance) use ($existing, $callback) {
            if ($existing) $existing($instance);
            $callback($instance);
        };

        return $this;
    }
}