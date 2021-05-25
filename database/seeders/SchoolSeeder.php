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
        DB::connection('pgsql2')->table('career_support_models_school')->insert([
            'school_type_id' => 1,
            'name' => Str::random(),
            'phone' => rand(1234567, 12345678),
            //
            'email' => 'adearta@tudent.ub.ac.id',
            'fax' => rand(1234567, 1234567),
            'address' => Str::random(),
            'website' => 'http://www.' . Str::random(10) . '.com',
            'logo' => '/' . Str::random(3) . '/' . Str::random(3) . '/' . Str::random(3) . '/',
            // 'is_registered' =, hidden
            'secret' => Str::random(8),
            //
            'city_id' => rand(1, 2),
            'branch' => Str::random(2),
            'subdomain' => '/' . Str::random(8),
            'is_active' => true,
            'is_selected' => true,
            'is_recipient' => false,
            'postal_code' => rand(80224, 81224),
            'about' => Str::random(15),
            'mission' => Str::random(15),
            'vision' => Str::random(15),
            'facebookURL' => 'http://www.facebook.com/' . Str::random(8),
            'googleURL' => 'http://www.google.com/' . Str::random(8),
            'linkedinURL' => 'http://www.linkedln.com/' . Str::random(8),
            'pinterestURL' => 'http://www.pinterestURL.com/' . Str::random(8),
            'twitterURL' => 'http://www.twitter.com/' . Str::random(8),
            'verification_status' => rand(1, 5),
            'registration_step' => rand(1, 5),
            'subpath' => Str::random(8),
            'banner' => Str::random(8),
            'reason_inactive' => 'none',
            'is_deleted' => false,
            'created' => date("Y-m-d"),
            'modified' => date("Y-m-d"),
        ]);
    }
}
