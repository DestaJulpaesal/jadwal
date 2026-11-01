<?php

return [
    'habits' => [
        ['title' => 'Belajar bahasa Inggris', 'frequency' => 'daily', 'goal_value' => 30, 'goal_unit' => 'menit'],
        ['title' => 'Lari', 'frequency' => 'weekdays', 'weekdays' => [7]],
        ['title' => 'Minum air', 'frequency' => 'daily', 'goal_value' => 8, 'goal_unit' => 'gelas'],
        ['title' => 'Baca buku', 'frequency' => 'daily', 'goal_value' => 15, 'goal_unit' => 'menit'],
        ['title' => 'Olahraga', 'frequency' => 'weekly_count', 'weekly_target' => 3],
    ],
    'money_categories' => [
        'expense' => ['Makan & minum', 'Transportasi', 'Belanja', 'Tagihan', 'Kesehatan', 'Pendidikan', 'Hiburan', 'Lainnya'],
        'income' => ['Gaji', 'Bonus', 'Lainnya'],
    ],
];
