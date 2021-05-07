<?php

use App\TicketTheme;
use Illuminate\Database\Seeder;

class TicketThemesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TicketTheme::create(['name' => 'Фото']);
        TicketTheme::create(['name' => 'Собрать']);
    }
}
