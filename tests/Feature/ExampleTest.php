<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_opens_the_web_chat(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Iniciar atendimento')
            ->assertSee('/api/chat', false);
    }
}
