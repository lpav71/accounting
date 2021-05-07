<?php


namespace App\Services\Telegram\Commands;


use App\TicketTheme;
use Auth;
use Telegram\Bot\Commands\Command;

class TicketCreateCommand extends Command
{

    /**
     * @var string Command Name
     */
    protected $name = 'ticketcreate';

    /**
     * @var array Command Aliases
     */
    protected $aliases = ['ticketcreatecommand'];

    /**
     * @var string Command Description
     */
    protected $description = "Создать тикет";

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        if (empty(Auth::user())) {
            $text = sprintf('%s' . PHP_EOL, 'Вы ещё не вошли в аккаунт');
            $this->replyWithMessage(compact('text'));
            return 'ok';
        }
        $this->replyWithMessage([
            'text' => __('Select theme'),
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => $this->getButtons()
            ])
        ]);
    }

    /**
     * get buttons first step
     *
     * @return array
     */
    private function getButtons(): array
    {
        $buttons = [];
        foreach (TicketTheme::where('is_hidden',0)->get() as $ticketTheme) {
            $button = [
                'text' => $ticketTheme->name,
                'callback_data' => '/ticketcreate ' . $ticketTheme->id,
            ];
            $buttons[] = [$button];
        };
        return $buttons;
    }


}