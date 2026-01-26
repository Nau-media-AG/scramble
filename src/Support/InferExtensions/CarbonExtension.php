<?php

namespace Dedoc\Scramble\Support\InferExtensions;

use Carbon\CarbonInterface;
use Dedoc\Scramble\Infer\Extensions\Event\MethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\MethodReturnTypeExtension;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\StringType;
use Dedoc\Scramble\Support\Type\Type;

class CarbonExtension implements MethodReturnTypeExtension
{
    /**
     * Methods that return date-time formatted strings (ISO 8601 / RFC 3339 compatible)
     */
    private const DATE_TIME_METHODS = [
        'toIso8601String',
        'toIso8601ZuluString',
        'toISOString',
        'toDateTimeString',
        'toDateTimeLocalString',
        'toAtomString',
        'toW3cString',
        'toRfc822String',
        'toRfc850String',
        'toRfc1036String',
        'toRfc1123String',
        'toRfc2822String',
        'toRfc3339String',
        'toRfc7231String',
        'toRssString',
        'toCookieString',
        'toJSON',
        'jsonSerialize',
    ];

    /**
     * Methods that return date-only formatted strings
     */
    private const DATE_METHODS = [
        'toDateString',
    ];

    /**
     * Methods that return time-only formatted strings
     */
    private const TIME_METHODS = [
        'toTimeString',
    ];

    public function shouldHandle(ObjectType $type): bool
    {
        return $type->isInstanceOf(CarbonInterface::class);
    }

    public function getMethodReturnType(MethodCallEvent $event): ?Type
    {
        $methodName = $event->getName();

        if (in_array($methodName, self::DATE_TIME_METHODS, true)) {
            return tap(new StringType, fn ($t) => $t->setAttribute('format', 'date-time'));
        }

        if (in_array($methodName, self::DATE_METHODS, true)) {
            return tap(new StringType, fn ($t) => $t->setAttribute('format', 'date'));
        }

        if (in_array($methodName, self::TIME_METHODS, true)) {
            return tap(new StringType, fn ($t) => $t->setAttribute('format', 'time'));
        }

        return null;
    }
}
