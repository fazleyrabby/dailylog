<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Fazley Rabbi',
            'email' => 'fazley111@gmail.com',
            'password' => bcrypt('01821013136rabby'),
        ]);

        $this->call(FolderSeeder::class);
    }
}
