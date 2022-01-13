<?php

namespace OpenDialogAi\Webchat\Tests\Unit;

use OpenDialogAi\Webchat\Tests\TestCase;
use OpenDialogAi\Webchat\WebchatSetting;
use OpenDialogAi\Core\ComponentSetting;

class WebchatSettingsCommandTest extends TestCase
{
    public function testCommandRun()
    {
        $this->app['config']->set(
            'opendialog.component_settings',
            [
                WebchatSetting::GENERAL => [
                    WebchatSetting::URL => [
                        WebchatSetting::DISPLAY_NAME => 'URL',
                        WebchatSetting::DISPLAY => false,
                        WebchatSetting::DESCRIPTION => 'The URL the bot is hosted at',
                        WebchatSetting::TYPE => WebchatSetting::STRING,
                    ],
                    WebchatSetting::TEAM_NAME => [
                        WebchatSetting::DISPLAY_NAME => 'Chatbot Name',
                        WebchatSetting::DESCRIPTION => 'The name displayed in the chatbot header',
                        WebchatSetting::TYPE => WebchatSetting::STRING,
                        WebchatSetting::SECTION => "General Settings",
                        WebchatSetting::SUBSECTION => 'Header',
                        WebchatSetting::SIBLING => WebchatSetting::LOGO
                    ],
                    WebchatSetting::LOGO => [
                        WebchatSetting::DISPLAY_NAME => 'Logo',
                        WebchatSetting::DESCRIPTION => 'The chatbot logo displayed in the header',
                        WebchatSetting::TYPE => WebchatSetting::STRING,
                        WebchatSetting::SECTION => "General Settings",
                        WebchatSetting::SUBSECTION => 'Header',
                        WebchatSetting::SIBLING => WebchatSetting::TEAM_NAME
                    ]
                ]
            ]
        );

        $this->artisan('component:settings');

        $this->assertCount(4, ComponentSetting::all());

        $this->assertDatabaseHas('component_settings', ['name' => 'general']);
        $this->assertDatabaseHas('component_settings', ['name' => 'url']);
        $this->assertDatabaseHas('component_settings', ['name' => 'teamName']);
        $this->assertDatabaseHas('component_settings', ['name' => 'logo']);

        $teamName = ComponentSetting::where('name', 'teamName')->first();
        $this->assertEquals('Chatbot Name', $teamName->display_name);
        $this->assertEquals('string', $teamName->type);
        $this->assertEquals(true, $teamName->display);
        $this->assertEquals('General Settings', $teamName->section);
        $this->assertEquals('Header', $teamName->subsection);
        $this->assertEquals('The name displayed in the chatbot header', $teamName->description);
        $this->assertEquals(1, $teamName->parent_id);
        $this->assertEquals(3, $teamName->sibling);
    }
}
