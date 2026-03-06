<?php

use App\Domains\Notifications\Actions\SendUserNotificationAction;
use App\Domains\Notifications\DTOs\UserNotificationData;
use App\Models\User;

test('user can fetch and mark notifications as read', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    app(SendUserNotificationAction::class)->execute(new UserNotificationData(
        user_id: $user->id,
        event: 'maintenance_request.approved',
        title: 'Maintenance request approved',
        body: 'Request #10 is now Approved.',
        action_url: '/maintenance/10',
        meta: ['maintenance_request_id' => 10],
    ));

    $response = $this->get(route('notifications.index'));
    $response->assertOk();
    expect($response->json('notifications'))->toHaveCount(1);

    $notificationId = $response->json('notifications.0.id');

    $this->post(route('notifications.read', $notificationId))
        ->assertOk()
        ->assertJson(['unread_count' => 0]);
});
