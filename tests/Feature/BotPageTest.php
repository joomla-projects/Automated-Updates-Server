<?php

namespace Tests\Feature;

use Tests\TestCase;

class BotPageTest extends TestCase
{
    public function testBotPageListsConfiguredIps(): void
    {
        config(['autoupdates.bot_ips' => ['192.0.2.10', '2001:db8::10']]);

        $this->get('/bot')
            ->assertOk()
            ->assertSee('Joomla.org Automated Updates Server')
            ->assertSee('192.0.2.10')
            ->assertSee('2001:db8::10');
    }

    public function testBotPageWithoutIps(): void
    {
        config(['autoupdates.bot_ips' => []]);

        $this->get('/bot')
            ->assertOk()
            ->assertSee('No addresses are published at the moment.');
    }
}
