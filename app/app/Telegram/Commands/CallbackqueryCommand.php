<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Update;
use Bot\Conversation;

/**
 * Callback query command
 */
class CallbackqueryCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'callbackquery';

    /**
     * @var string
     */
    protected $description = 'Reply to callback query';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * Command execute method
     *
     * @return mixed
     */
    public function execute()
    {
        $callback_query = $this->getUpdate()->getCallbackQuery();

        $query_id = $callback_query->getId();
        $query_data = $callback_query->getData();

        if (!empty($query_data)) {
            list($response_command, $response_state, $response_payload) = $this->telegram->unserialize($query_data);
            error_log($query_data);
            if (!empty($response_command)) {
                $conversation = new Conversation($callback_query->getFrom()->getId(), $callback_query->getMessage()->getChat()->getId(), $response_command);
                $conversation->setState($response_state);
                $conversation->update();

                $update = new Update([
                    'update_id' => 0,
                    'message' => [
                        'message_id' => 0,
                        'from' => [
                            'id' => $callback_query->getFrom()->getId(),
                            'first_name' => $callback_query->getFrom()->getFirstName(),
                            'username' => $callback_query->getFrom()->getUsername(),
                        ],
                        'date' => time(),
                        'chat' => [
                            'id' => $callback_query->getMessage()->getChat()->getId(),
                            'type' => 'private',
                        ],
                        'text' => $response_payload,
                        'command' => $response_command,
                        'reply_to_message' => [
                            'message_id' => $callback_query->getMessage()->getMessageId()
                        ],
                    ],
                ]);

                $this->telegram->processUpdate($update);
            }
        }

        $data = [
            'callback_query_id' => $query_id,
            'text' => '',
            'show_alert' => false,
            'cache_time' => 5,
        ];

        return Request::answerCallbackQuery($data);
    }
}
