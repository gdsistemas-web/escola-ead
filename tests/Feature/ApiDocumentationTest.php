<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_api_documentation_page_is_available(): void
    {
        $this->get('/api/documentation')
            ->assertOk()
            ->assertSee('swagger-ui');
    }

    public function test_openapi_spec_is_available(): void
    {
        $this->get('/api/docs/openapi.yaml')
            ->assertOk()
            ->assertSee('openapi: 3.0.3')
            ->assertSee('/courses:');
    }
}
