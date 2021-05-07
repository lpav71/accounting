<?php

use App\TicketState;
use Illuminate\Database\Seeder;

class TicketStatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TicketState::create(['name' => 'Новый', 'is_default' => 1]);
        TicketState::create(['name' => 'В работе']);
        TicketState::create(['name' => 'Закрыт', 'is_closed' => 1]);
    }
}
