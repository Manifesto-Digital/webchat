<?php

namespace OpenDialogAi\Webchat\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenDialogAi\AttributeEngine\CoreAttributes\UserAttribute;
use OpenDialogAi\AttributeEngine\CoreAttributes\UtteranceAttribute;
use OpenDialogAi\AttributeEngine\Exceptions\AttributeDoesNotExistException;
use OpenDialogAi\ContextEngine\Contexts\User\UserContext;
use OpenDialogAi\ContextEngine\Facades\ContextService;
use OpenDialogAi\Core\Components\Configuration\ComponentConfiguration;
use OpenDialogAi\Core\Components\Configuration\ConfigurationDataHelper;
use OpenDialogAi\Core\Conversation\Conversation;
use OpenDialogAi\Webchat\WebchatSetting;
use OpenDialogAi\Webchat\WebchatSettingsConfiguration\Service\WebchatSettingsConfigurationPageInformation;
use OpenDialogAi\Webchat\WebchatSettingsConfiguration\Service\WebchatSettingsConfigurationServiceInterface;

class WebchatSettings
{
    public const NEW_USER = 'new';
    public const ONGOING_USER = 'ongoing';
    public const RETURNING_USER = 'returning';

    /**
     * @param Request $request
     * @return mixed
     */
    public static function getScenarioIdFromRequest(Request $request)
    {
        return $request->get('scenario_id') ?? $request->json('custom_settings.user.custom.selected_scenario');
    }

    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function __invoke(Request $request)
    {
        if ($error = $this->validateRequest($request)) {
            return response($error, 400);
        }

        /** @var ComponentConfiguration $configuration */
        $configuration = ComponentConfiguration::where([
            'name' => ConfigurationDataHelper::WEBCHAT_PLATFORM,
            'scenario_id' => self::getScenarioIdFromRequest($request),
        ])->first();

        if (is_null($configuration)) {
            return response()->json([]);
        }

        $settings = $this->setDynamicSettings($configuration->configuration, $request);

        // Return the config as JSON.
        return response()->json($settings);
    }

    /**
     * @param Request $request
     * @return string|null
     */
    private function validateRequest(Request $request)
    {
        if (!self::getScenarioIdFromRequest($request)) {
            return 'No scenario_id given.';
        }

        if ($request->get('width') && !is_numeric($request->get('width'))) {
            return 'Width parameter must be an integer.';
        }

        if ($request->get('url') && !filter_var($request->get('url'), FILTER_VALIDATE_URL)) {
            return 'Url parameter is not a valid URL.';
        }

        return null;
    }

    /**
     * Uses the user history record to establish whether this is a new, returning or ongoing user.
     * If there is no user context available => new user
     * If the user is currently in a conversation => ongoing user
     * If the user is not currently in a conversation => returning user
     *
     * @param $userId
     * @return string
     */
    private function getUserType($userId): string
    {
        /** @var UserContext $userContext */
        $userContext = ContextService::getContext(UserContext::USER_CONTEXT);
        $userContext->setUserId($userId);

        try {
            /** @var UserAttribute $userAttribute */
            $userAttribute = $userContext->getAttribute(UtteranceAttribute::UTTERANCE_USER, true);

            if ($userAttribute->getUserHistoryRecord()->getConversationId() === Conversation::UNDEFINED) {
                return self::RETURNING_USER;
            }

            return self::ONGOING_USER;
        } catch (AttributeDoesNotExistException $e) {
            return self::NEW_USER;
        }
    }

    /**
     * @param array $config
     * @param Request $request
     * @return array
     */
    private function setDynamicSettings(array $config, Request $request): array
    {
        $config[WebchatSetting::USER_TYPE] = self::NEW_USER;
        $config[WebchatSetting::SHOW_MINIMIZED] = false;
        $config[WebchatSetting::OPEN_INTENT] = 'WELCOME';

        if ($request->get('user_id')) {
            $config[WebchatSetting::USER_TYPE] = $this->getUserType($request->get('user_id'));
        }

        /** @var WebchatSettingsConfigurationServiceInterface $configurationService */
        $configurationService = resolve(WebchatSettingsConfigurationServiceInterface::class);

        $pageUrl = $request->get('url') ?? $request->header('referer');

        $queryParameters = [];
        $queryParametersString = parse_url($pageUrl, PHP_URL_QUERY);

        if (!is_null($queryParametersString)) {
            $pageUrl = str_replace('?' . $queryParametersString, '', $pageUrl);
            parse_str($queryParametersString, $queryParameters);
        }

        $pageInfo = new WebchatSettingsConfigurationPageInformation($pageUrl);
        $pageInfo->setUserId($request->get('user_id') ?? null);
        $pageInfo->setWidth($request->get('width') ?? null);
        $pageInfo->setCallbackId($request->get('callback_id') ?? null);
        $pageInfo->setQueryParameters($queryParameters ?? []);
        $pageInfo->setTags($request->json('tags') ?? []);

        $settings = $configurationService->runConfigurations($config, $pageInfo);

        $general = $settings[WebchatSetting::GENERAL];

        // phpcs:disable
        switch ($config[WebchatSetting::USER_TYPE]) {
            case self::NEW_USER:
                $settings[WebchatSetting::SHOW_MINIMIZED] = $general[WebchatSetting::NEW_USER_START_MINIMIZED] ?? false;
                $settings[WebchatSetting::OPEN_INTENT] = $general[WebchatSetting::NEW_USER_OPEN_CALLBACK] ?? null;
                break;

            case self::RETURNING_USER:
                $settings[WebchatSetting::SHOW_MINIMIZED] = $general[WebchatSetting::RETURNING_USER_START_MINIMIZED] ?? false;
                $settings[WebchatSetting::OPEN_INTENT] = $general[WebchatSetting::RETURNING_USER_OPEN_CALLBACK] ?? null;
                break;

            case self::ONGOING_USER:
                $settings[WebchatSetting::SHOW_MINIMIZED] = $general[WebchatSetting::ONGOING_USER_START_MINIMIZED] ?? false;
                $settings[WebchatSetting::OPEN_INTENT] = $general[WebchatSetting::ONGOING_USER_OPEN_CALLBACK] ?? null;
                break;
        }
        // phpcs:enable

        return $settings;
    }
}
