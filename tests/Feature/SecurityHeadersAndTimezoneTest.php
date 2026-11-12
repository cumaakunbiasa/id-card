<?php

test('same-origin camera access is allowed by the permissions policy', function () {
    $this->get('/')
        ->assertOk()
        ->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
});

test('application uses the Jakarta timezone by default', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta');
    expect(date_default_timezone_get())->toBe('Asia/Jakarta');
    expect(config('database.connections.mysql.timezone'))->toBe('+07:00');
    expect(config('database.connections.mariadb.timezone'))->toBe('+07:00');
});
