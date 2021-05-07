<?php

use App\TicketPriority;
use Illuminate\Database\Seeder;

class TicketPrioritiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TicketPriority::create(['name' => 'Нормальный', 'is_default' => 1, 'rate' => 10]);
        TicketPriority::create(['name' => 'Срочный', 'rate' => 30]);
        TicketPriority::create(['name' => 'Очень срочный', 'rate' => 50]);
    }
}
