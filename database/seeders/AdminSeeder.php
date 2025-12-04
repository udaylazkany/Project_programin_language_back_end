<?php

namespace Database\Seeders;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Request;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Testing\Fluent\Concerns\Has;
use App\Models\Admin;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin =Admin::create(['name'=>'uday',
        'email'=>'ulazkany@gmail.com','password'=>Hash::make('hadel98olamath')]);
    }
}
