<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        DB::connection('pgsql2')->table('career_support_models_student')->insert([
            //
            'nim' => rand(1, 5),
            'name' => Str::random(10),
            'phone' => rand(123456, 1234567),
            //
            'email' => 'hebryclover@gmail.com',
            'schoolfile_id' => rand(3, 8),
            //
            'school_id' => 1,
            // 'batch' => rand(2017, 2020),
            // 'class' => Str::random(1),
            'is_deleted' => false,
            'created' => date('Y-m-d'),
            'modified' => date("Y-m-d"),
        ]);
    }
}
