<?php

namespace Zeyn4loff\FluentResources\Traits;

use Illuminate\Http\Request;

trait HasTranslations
{
    protected array $translatableFields = [];
    protected ?string $translationResource = null;

    protected function applyTranslationLogic(array $data, Request $request): array
    {
        $shouldReplace = $this->modifiers['replaceWithTranslation'] ?? config('fluent-resources.auto_replace_translations');

        if (!$shouldReplace || !method_exists($this->resource, 'translations')) {
            return $data;
        }

        if (!$this->resource->relationLoaded('translations')) {
            $this->resource->loadMissing('translations');
        }

        $translations = $this->resource->translations;
        $langKey = config('fluent-resources.language_key', 'language_id');
        $defaultLang = config('fluent-resources.default_language_id', 1);
        $targetLang = $this->modifiers['languageId'] ?? $request->get($langKey) ?? $defaultLang;

        $translation = $translations->firstWhere($langKey, $targetLang)
            ?? $translations->firstWhere($langKey, $defaultLang);

        if ($translation) {
            foreach ($this->translatableFields as $field) {
                if (isset($translation->{$field})) {
                    $data[$field] = $translation->{$field};
                }
            }
        }

        if (($this->modifiers['includeAllTranslations'] ?? false) && $this->translationResource) {
            $data['translations'] = $translations->isEmpty() ? null : ($this->translationResource)::collection($translations);
        }

        return $data;
    }

    public function copyTranslationState($instance): void
    {
        $instance->translatableFields = $this->translatableFields;
        $instance->translationResource = $this->translationResource;
    }

    public function replaceTranslatableFields(bool $val = true): static { $this->modifiers['replaceWithTranslation'] = $val; return $this; }
    public function withAllTranslations(bool $val = true): static { $this->modifiers['includeAllTranslations'] = $val; return $this; }
    public function setLanguageId(?int $id): static { $this->modifiers['languageId'] = $id; return $this; }
    public function setTranslatableFields(array $fields): static { $this->translatableFields = $fields; return $this; }
    public function setTranslationResource(string $class): static { $this->translationResource = $class; return $this; }
}