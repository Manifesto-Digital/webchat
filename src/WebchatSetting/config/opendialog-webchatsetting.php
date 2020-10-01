<?php

use OpenDialogAi\Webchat\WebchatSetting;

return [
    WebchatSetting::GENERAL => [
        WebchatSetting::STRING => [
            WebchatSetting::URL,
            WebchatSetting::TEAM_NAME,
            WebchatSetting::LOGO,
            WebchatSetting::CHATBOT_CSS_PATH,
            WebchatSetting::CHATBOT_FULLPAGE_CSS_PATH,
            WebchatSetting::PAGE_CSS_PATH,
            WebchatSetting::CHATBOT_NAME,
            WebchatSetting::CHATBOT_AVATAR_PATH,
            WebchatSetting::RESTART_BUTTON_CALLBACK,
            WebchatSetting::NEW_USER_OPEN_CALLBACK,
            WebchatSetting::RETURNING_USER_OPEN_CALLBACK,
            WebchatSetting::ONGOING_USER_OPEN_CALLBACK,
        ],
        WebchatSetting::BOOLEAN => [
            WebchatSetting::OPEN,
            WebchatSetting::HIDE_DATETIME_MESSAGE,
            WebchatSetting::HIDE_MESSAGE_TIME,
            WebchatSetting::DISABLE_CLOSE_CHAT,
            WebchatSetting::START_MINIMIZED,
            WebchatSetting::USE_BOT_AVATAR,
            WebchatSetting::USE_HUMAN_AVATAR,
            WebchatSetting::USE_BOT_NAME,
            WebchatSetting::USE_HUMAN_NAME,
            WebchatSetting::COLLECT_USER_IP,
            WebchatSetting::SHOW_RESTART_BUTTON,
            WebchatSetting::MESSAGE_ANIMATION,
            WebchatSetting::HIDE_TYPING_INDICATOR_ON_INTERNAL_MESSAGES,
            WebchatSetting::NEW_USER_START_MINIMIZED,
            WebchatSetting::RETURNING_USER_START_MINIMIZED,
            WebchatSetting::ONGOING_USER_START_MINIMIZED,
        ],
        WebchatSetting::NUMBER => [
            WebchatSetting::MESSAGE_DELAY,
        ],
        WebchatSetting::MAP => [
            WebchatSetting::VALID_PATH,
            WebchatSetting::CALLBACK_MAP,
        ],
    ],
    WebchatSetting::COLOURS => [
        WebchatSetting::COLOUR => [
            WebchatSetting::HEADER_BACKGROUND,
            WebchatSetting::HEADER_TEXT,
            WebchatSetting::LAUNCHER_BACKGROUND,
            WebchatSetting::MESSAGE_LIST_BACKGROUND,
            WebchatSetting::SENT_MESSAGE_BACKGROUND,
            WebchatSetting::SENT_MESSAGE_TEXT,
            WebchatSetting::RECEIVED_MESSAGE_BACKGROUND,
            WebchatSetting::RECEIVED_MESSAGE_TEXT,
            WebchatSetting::USER_INPUT_BACKGROUND,
            WebchatSetting::USER_INPUT_TEXT,
            WebchatSetting::ICON_BACKGROUND,
            WebchatSetting::ICON_HOVER_BACKGROUND,
            WebchatSetting::BUTTON_BACKGROUND,
            WebchatSetting::BUTTON_HOVER_BACKGROUND,
            WebchatSetting::BUTTON_TEXT,
            WebchatSetting::EXTERNAL_BUTTON_BACKGROUND,
            WebchatSetting::EXTERNAL_BUTTON_HOVER_BACKGROUND,
            WebchatSetting::EXTERNAL_BUTTON_TEXT,
        ],
    ],
    WebchatSetting::COMMENTS => [
        WebchatSetting::BOOLEAN => [
            WebchatSetting::COMMENTS_ENABLED,
        ],
        WebchatSetting::STRING => [
            WebchatSetting::COMMENTS_NAME,
            WebchatSetting::COMMENTS_ENABLED_PATH_PATTERN,
            WebchatSetting::COMMENTS_ENTITY_NAME,
            WebchatSetting::COMMENTS_CREATED_FIELDNAME,
            WebchatSetting::COMMENTS_TEXT_FIELDNAME,
            WebchatSetting::COMMENTS_ENDPOINT,
            WebchatSetting::COMMENTS_AUTH_TOKEN,
            WebchatSetting::COMMENTS_AUTHOR_ENTITY_NAME,
            WebchatSetting::COMMENTS_AUTHOR_RELATIONSHIP_NAME,
            WebchatSetting::COMMENTS_AUTHOR_ID_FIELDNAME,
            WebchatSetting::COMMENTS_AUTHOR_NAME_FIELDNAME,
            WebchatSetting::COMMENTS_SECTION_ENTITY_NAME,
            WebchatSetting::COMMENTS_SECTION_RELATIONSHIP_NAME,
            WebchatSetting::COMMENTS_SECTION_ID_FIELDNAME,
            WebchatSetting::COMMENTS_SECTION_NAME_FIELDNAME,
            WebchatSetting::COMMENTS_SECTION_FILTER_PATH_PATTERN,
            WebchatSetting::COMMENTS_SECTION_FILTER_QUERY,
            WebchatSetting::COMMENTS_SECTION_PATH_PATTERN,
        ],
    ],
    WebchatSetting::WEBCHAT_HISTORY => [
        WebchatSetting::BOOLEAN => [
            WebchatSetting::SHOW_HISTORY,
        ],
        WebchatSetting::NUMBER => [
            WebchatSetting::NUMBER_OF_MESSAGES,
        ],
    ]
];
