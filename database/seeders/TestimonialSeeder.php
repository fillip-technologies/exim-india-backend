<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'name' => 'Marcus Vance',
                'role' => 'Director of Global Procurement',
                'company' => 'BevTech Innovations Europe (Frankfurt, Germany)',
                'avatar' => 'testimonials/avatar-1.jpg',
                'quote' => 'We have sourced synthetic food colours and aluminium lake pigments from Exim India for over six years. Their batch consistency and fast regulatory dossiers make EU customs clearance completely seamless.',
            ],
            [
                'name' => 'Dr. Amira El-Sayed',
                'role' => 'Head of Quality & Formulations',
                'company' => 'Gulf Confectionery & Bakery Ltd. (Dubai, UAE)',
                'avatar' => 'testimonials/avatar-2.jpg',
                'quote' => 'We tested Exim India\'s cloud emulsions and lake colours across our gummy lines. The color stability under high heat exceeded our benchmark, and express evaluation samples always arrive within 48 hours.',
            ],
            [
                'name' => 'Robert M. Davies',
                'role' => 'VP of Supply Chain & QA',
                'company' => 'Apex Health & Pharma Formulations (Toronto, Canada)',
                'avatar' => 'testimonials/avatar-3.jpg',
                'quote' => 'Our pharmaceutical operations demand strict USP/EP certified purity for tablet coatings. Exim India\'s airtight fiber drums and detailed COA heavy-metal screening give our QA audit team complete confidence.',
            ],
            [
                'name' => 'Kenji Takahashi',
                'role' => 'Chief Formulation Chemist',
                'company' => 'Nippon Flavours & Ingredients (Osaka, Japan)',
                'avatar' => 'testimonials/avatar-4.jpg',
                'quote' => 'We audited dozens of colour suppliers before qualifying Exim India. Their Lake Tartrazine and botanical extracts consistently match Japan\'s strict Food Sanitation Law standards with zero batch variance.',
            ],
            [
                'name' => 'Claire Harrington',
                'role' => 'Head of Ingredients Sourcing',
                'company' => 'Britannia Food & Beverage Group (London, UK)',
                'avatar' => 'testimonials/avatar-5.jpg',
                'quote' => 'From water-soluble FD&C dyes to custom spray-dried fruit flavour compounds, Exim India provides prompt sea-freight dispatch and flawless documentation. By far our most dependable export partner.',
            ],
        ];

        foreach ($rows as $i => $row) {
            Testimonial::updateOrCreate(
                ['name' => $row['name'], 'company' => $row['company']],
                [...$row, 'sort_order' => $i + 1],
            );
        }
    }
}
