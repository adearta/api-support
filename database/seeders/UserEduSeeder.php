<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserEduSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::connection('pgsql2')->table('career_support_models_usereducation')->insert([
            'start_month' => rand(1, 12),
            //
            'start_year' => rand(2017, 2018),
            'end_month' => rand(1, 12),
            'end_year' => rand(2020, 2021),
            'gpa' => 3,
            'degree_id' => 3,
            //
            'school_id' => 1,
            //
            'user_id' => 2,
            //
            'major_id' => 3,
            'end_date' => '2023-11-21',
            'start_date' => '2017-11-21',
            //
            'nim' => 3,
            'verified' => true,
            'school_name' => Str::random(10),
            'is_active' => true,
            // 'batch' => rand(2017, 2020),
            // 'class' => Str::random(1),
            'is_deleted' => false,
            'created' => date('Y-m-d'),
            'modified' => date("Y-m-d"),
        ]);
        DB::connection('pgsql2')->table('career_support_models_usereducation')->insert([
            'start_month' => rand(1, 12),
            //
            'start_year' => rand(2017, 2018),
            'end_month' => rand(1, 12),
            'end_year' => rand(2020, 2021),
            'gpa' => 3,
            'degree_id' => 2,
            //
            'school_id' => 1,
            //
            'user_id' => 4,
            //
            'major_id' => 4,
            'end_date' => '2023-11-21',
            'start_date' => '2017-11-21',
            //
            'nim' => 2,
            'verified' => true,
            'school_name' => Str::random(10),
            'is_active' => true,
            // 'batch' => rand(2017, 2020),
            // 'class' => Str::random(1),
            'is_deleted' => false,
            'created' => date('Y-m-d'),
            'modified' => date("Y-m-d"),
        ]);
    }
}
