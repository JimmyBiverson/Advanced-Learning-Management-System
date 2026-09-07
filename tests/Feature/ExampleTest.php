<?php

it('redirects to the installer when the application is not installed', function () {
    $this->get('/')
        ->assertRedirect(route('install.index'));
});
