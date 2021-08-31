<?php

namespace OpenDialogAi\Webchat\Tests\Feature;

use Mockery;
use OpenDialogAi\AttributeEngine\CoreAttributes\UserAttribute;
use OpenDialogAi\AttributeEngine\CoreAttributes\UserHistoryRecord;
use OpenDialogAi\AttributeEngine\Exceptions\AttributeDoesNotExistException;
use OpenDialogAi\ContextEngine\Contexts\User\UserContext;
use OpenDialogAi\ContextEngine\Facades\ContextService;
use OpenDialogAi\Core\Components\Configuration\ComponentConfiguration;
use OpenDialogAi\PlatformEngine\Components\WebchatPlatform;
use OpenDialogAi\Webchat\Tests\TestCase;
use OpenDialogAi\Webchat\WebchatSetting;

class SettingsEndpointTest extends TestCase
{
    public function testSettingsApi()
    {
        $configuration = [
            'general' => [
                'teamName' => 'OpenDialog Webchat',
                'open' => true,
            ],
            'colours' => [
                'headerBackground' => '#ffffff',
                'headerText' => '#000000',
            ],
            'comments' => [
                'commentsName' => 'Comments Tab',
                'commentsEnabled' => false,
            ],
            'messageDelay' => 1000,
            'testObject' => [
                'foo' => 'bar',
                'bee' => 'baz',
            ]
        ];

        $this->createWebchatPlatformConfiguration($configuration);

        $response = $this->json('GET', '/webchat-config?scenario_id=0x000');
        $response
            ->assertStatus(200)
            ->assertJson($configuration, true);
    }

    public function testSettingsApiWithUserIdOngoingUser()
    {
        $configuration = [
            'general' => [
                'ongoingUserStartMinimized' => false,
                'ongoingUserOpenCallback' => 'ongoing_user_open_callback',
            ]
        ];

        $this->createWebchatPlatformConfiguration($configuration);

        $userId = 'test';

        $this->mockUserContext($userId, 1);

        $this->json('GET', '/webchat-config?scenario_id=0x000&user_id=' . $userId)
            ->assertStatus(200)
            ->assertJson([
                'userType' => 'ongoing',
                'showMinimized' => false,
                'openIntent' => 'ongoing_user_open_callback',
            ], true);
    }

    public function testSettingsApiWithUserIdReturningUser()
    {
        $configuration = [
            'general' => [
                'returningUserStartMinimized' => false,
                'returningUserOpenCallback' => 'returning_user_open_callback',
            ]
        ];

        $this->createWebchatPlatformConfiguration($configuration);

        $userId = 'test';

        $this->mockUserContext($userId, 'undefined');

        $this->json('GET', '/webchat-config?scenario_id=0x000&user_id=' . $userId)
            ->assertStatus(200)
            ->assertJson([
                'userType' => 'returning',
                'showMinimized' => false,
                'openIntent' => 'returning_user_open_callback',
            ], true);
    }

    public function testSettingsApiWithUserIdNewUser()
    {
        $configuration = [
            'general' => [
                'newUserStartMinimized' => true,
                'newUserOpenCallback' => 'new_user_open_callback',
            ]
        ];

        $this->createWebchatPlatformConfiguration($configuration);

        $userId = 'test';

        $this->mockUserContext($userId, null, false);

        $this->json('GET', '/webchat-config?scenario_id=0x000&user_id=' . $userId)
            ->assertStatus(200)
            ->assertJson([
                'userType' => 'new',
                'showMinimized' => true,
                'openIntent' => 'new_user_open_callback',
            ], true);
    }

    /**
     * @param string $userId
     * @param $conversationId
     */
    private function mockUserContext(string $userId, $conversationId, $userRecordExists = true): void
    {
        $userHistoryRecord = Mockery::mock(UserHistoryRecord::class)->makePartial();
        $userHistoryRecord->shouldReceive('getConversationId')
            ->andReturn($conversationId);

        $userHistoryRecord->shouldReceive('getId')
            ->andReturn('history_record');

        $userAttribute = new UserAttribute($userId);
        $userAttribute->setUserHistoryRecord($userHistoryRecord);

        $userContext = Mockery::mock(UserContext::class)->makePartial();
        if ($userRecordExists) {
            $userContext->shouldReceive('getAttribute')
                ->withArgs(['utterance_user', true])
                ->andReturn($userAttribute);
        } else {
            $userContext->shouldReceive('getAttribute')
                ->withArgs(['utterance_user', true])
                ->andThrow(AttributeDoesNotExistException::class);
        }

        $userContext->shouldReceive('setUserId')
            ->andReturn(true);

        ContextService::shouldReceive('getContext')
            ->withArgs(['user'])
            ->andReturn($userContext);
    }

    /**
     * @param array $configuration
     */
    private function createWebchatPlatformConfiguration(array $configuration): void
    {
        ComponentConfiguration::create([
            'name' => 'Webchat',
            'scenario_id' => '0x000',
            'component_id' => WebchatPlatform::getComponentId(),
            'configuration' => $configuration,
            'active' => true,
        ]);
    }
}
