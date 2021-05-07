<?php


namespace App\Services\Telegram\Commands;


use App\User;
use GuzzleHttp\Client;
use Hash;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use Telegram;

class LoginCommand extends Command
{

    /**
     * @var string Command Name
     */
    protected $name = 'login';

    /**
     * @var array Command Aliases
     */
    protected $aliases = ['logincommand'];

    /**
     * @var string Command Description
     */
    protected $description = "Войти с помощью логина пароля аккаунтинга";

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $matches = null;
        try{
            if(preg_match('/\/(\S*)\s(.*)\s(.*)$/',Telegram::getWebhookUpdates()['message']['text'],$matches)){
                $email = $matches[2];
                $pass = $matches[3];
                $hasher = app('hash');
                $user = User::where('email',$email)->first();
                if(!is_null($user) && $hasher->check($pass, $user->password)){
                    $user->update(['telegram_chat_id' => Telegram::getWebhookUpdates()['message']['from']['id']]);
                    $text = sprintf('%s' . PHP_EOL, 'Вы вошли как '.$user->name);
                    $text .= sprintf('%s' . PHP_EOL, 'Введите /tickets чтобы посмотреть доступные вам тикеты');
                    $this->replyWithMessage(compact('text'));
                }else{
                    $this->wrongPass();
                }
            }else{
                $this->wrongPass();

            }
        }catch (\Exception $e){
            $this->wrongPass();
        }
        $text = sprintf('%s' . PHP_EOL, 'Удалите сообщения в которых содержатся логин и пароль чтобы никто их не узнал');
        $this->replyWithMessage(compact('text'));
    }

    private function wrongPass(){
        $text = sprintf('%s' . PHP_EOL, 'Неверный логин или пароль');
        $this->replyWithMessage(compact('text'));
    }

}