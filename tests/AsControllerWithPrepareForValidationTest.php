<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerWithPrepareForValidationTest
{
    use AsController;

    public function prepareForValidation(ActionRequest $request)
    {
        preg_match('/^(\d+)?(.)?(\d+)?$/', $request->route('expression'), $match);

        $request->merge([
            'left' => $match[1] ?? null,
            'operator' => $match[2] ?? null,
            'right' => $match[3] ?? null,
        ]);
    }

    public function rules()
    {
        return [
            'left' => ['required'],
            'operator' => ['required', 'in:+,-'],
            'right' => ['required'],
        ];
    }

    public function handle(ActionRequest $request)
    {
        return $request->operator === '+'
            ? $request->left + $request->right
            : $request->left - $request->right;
    }
}

beforeEach(function () {
    // Given the action is registered as a controller.
    Route::get('/controller/{expression}', AsControllerWithPrepareForValidationTest::class);
});

it('passes validation', function () {
    // When we provide a raw expression
    $reponse = $this->getJson('/controller/1+2');

    // Then that expression was parsed as attributes
    // by the prepareForValidation method.
    $reponse->assertOk()->assertExactJson([3]);
});

it('fails validation', function () {
    // When we provide a raw expression with the missing right operand.
    $reponse = $this->getJson('/controller/4-');

    // Then that expression was parsed with a missing
    // right operand and results in a validation error.
    $reponse->assertStatus(422);
    $reponse->assertJsonValidationErrors([
        'right' => 'The right field is required.',
    ]);
});
