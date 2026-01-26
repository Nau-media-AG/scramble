<?php

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\Components;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\JsonResourceTypeToSchema;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\ModelToSchema;
use Dedoc\Scramble\Tests\Files\SamplePostModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->infer = app(Infer::class);
    $this->context = new OpenApiContext(new OpenApi('3.1.0'), new GeneratorConfig);
});

/**
 * @return array{0: \Dedoc\Scramble\Support\Generator\Types\Type, 1: Components}
 */
function JsonResourceExtensionTest_analyze(Infer $infer, OpenApiContext $context, string $class)
{
    $transformer = new TypeTransformer($infer, $context, [
        ModelToSchema::class,
        JsonResourceTypeToSchema::class,
    ]);
    $extension = new JsonResourceTypeToSchema($infer, $transformer, $context->openApi->components, $context);

    $type = new ObjectType($class);

    $openApiType = $extension->toSchema($type);

    return [$openApiType, $context->openApi->components];
}

it('supports whenHas', function () {
    [$schema] = JsonResourceExtensionTest_analyze($this->infer, $this->context, JsonResourceExtensionTest_WhenHas::class);

    expect($schema->toArray())->toBe([
        'type' => 'object',
        'properties' => [
            'user' => [
                '$ref' => '#/components/schemas/SampleUserModel',
            ],
            'value' => [
                'type' => 'integer',
                'enum' => [42],
            ],
            'default' => [
                'anyOf' => [
                    [
                        'type' => 'string',
                        'enum' => ['foo'],
                    ],
                    [
                        'type' => 'integer',
                        'enum' => [42],
                    ],
                ],
            ],
        ],
        'required' => ['default'],
    ]);
});
/** @mixin SamplePostModel */
class JsonResourceExtensionTest_WhenHas extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'user' => $this->whenHas('user'),
            'value' => $this->whenHas('user', 42),
            'default' => $this->whenHas('user', 42, 'foo'),
        ];
    }
}

class JsonResourceExtensionTest_MatchWithThrow extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'property' => match (rand(0, 2)) {
                0 => 'foo',
                1 => throw new Exception('foo'),
                default => 123,
            },
        ];
    }
}

it('supports match with throw', function () {
    [$schema] = JsonResourceExtensionTest_analyze($this->infer, $this->context, JsonResourceExtensionTest_MatchWithThrow::class);

    expect($schema->toArray())->toBe([
        'type' => 'object',
        'properties' => [
            'property' => [
                'anyOf' => [
                    ['type' => 'string', 'enum' => ['foo']],
                    ['type' => 'integer', 'enum' => [123]],
                ],
            ],
        ],
        'required' => ['property'],
    ]);
});

/**
 * Test case for GitHub issue #1059
 * Complex JsonResource with multiple early returns of empty arrays and final return with data
 *
 * @see https://github.com/dedoc/scramble/issues/1059
 */
class JsonResourceExtensionTest_MultipleEmptyReturns extends JsonResource
{
    public function toArray(Request $request): array
    {
        $value = $this->resource->value ?? '';

        if (! is_string($value) || $value === '') {
            return [];
        }

        $items = explode(',', $value);

        if (empty($items)) {
            return [];
        }

        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'name' => [
                    'en' => 'English name',
                    'es' => 'Spanish name',
                ],
                'code' => trim($item),
            ];
        }

        return $data;
    }
}

/**
 * @see https://github.com/dedoc/scramble/issues/1059
 *
 * This test verifies the bug where complex JsonResource with multiple early `return []`
 * and dynamic array construction produces incorrect schema (tuple-style instead of array).
 */
it('supports resource with multiple empty array returns', function () {
    [$schema] = JsonResourceExtensionTest_analyze($this->infer, $this->context, JsonResourceExtensionTest_MultipleEmptyReturns::class);

    $schemaArray = $schema->toArray();

    // The schema should have 'anyOf' with two possible return types
    expect($schemaArray)->toHaveKey('anyOf')
        ->and($schemaArray['anyOf'])->toHaveCount(2);

    // Bug #1059: Schema should NOT contain 'prefixItems' (tuple-style)
    // Instead, it should use 'items' for proper array representation
    $hasPrefixItems = collect($schemaArray['anyOf'])->contains(fn ($item) => isset($item['prefixItems']));
    expect($hasPrefixItems)->toBeFalse('Schema should use "items" (array) not "prefixItems" (tuple)');

    // Verify the dynamic list uses proper 'items' schema
    $dynamicListSchema = collect($schemaArray['anyOf'])->first(fn ($item) => isset($item['items']));
    expect($dynamicListSchema)->not->toBeNull()
        ->and($dynamicListSchema['items'])->toHaveKey('type')
        ->and($dynamicListSchema['items']['type'])->toBe('object')
        ->and($dynamicListSchema['items']['properties'])->toHaveKeys(['name', 'code']);
});
