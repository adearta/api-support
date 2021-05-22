<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
// use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
// use phpDocumentor\Reflection\Types\Boolean;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('career_support_models_school')->insert([
            'school_name' => Str::random(10),
            'school_email' => 'hebryclover@gmail.com',
            'is_deleted' => false,

        ]);
        DB::table('career_support_models_school')->insert([
            'school_name' => Str::random(10),
            'school_email' => 'adearta48@gmail.com',
            'is_deleted' => false,

        ]);
        DB::table('career_support_models_school')->insert([
            'school_name' => Str::random(10),
            'school_email' => 'adearta@student.ub.ac.id',
            'is_deleted' => false,

        ]);
    }
}
