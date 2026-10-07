<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(file_get_contents(__DIR__.'/data/testimonials.json'), true);

        foreach ($rows as $row) {
            Testimonial::updateOrCreate(['name' => $row['name'], 'company' => $row['company']], $row);
        }
    }
}
