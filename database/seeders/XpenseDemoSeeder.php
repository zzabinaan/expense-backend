<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class XpenseDemoSeeder extends Seeder
{
    private const CATEGORIES = [
        ['name' => 'Food',          'color' => '#f97316'],
        ['name' => 'Transport',     'color' => '#3b82f6'],
        ['name' => 'Shopping',      'color' => '#a855f7'],
        ['name' => 'Bills',         'color' => '#ef4444'],
        ['name' => 'Entertainment', 'color' => '#f59e0b'],
        ['name' => 'Health',        'color' => '#22c55e'],
        ['name' => 'Other',         'color' => '#6b7280'],
    ];

    // [category_name => [min, max, descriptions[]]]
    private const EXPENSE_TEMPLATES = [
        'Food' => [
            'amounts' => [15000, 85000],
            'descriptions' => [
                'Makan siang',
                'Kopi pagi',
                'Sarapan',
                'Makan malam',
                'Snack sore',
                'Bubble tea',
                'Bakso',
                'Nasi padang',
                'Indomie warung',
                'Gorengan',
                'Ayam geprek',
                'Soto',
                'Pizza',
                'Boba',
                'Mie goreng',
            ],
            'freq' => 2.5,
        ],
        'Transport' => [
            'amounts' => [5000, 45000],
            'descriptions' => [
                'Ojek online',
                'Bensin motor',
                'Parkir',
                'Grab',
                'Gojek',
                'Transjakarta',
                'KRL',
                'Bensin mobil',
                'Tol',
                'Taksi',
            ],
            'freq' => 1.5,
        ],
        'Shopping' => [
            'amounts' => [50000, 600000],
            'descriptions' => [
                'Baju baru',
                'Sepatu',
                'Belanja bulanan',
                'Alat tulis',
                'Skincare',
                'Peralatan dapur',
                'Buku',
                'Aksesoris',
                'Tas',
                'Shampoo & sabun',
                'Deterjen',
                'Peralatan mandi',
                'Charger hp',
                'Earphone',
            ],
            'freq' => 0.4,
        ],
        'Bills' => [
            'amounts' => [50000, 500000],
            'descriptions' => [
                'Listrik',
                'Internet',
                'Netflix',
                'Spotify',
                'Air PDAM',
                'Iuran RT',
                'Bayar kos',
                'Cicilan hp',
                'Asuransi',
                'BPJS',
            ],
            'freq' => 0.2,
        ],
        'Entertainment' => [
            'amounts' => [30000, 250000],
            'descriptions' => [
                'Nonton bioskop',
                'Game online top-up',
                'YouTube Premium',
                'Karaoke',
                'Nongkrong kafe',
                'Konser',
                'Bowling',
                'Escape room',
            ],
            'freq' => 0.5,
        ],
        'Health' => [
            'amounts' => [20000, 400000],
            'descriptions' => [
                'Vitamin C',
                'Beli obat',
                'Dokter umum',
                'Suplemen fitness',
                'Masker',
                'Cek darah',
                'Apotek',
                'Gym bulanan',
            ],
            'freq' => 0.3,
        ],
        'Other' => [
            'amounts' => [10000, 150000],
            'descriptions' => [
                'Uang laundry',
                'Hadiah ulang tahun',
                'Amal & sedekah',
                'Fotokopi',
                'Print dokumen',
                'Top-up e-wallet',
                'Uang jajan keponakan',
            ],
            'freq' => 0.4,
        ],
    ];

    public function run(): void
    {
        // Create or update user
        $user = User::updateOrCreate(
            ['email' => 'zzabinaan@gmail.com'],
            [
                'name' => 'Zabina',
                'password' => Hash::make('12345678'),
            ]
        );

        // Delete existing seeded data for clean re-seed
        $user->expenses()->delete();
        $user->categories()->delete();

        // Seed categories
        $categories = [];
        foreach (self::CATEGORIES as $cat) {
            $categories[$cat['name']] = Category::create([
                'user_id' => $user->id,
                'name' => $cat['name'],
                'color' => $cat['color'],
            ]);
        }

        // Generate expenses from Jan 1 to June 14, 2026 (yesterday)
        $start = Carbon::create(2026, 1, 1);
        $end = Carbon::create(2026, 6, 14);

        $expenses = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            // Slightly fewer expenses on weekends
            $dayMultiplier = $current->isWeekend() ? 0.6 : 1.0;

            foreach ($categories as $catName => $category) {
                $template = self::EXPENSE_TEMPLATES[$catName];
                $freq = $template['freq'] * $dayMultiplier;

                // Bills mostly at start/end of month
                if ($catName === 'Bills') {
                    $freq = ($current->day <= 5 || $current->day >= 25) ? 0.4 : 0.05;
                }

                // Determine how many entries to create today for this category
                $rand = mt_rand(0, 999) / 1000;
                if ($rand > $freq) {
                    continue;
                }

                // Occasionally add 2 entries for Food
                $entries = ($catName === 'Food' && mt_rand(0, 2) === 0) ? 2 : 1;

                for ($i = 0; $i < $entries; $i++) {
                    [$min, $max] = $template['amounts'];

                    // Round to nearest 500 for realism
                    $amount = round(mt_rand($min / 500, $max / 500) * 500);

                    $descriptions = $template['descriptions'];
                    $description = $descriptions[array_rand($descriptions)];

                    $expenses[] = [
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                        'expense_date' => $current->toDateString(),
                        'amount' => $amount,
                        'description' => $description,
                        'created_at' => $current->copy()->setTime(mt_rand(7, 21), mt_rand(0, 59)),
                        'updated_at' => $current->copy()->setTime(mt_rand(7, 21), mt_rand(0, 59)),
                    ];
                }
            }

            $current->addDay();
        }

        // Bulk insert in chunks
        foreach (array_chunk($expenses, 200) as $chunk) {
            Expense::insert($chunk);
        }
    }
}
