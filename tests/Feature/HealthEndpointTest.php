<?php

test('health returns db and redis ok', function () {
    $this->get(route('health.check'))
        ->assertOk()
        ->assertJson(['db' => 'ok', 'redis' => 'ok']);
});
