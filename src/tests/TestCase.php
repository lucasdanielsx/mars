<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function getJsonFileResponse(string $file) {
        return json_decode(file_get_contents(getcwd() . "/tests/Mock/response/" . $file), true);
    }

    public function getJsonFileRequest(string $file) {
        return json_decode(file_get_contents(getcwd() . "/tests/Mock/request/" . $file), true);
    }
}
