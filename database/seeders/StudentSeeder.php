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
        DB::table('career_support_models_student')->insert([
            'school_id' => 1,
            'name' => Str::random(10),
            'batch' => rand(2017, 2020),
            'class' => Str::random(1),
            'nim' => rand(123456, 1234567),
            'is_deleted' => false,

        ]);
        DB::table('career_support_models_student')->insert([
            'school_id' => 1,
            'name' => Str::random(10),
            'batch' => rand(2017, 2020),
            'class' => Str::random(1),
            'nim' => rand(123456, 1234567),
            'is_deleted' => false,

        ]);
        DB::table('career_support_models_student')->insert([
            'school_id' => 1,
            'name' => Str::random(10),
            'batch' => rand(2017, 2020),
            'class' => Str::random(1),
            'nim' => rand(123456, 1234567),
            'is_deleted' => false,

        ]);
        DB::table('career_support_models_student')->insert([
            'school_id' => 1,
            'name' => Str::random(10),
            'batch' => rand(2017, 2020),
            'class' => Str::random(1),
            'nim' => rand(123456, 1234567),
            'is_deleted' => false,

        ]);
        DB::table('career_support_models_student')->insert([
            'school_id' => 2,
            'name' => Str::random(10),
            'batch' => rand(2017, 2020),
            'class' => Str::random(1),
            'nim' => rand(123456, 1234567),
            'is_deleted' => false,

        ]);
        DB::table('career_support_models_student')->insert([
            'school_id' => 2,
            'name' => Str::random(10),
            'batch' => rand(2017, 2020),
            'class' => Str::random(1),
            'nim' => rand(123456, 1234567),
            // 'year' => rand(1999, 2002),
            'is_deleted' => false,

        ]);
        DB::table('career_support_models_student')->insert([
            'school_id' => 2,
            'name' => Str::random(10),
            'batch' => rand(2017, 2020),
            'class' => Str::random(1),
            'nim' => rand(123456, 1234567),
            // 'year' => rand(1999, 2002),
            'is_deleted' => false,

        ]);
    }
}
