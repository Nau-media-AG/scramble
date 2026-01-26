<?php

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\JsonResourceTypeToSchema;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\ModelToSchema;
use Dedoc\Scramble\Tests\Files\SamplePostModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

beforeEach(function () {
    $this->infer = app(Infer::class);
    $this->context = new OpenApiContext(new OpenApi('3.1.0'), new GeneratorConfig);
});

function CarbonExtensionTest_analyze(Infer $infer, OpenApiContext $context, string $class)
{
    $transformer = new TypeTransformer($infer, $context, [
        ModelToSchema::class,
        JsonResourceTypeToSchema::class,
    ]);
    $extension = new JsonResourceTypeToSchema($infer, $transformer, $context->openApi->components, $context);

    $type = new ObjectType($class);

    return $extension->toSchema($type);
}

/**
 * @property SamplePostModel $resource
 */
class CarbonExtensionTest_DateTimeMethods extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'toIso8601String' => $this->created_at?->toIso8601String(),
            'toIso8601ZuluString' => $this->created_at?->toIso8601ZuluString(),
            'toISOString' => $this->created_at?->toISOString(),
            'toDateTimeString' => $this->created_at?->toDateTimeString(),
            'toDateTimeLocalString' => $this->created_at?->toDateTimeLocalString(),
            'toAtomString' => $this->created_at?->toAtomString(),
            'toW3cString' => $this->created_at?->toW3cString(),
            'toRfc822String' => $this->created_at?->toRfc822String(),
            'toRfc850String' => $this->created_at?->toRfc850String(),
            'toRfc1036String' => $this->created_at?->toRfc1036String(),
            'toRfc1123String' => $this->created_at?->toRfc1123String(),
            'toRfc2822String' => $this->created_at?->toRfc2822String(),
            'toRfc3339String' => $this->created_at?->toRfc3339String(),
            'toRfc7231String' => $this->created_at?->toRfc7231String(),
            'toRssString' => $this->created_at?->toRssString(),
            'toCookieString' => $this->created_at?->toCookieString(),
            'toJSON' => $this->created_at?->toJSON(),
            'jsonSerialize' => $this->created_at?->jsonSerialize(),
        ];
    }
}

it('infers date-time format for Carbon datetime methods', function () {
    $schema = CarbonExtensionTest_analyze($this->infer, $this->context, CarbonExtensionTest_DateTimeMethods::class);

    $properties = $schema->toArray()['properties'];

    $expectedDateTimeFormat = ['type' => ['string', 'null'], 'format' => 'date-time'];

    expect($properties['toIso8601String'])->toBe($expectedDateTimeFormat);
    expect($properties['toIso8601ZuluString'])->toBe($expectedDateTimeFormat);
    expect($properties['toISOString'])->toBe($expectedDateTimeFormat);
    expect($properties['toDateTimeString'])->toBe($expectedDateTimeFormat);
    expect($properties['toDateTimeLocalString'])->toBe($expectedDateTimeFormat);
    expect($properties['toAtomString'])->toBe($expectedDateTimeFormat);
    expect($properties['toW3cString'])->toBe($expectedDateTimeFormat);
    expect($properties['toRfc822String'])->toBe($expectedDateTimeFormat);
    expect($properties['toRfc850String'])->toBe($expectedDateTimeFormat);
    expect($properties['toRfc1036String'])->toBe($expectedDateTimeFormat);
    expect($properties['toRfc1123String'])->toBe($expectedDateTimeFormat);
    expect($properties['toRfc2822String'])->toBe($expectedDateTimeFormat);
    expect($properties['toRfc3339String'])->toBe($expectedDateTimeFormat);
    expect($properties['toRfc7231String'])->toBe($expectedDateTimeFormat);
    expect($properties['toRssString'])->toBe($expectedDateTimeFormat);
    expect($properties['toCookieString'])->toBe($expectedDateTimeFormat);
    expect($properties['toJSON'])->toBe($expectedDateTimeFormat);
    expect($properties['jsonSerialize'])->toBe($expectedDateTimeFormat);
});

/**
 * @property SamplePostModel $resource
 */
class CarbonExtensionTest_DateMethods extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'toDateString' => $this->created_at?->toDateString(),
        ];
    }
}

it('infers date format for Carbon date methods', function () {
    $schema = CarbonExtensionTest_analyze($this->infer, $this->context, CarbonExtensionTest_DateMethods::class);

    $properties = $schema->toArray()['properties'];

    expect($properties['toDateString'])->toBe(['type' => ['string', 'null'], 'format' => 'date']);
});

/**
 * @property SamplePostModel $resource
 */
class CarbonExtensionTest_TimeMethods extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'toTimeString' => $this->created_at?->toTimeString(),
        ];
    }
}

it('infers time format for Carbon time methods', function () {
    $schema = CarbonExtensionTest_analyze($this->infer, $this->context, CarbonExtensionTest_TimeMethods::class);

    $properties = $schema->toArray()['properties'];

    expect($properties['toTimeString'])->toBe(['type' => ['string', 'null'], 'format' => 'time']);
});
