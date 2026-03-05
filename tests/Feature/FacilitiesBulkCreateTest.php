<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\User;
use Spatie\Permission\Models\Permission;

test('user can bulk create facilities', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('facilities.create');
    $user->givePermissionTo('facilities.create');

    $type = FacilityType::create(['name' => 'Campus']);

    $this->actingAs($user);

    $response = $this->post(route('facilities.store'), [
        'facilities' => [
            [
                'name' => 'North Campus Gym',
                'facility_type_id' => $type->id,
                'parent_id' => null,
                'condition' => 'Good',
                'managed_by' => null,
            ],
            [
                'name' => 'South Campus Library',
                'facility_type_id' => $type->id,
                'parent_id' => null,
                'condition' => 'Bad',
                'managed_by' => null,
            ],
        ],
    ]);

    $response
        ->assertRedirect(route('facilities.index'))
        ->assertSessionHas('success', '2 facilities created successfully.');

    expect(Facility::query()->where('name', 'North Campus Gym')->exists())->toBeTrue();
    expect(Facility::query()->where('name', 'South Campus Library')->exists())->toBeTrue();
});
