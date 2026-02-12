<?php

namespace Zeyn4loff\FluentResources\Traits;

trait HasTranslations
{
    protected array $translatableFields = [];
    protected ?string $translationResource = null;

    protected function applyTranslationLogic(array $data, $request): array
    {
        $shouldReplace = $this->modifiers['replaceWithTranslation'] ?? false;

        if (!$shouldReplace || !method_exists($this->resource, 'translations')) {
            return $data;
        }

        if (!$this->resource->relationLoaded('translations')) {
            return $data;
        }

        $translations = $this->resource->translations;
        $defaultLang = $this->getDefaultLanguageId();
        $targetLang = $this->modifiers['languageId'] ?? $request->get('language_id') ?? $defaultLang;

        $translation = $translations->firstWhere('language_id', $targetLang)
            ?? $translations->firstWhere('language_id', $defaultLang);

        if ($translation) {
            foreach ($this->translatableFields as $field) {
                if (isset($translation->{$field})) {
                    $data[$field] = $translation->{$field};
                }
            }
        }

        if (($this->modifiers['includeAllTranslations'] ?? false) && $this->translationResource) {
            $data['translations'] = $translations->isEmpty()
                ? null
                : $this->translationResource::collection($translations);
        }

        return $data;
    }

    protected function copyTranslationState($instance): void
    {
        $instance->translatableFields = $this->translatableFields;
        $instance->translationResource = $this->translationResource;
    }

    public function replaceTranslatableFields(): static { $this->modifiers['replaceWithTranslation'] = true; return $this; }
    public function withAllTranslations(): static { $this->modifiers['includeAllTranslations'] = true; return $this; }
    public function setLanguageId(?int $id): static { $this->modifiers['languageId'] = $id; return $this; }
    public function setTranslatableFields(array $fields): static { $this->translatableFields = $fields; return $this; }
    public function setTranslationResource(string $class): static { $this->translationResource = $class; return $this; }
}