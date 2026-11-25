<?php

use App\Models\User;

test('the workspace renders for a signed-in member', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk();
});
