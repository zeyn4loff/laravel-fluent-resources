<?php

class TranslatableProductResource extends BaseResource {
    protected array $translatableFields = ['name'];
    protected function toBaseArray($request): array {
        return ['id' => $this->id, 'name' => $this->name];
    }
}

test('it replaces fields with translations', function () {
    $mockModel = (object)[
        'id' => 1,
        'name' => 'Original',
        'translations' => collect([
            (object)['language_id' => 2, 'name' => 'Translated Name']
        ]),
    ];
    // Имитируем Laravel relation
    $mockModel->relationLoaded = fn($rel) => $rel === 'translations';

    $resource = (new TranslatableProductResource($mockModel))
        ->replaceTranslatableFields()
        ->setLanguageId(2);

    $result = $resource->toArray(request());

    expect($result['name'])->toBe('Translated Name');
});