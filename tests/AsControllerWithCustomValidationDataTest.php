<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerWithCustomValidationDataTest
{
    use AsController;

    public function getValidationData(ActionRequest $request): array
    {
        // We include the route parameters in the validation data.
        return array_merge(
            $request->route()->parametersWithoutNulls(),
            $request->all(),
        );
    }

    public function rules()
    {
        return [
            'left' => ['required'],
            'right' => ['required'],
        ];
    }

    public function handle(ActionRequest $request)
    {
        return $request->left + $request->right;
    }
}

beforeEach(function () {
    // Given an action with custom validation data registered as a controller.
    Route::post('/controller/{left}', AsControllerWithCustomValidationDataTest::class);
});

it('passes validation', function () {
    // When we provide valid data.
    $reponse = $this->postJson('/controller/1', [
        'right' => 2,
    ]);

    // Then we receive a successful response.
    $reponse->assertOk();
    $reponse->assertExactJson([3]);
});

it('fails validation', function () {
    // When we provide invalid data.
    $reponse = $this->postJson('/controller/1');

    // Then we receive a validation error.
    $reponse->assertStatus(422);
    $reponse->assertJsonMissingValidationErrors('left');
    $reponse->assertJsonValidationErrors([
        'right' => 'The right field is required.',
    ]);
});
