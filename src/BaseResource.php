<?php

namespace Zeyn4loff\FluentResources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Pagination\AbstractPaginator;
use Zeyn4loff\FluentResources\Traits\{HasTranslations, HasFluentScopes};

abstract class BaseResource extends JsonResource
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

    protected mixed $originalCollectionResource = null;

    public function toArray(Request $request): array
    {
        $data = $this->toBaseArray($request);
        $data = $this->applyTranslationLogic($data, $request);
        $data = $this->filterFields($data);
        $data = $this->sortFields($data);

        if (config('fluent-resources.convert_keys_to_camel_case', false)) {
            $data = $this->convertToCamelCase($data);
        }

        return $data;
    }

    abstract protected function toBaseArray(Request $request): array;

    protected function nested(string $class, string $relation): mixed
    {
        if (!$this->resource->relationLoaded($relation)) {
            $this->resource->loadMissing($relation);
        }

        $data = $this->resource->{$relation};
        if (!$data) return null;

        $instance = ($data instanceof Collection || $data instanceof AbstractPaginator)
            ? $class::makeCollection($data)
            : new $class($data);

        $this->inheritState($instance, $relation);

        return (method_exists($instance, 'applyToCollection')) ? $instance->applyToCollection() : $instance;
    }

    protected function inheritState(mixed $instance, string $relation): void
    {
        $instance->modifiers['languageId'] = $this->modifiers['languageId'];
        $instance->modifiers['isExternal'] = $this->modifiers['isExternal'];
        $instance->modifiers['replaceWithTranslation'] = $this->modifiers['replaceWithTranslation'];

        if (isset($this->modifiers['scopes'][$relation])) {
            $this->modifiers['scopes'][$relation]($instance);
        }
    }

    protected function filterFields(array $data): array
    {
        $defaults = config('fluent-resources.defaults.hidden_fields', ['created_at', 'updated_at']);
        $external = $this->modifiers['isExternal'] ? config('fluent-resources.defaults.external_hidden_fields', []) : [];
        $hidden = array_unique(array_merge($defaults, $this->modifiers['hiddenFields'], $external));
        $visible = $this->modifiers['showFields'];

        return array_filter($data, function ($key) use ($hidden, $visible) {
            return in_array($key, $visible) || !in_array($key, $hidden);
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function sortFields(array $data): array
    {
        $order = $this->modifiers['fieldOrder'];
        return empty($order) ? $data : array_merge(array_intersect_key(array_flip($order), $data), $data);
    }

    protected function convertToCamelCase(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $newKey = Str::camel($key);
            if (is_array($value)) $value = $this->convertToCamelCase($value);
            $result[$newKey] = $value;
        }
        return $result;
    }

    public function setExternal(bool $val = true): static { $this->modifiers['isExternal'] = $val; return $this; }
    public function setFieldOrder(array $order): static { $this->modifiers['fieldOrder'] = $order; return $this; }

    public static function makeCollection(mixed $resource): static { return (new static(null))->setCollectionResource($resource); }
    protected function setCollectionResource(mixed $resource): static { $this->originalCollectionResource = $resource; return $this; }

    public function applyToCollection(): AnonymousResourceCollection
    {
        $resource = $this->originalCollectionResource;
        $collection = method_exists($resource, 'getCollection') ? $resource->getCollection() : $resource;

        if ($collection instanceof Collection) {
            $collection->transform(function ($item) {
                $instance = new static($item);
                $instance->modifiers = $this->modifiers;
                if (method_exists($this, 'copyTranslationState')) $this->copyTranslationState($instance);
                return $instance;
            });
        }

        return static::collection($resource);
    }

    public function paginate(int $perPage = 15, string $pageName = 'page', ?int $page = null): AnonymousResourceCollection
    {
        $resource = $this->originalCollectionResource;
        if ($resource && method_exists($resource, 'paginate')) {
            $this->setCollectionResource($resource->paginate($perPage, ['*'], $pageName, $page));
        }
        return $this->applyToCollection();
    }
}