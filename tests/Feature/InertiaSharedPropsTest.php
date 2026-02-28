<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard shares auth permissions and flash data', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withSession(['success' => 'Facility updated.'])
        ->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('auth.user.id', $user->id)
        ->where('auth.permissions', [])
        ->where('flash.success', 'Facility updated.')
        ->where('flash.error', null)
        ->where('flash.warning', null)
        ->where('flash.info', null)
    );
});
