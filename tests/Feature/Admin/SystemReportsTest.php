<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function adminReportUser(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('admin reports dashboard renders summary', function () {
    $user = adminReportUser(['reports.view']);

    $this->actingAs($user);

    $response = $this->get(route('reports.admin'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('data.summary')
        ->has('data.statusBreakdown')
        ->has('data.trends')
    );
});

test('admin reports export supports csv and pdf', function () {
    $user = adminReportUser(['reports.view']);

    $this->actingAs($user);

    $csv = $this->get(route('reports.admin.export', ['format' => 'csv']));
    $csv->assertOk();
    $csv->assertHeader('Content-Type', 'text/csv; charset=utf-8');

    $pdf = $this->get(route('reports.admin.export', ['format' => 'pdf']));
    $pdf->assertOk();
    $pdf->assertHeader('Content-Type', 'application/pdf');
});
