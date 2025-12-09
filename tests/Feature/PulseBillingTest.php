<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CallStatus;
use App\Enums\UserType;
use App\Models\Call;
use App\Models\User;
use App\Settings\CallingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user creation inherits default pulse settings', function () {
    CallingSetting::fake([
        'pulse_rate' => 0.50,
        'pulse_duration' => 30,
    ]);

    $user = User::factory()->create([
        'type' => UserType::User,
    ]);

    expect($user->pulse_rate)->toBe(0.50)
        ->and($user->pulse_duration)->toBe(30);
});

test('call cost is calculated correctly based on pulses', function () {
    $user = User::factory()->create([
        'balance' => 100.00,
    ]);

    $user->update([
        'pulse_rate' => 0.50,     // 0.50 BDT per pulse
        'pulse_duration' => 10,   // 10 seconds per pulse
    ]);

    // Test Case 1: 10s duration (exact 1 pulse)
    $call1 = Call::factory()->create([
        'user_id' => $user->id,
        'status' => CallStatus::Pending,
        'duration' => 10,
    ]);

    $call1->update(['status' => CallStatus::Completed]);

    expect($call1->refresh()->cost)->toBe(0.50);
    expect($user->refresh()->balance)->toBe(99.50);

    // Test Case 2: 11s duration (2 pulses)
    $call2 = Call::factory()->create([
        'user_id' => $user->id,
        'status' => CallStatus::Pending,
        'duration' => 11,
    ]);

    $call2->update(['status' => CallStatus::Completed]);

    expect($call2->refresh()->cost)->toBe(1.00); // 2 * 0.50
    expect($user->refresh()->balance)->toBe(98.50);

    // Test Case 3: 5s duration (1 pulse)
    $call3 = Call::factory()->create([
        'user_id' => $user->id,
        'status' => CallStatus::Pending,
        'duration' => 5,
    ]);

    $call3->update(['status' => CallStatus::Completed]);

    expect($call3->refresh()->cost)->toBe(0.50);
    expect($user->refresh()->balance)->toBe(98.00);
});

test('user resource returns correct pulse attributes', function () {
    $user = User::factory()->create();

    // Update after creation to override observer defaults
    $user->update([
        'pulse_rate' => 0.75,
        'pulse_duration' => 60,
    ]);

    $resource = new \App\Http\Resources\UserResource($user);
    $data = $resource->resolve();

    expect($data)->toHaveKey('pulse_rate', 0.75)
        ->and($data)->toHaveKey('pulse_duration', 60)
        ->and($data)->not->toHaveKey('rate');
});
