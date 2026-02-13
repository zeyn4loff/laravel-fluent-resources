<?php

use Zeyn4loff\FluentResources\Tests\TestCase;
use Zeyn4loff\FluentResources\BaseResource;
use Illuminate\Http\Request;

class UserResource extends BaseResource {
    protected function toBaseArray($request): array {
        return [
            'id' => 1,
            'first_name' => 'Zeyn',
            'email_address' => 'test@example.com',
            'profile' => [
                'city_name' => 'Baku',
                'zip_code' => '1000'
            ]
        ];
    }
}

test('it can hide nested fields and convert to camelCase', function () {
    // Включаем camelCase в конфиге для этого теста
    config(['fluent-resources.convert_keys_to_camel_case' => true]);

    $resource = (new UserResource(null))
        ->hide(['profile.zip_code', 'email_address']);

    $result = $resource->toArray(new Request());

    // Проверяем скрытие
    expect($result)->not->toHaveKey('emailAddress')
        ->and($result['profile'])->not->toHaveKey('zipCode');

    // Проверяем camelCase
    expect($result)->toHaveKey('firstName')
        ->and($result['profile'])->toHaveKey('cityName');
});