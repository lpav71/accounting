<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(TicketStatesTableSeeder::class);
        $this->call(TicketPrioritiesTableSeeder::class);
        $this->call(TicketThemesTableSeeder::class);
    }
}
