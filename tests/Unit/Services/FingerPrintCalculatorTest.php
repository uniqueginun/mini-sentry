<?php

use App\Services\FingerPrintCalculator;

it('groups events that share an exception type, class, and method', function () {
    $calculator = new FingerPrintCalculator;
    $payload = defaultGroupingPayload();

    $first = $calculator->calculate(7, $payload);
    $second = $calculator->calculate(7, $payload);

    expect($first)->toBe($second);
    expect($first)->toBe(hash('sha256', '7|DivisionByZeroError|InvoiceService|calculateTotal'));
});

it('does not group events that differ only by application method', function () {
    $calculator = new FingerPrintCalculator;

    $total = $calculator->calculate(7, defaultGroupingPayload());
    $tax = $calculator->calculate(7, defaultGroupingPayload(
        method: 'calculateTax',
    ));

    expect($tax)->toBe(hash('sha256', '7|DivisionByZeroError|InvoiceService|calculateTax'));
    expect($tax)->not->toBe($total);
});

it('does not group the same error across projects', function () {
    $calculator = new FingerPrintCalculator;
    $payload = defaultGroupingPayload();

    $projectSeven = $calculator->calculate(7, $payload);
    $projectEight = $calculator->calculate(8, $payload);

    expect($projectSeven)->toBe(hash('sha256', '7|DivisionByZeroError|InvoiceService|calculateTotal'));
    expect($projectEight)->toBe(hash('sha256', '8|DivisionByZeroError|InvoiceService|calculateTotal'));
    expect($projectEight)->not->toBe($projectSeven);
});

it('hashes client fingerprint parts after stripping class method line numbers', function () {
    $calculator = new FingerPrintCalculator;

    $payload = [
        'event' => [
            'fingerprint' => [
                'Illuminate\Database\Eloquent\ModelNotFoundException',
                'App\MiniSentry\ExceptionSampleGenerator::modelNotFound:48',
                'App\Models\User',
            ],
            'exception' => [
                'values' => [[
                    'type' => 'RuntimeException',
                    'stacktrace' => [[
                        'class' => 'IgnoredService',
                        'function' => 'ignored',
                        'in_app' => true,
                    ]],
                ]],
            ],
        ],
    ];

    $fingerprint = $calculator->calculate(7, $payload);

    expect($fingerprint)->toBe(hash(
        'sha256',
        '7|Illuminate\Database\Eloquent\ModelNotFoundException|App\MiniSentry\ExceptionSampleGenerator::modelNotFound|App\Models\User',
    ));
});

it('uses the first in-app frame instead of vendor frames when the client fingerprint is omitted', function () {
    $calculator = new FingerPrintCalculator;

    $fingerprint = $calculator->calculate(7, defaultGroupingPayload());

    expect($fingerprint)->toBe(hash('sha256', '7|DivisionByZeroError|InvoiceService|calculateTotal'));
});

it('falls back to the culprit when the stacktrace has no in-app frame', function () {
    $calculator = new FingerPrintCalculator;

    $payload = [
        'event' => [
            'culprit' => 'InvoiceService::calculateTotal:22',
            'exception' => [
                'values' => [[
                    'type' => 'DivisionByZeroError',
                    'stacktrace' => [[
                        'class' => 'Vendor\Math',
                        'function' => 'divide',
                        'in_app' => false,
                    ]],
                ]],
            ],
        ],
    ];

    $fingerprint = $calculator->calculate(7, $payload);

    expect($fingerprint)->toBe(hash('sha256', '7|DivisionByZeroError|InvoiceService|calculateTotal'));
});

it('hashes a custom client fingerprint with the project id', function () {
    $calculator = new FingerPrintCalculator;

    $payload = [
        'event' => [
            'fingerprint' => [
                'payment-processing',
                'stripe-timeout',
            ],
        ],
    ];

    $fingerprint = $calculator->calculate(7, $payload);

    expect($fingerprint)->toBe(hash('sha256', '7|payment-processing|stripe-timeout'));
});

it('falls back to default grouping when the client fingerprint is missing or empty', function (array $event) {
    $calculator = new FingerPrintCalculator;

    $fingerprint = $calculator->calculate(7, ['event' => $event]);

    expect($fingerprint)->toBe(hash('sha256', '7|DivisionByZeroError|InvoiceService|calculateTotal'));
})->with([
    'omitted' => [defaultGroupingEvent()],
    'null' => [defaultGroupingEvent(['fingerprint' => null])],
    'empty list' => [defaultGroupingEvent(['fingerprint' => []])],
]);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function defaultGroupingEvent(array $overrides = []): array
{
    return [
        ...$overrides,
        'exception' => [
            'values' => [[
                'type' => 'DivisionByZeroError',
                'stacktrace' => [
                    [
                        'class' => 'Vendor\Math',
                        'function' => 'divide',
                        'in_app' => false,
                    ],
                    [
                        'class' => 'InvoiceService',
                        'function' => 'calculateTotal',
                        'in_app' => true,
                    ],
                ],
            ]],
        ],
    ];
}

/**
 * @return array{event: array<string, mixed>}
 */
function defaultGroupingPayload(string $method = 'calculateTotal'): array
{
    $event = defaultGroupingEvent();
    $event['exception']['values'][0]['stacktrace'][1]['function'] = $method;

    return ['event' => $event];
}
