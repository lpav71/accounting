<?php


namespace App\Services\Telegram\Commands;


use Auth;
use Telegram\Bot\Commands\Command;

class LogoutCommand extends Command
{

    /**
     * @var string Command Name
     */
    protected $name = 'logout';

    /**
     * @var array Command Aliases
     */
    protected $aliases = ['logoutcommand'];

    /**
     * @var string Command Description
     */
    protected $description = "Выйти из аккаунта";

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        if (empty(Auth::user())) {
            $text = sprintf('%s' . PHP_EOL, 'Вы ещё не вошли в аккаунт');
            $this->replyWithMessage(compact('text'));
        } else {
            \Cache::store('file')->forget('current_ticket_' . Auth::id());
            Auth::user()->update(['telegram_chat_id' => null]);
            $text = sprintf('%s' . PHP_EOL, 'Вы успешно вышли из аккаунта ' . Auth::user()->name);
            $this->replyWithMessage(compact('text'));
        }
    }

}