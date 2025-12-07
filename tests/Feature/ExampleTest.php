<?php

declare(strict_types=1);

test('the application returns a successful response', function () {
    $response = $this->get('/user/login');

    $response->assertStatus(200);
});
