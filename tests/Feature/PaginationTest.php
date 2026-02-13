<?php

use Zeyn4loff\FluentResources\BaseResource;
use Illuminate\Support\Collection;

class PostResource extends BaseResource {
    protected function toBaseArray($request): array {
        return ['id' => $this->id, 'title' => $this->title];
    }
}

test('it preserves modifiers during pagination', function () {
    $data = collect([
        (object)['id' => 1, 'title' => 'Post 1'],
        (object)['id' => 2, 'title' => 'Post 2'],
    ]);

    $resource = PostResource::makeCollection($data)->hide(['title']);
    $result = $resource->applyToCollection();

    foreach ($result as $item) {
        expect($item->toArray(request()))->not->toHaveKey('title');
    }
});