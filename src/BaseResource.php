<?php

namespace Zeyn4loff\FluentResources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Zeyn4loff\FluentResources\Traits\HasTranslations;
use Zeyn4loff\FluentResources\Traits\HasFluentScopes;

class BaseResource extends JsonResource
{
    use HasTranslations, HasFluentScopes;

    protected array $modifiers = [
        'hiddenFields' => [],
        'showFields' => [],
        'fieldOrder' => ['id'],
        'isExternal' => false,
        'scopes' => [],
        'replaceWithTranslation' => false,
        'includeAllTranslations' => false,
        'languageId' => null,
    ];

    protected array $defaultHiddenFields = ['created_at', 'updated_at'];
    protected array $defaultExternalHiddenFields = [];
    protected $originalCollectionResource = null;

    public function toArray($request): array
    {
        $data = $this->transformData(parent::toArray($request));
        $data = $this->applyTranslationLogic($data, $request);
        $data = $this->filterFields($data);
        return $this->sortFields($data);
    }

    protected function transformData(array $data): array { return $data; }

    protected function nested(string $class, string $relation)
    {
        $data = $this->resource->{$relation};
        if (!$data) return null;

        $instance = ($data instanceof Collection || $data instanceof \Illuminate\Pagination\AbstractPaginator)
            ? $class::makeCollection($data)
            : new $class($data);

        // Наследование состояния
        $instance->modifiers['languageId'] = $this->modifiers['languageId'];
        $instance->modifiers['isExternal'] = $this->modifiers['isExternal'];
        $instance->modifiers['replaceWithTranslation'] = $this->modifiers['replaceWithTranslation'];

        if (isset($this->modifiers['scopes'][$relation])) {
            $this->modifiers['scopes'][$relation]($instance);
        }

        return (method_exists($instance, 'applyToCollection'))
            ? $instance->applyToCollection()
            : $instance;
    }

    protected function filterFields(array $data): array
    {
        $hidden = array_unique(array_merge(
            $this->defaultHiddenFields,
            $this->modifiers['hiddenFields'],
            $this->modifiers['isExternal'] ? $this->defaultExternalHiddenFields : []
        ));

        return array_diff_key($data, array_flip(array_diff($hidden, $this->modifiers['showFields'])));
    }

    protected function sortFields(array $data): array
    {
        $order = $this->modifiers['fieldOrder'];
        return empty($order) ? $data : array_merge(array_intersect_key(array_flip($order), $data), $data);
    }

    public function setExternal(bool $val = true): static { $this->modifiers['isExternal'] = $val; return $this; }
    public function setFieldOrder(array $order): static { $this->modifiers['fieldOrder'] = $order; return $this; }

    public static function makeCollection($resource): static { return (new static(null))->setCollectionResource($resource); }
    protected function setCollectionResource($resource): static { $this->originalCollectionResource = $resource; return $this; }

    public function applyToCollection(): AnonymousResourceCollection
    {
        $resource = $this->originalCollectionResource;
        $collection = method_exists($resource, 'getCollection') ? $resource->getCollection() : $resource;

        $collection->transform(function ($item) {
            $instance = new static($item);
            $instance->modifiers = $this->modifiers;
            if (method_exists($this, 'copyTranslationState')) $this->copyTranslationState($instance);
            return $instance;
        });

        if (method_exists($resource, 'setCollection')) {
            $resource->setCollection($collection);
            return static::collection($resource);
        }
        return static::collection($collection);
    }

    protected function getDefaultLanguageId(): int { return 1; }
}