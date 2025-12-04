<?php

test('the application returns a successful response', function () {
    $response = $this->get('/user/login');

    $response->assertStatus(200);
});
