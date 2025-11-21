<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('setting')->insert([
            'id_setting' => 1,
            'nama_perusahaan' => 'Rudy Motor',
            'alamat' => 'Jl. KHM Mansyur Gg 5 No 1A Kota Pekalongan',
            'telepon' => '08164243664',
            'Tipe_Nota' => 1, //kecil
            'path_logo' => 'img/logo.png',
            'path_kartu_customer' => 'img/member.png', 
        ]);
    }
}
