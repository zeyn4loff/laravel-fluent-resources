<?php

namespace Zeyn4loff\FluentResources\Traits;

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

    public function scope(string $relation, callable $callback): static
    {
        if (isset($this->modifiers['scopes'][$relation])) {
            $existing = $this->modifiers['scopes'][$relation];
            $this->modifiers['scopes'][$relation] = function($instance) use ($existing, $callback) {
                $existing($instance);
                $callback($instance);
            };
        } else {
            $this->modifiers['scopes'][$relation] = $callback;
        }
        return $this;
    }
}