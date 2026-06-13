<?php

namespace Database\Seeders;

use App\Models\Folder;
use Illuminate\Database\Seeder;

class FolderSeeder extends Seeder
{
    public function run(): void
    {
        $userId = 1;

        $tree = [
            'Banking & Finance' => [],
            'Bookmarks & Resources' => [],
            'Credentials - Platforms' => [
                'Developer Tools',
                'Gaming & Entertainment',
            ],
            'Credentials - Servers & Hosting' => [],
            'Development' => [
                'AI & LLM',
                'Commands & Snippets',
                'Laravel',
                'Learning & Concepts',
                'Resources',
            ],
            'ElectronicFirst - Work' => [
                'API & Products',
                'Code Snippets',
                'Disputes',
                'ENV & Config',
                'Fraud Detection',
                'Payment Integration',
                'Reports',
                'Revriser',
                'Tasks & Updates',
            ],
            'Islamic' => [],
            'Personal' => [
                'Addresses & Info',
                'Career & Writing',
                'Family',
                'Health',
            ],
            'Projects' => [
                'DailyLog',
                'Google AI Studio - Experiments',
                'Hujjah',
                'Larasearch',
                'Litepos',
                'Northster Inc.',
                'openenvmap',
                'Portfolio',
                'SignalStack',
                'Xencode',
                'Xentari',
            ],
            'Server & DevOps' => [
                'Deploy & Scripts',
                'Docker',
                'VPS & Proxmox',
            ],
            'Web Design' => [
                'Fonts & Components',
                'UI Inspiration',
            ],
        ];

        foreach ($tree as $parentName => $children) {
            $parent = Folder::create([
                'user_id' => $userId,
                'name' => $parentName,
                'parent_id' => null,
            ]);

            foreach ($children as $childName) {
                Folder::create([
                    'user_id' => $userId,
                    'name' => $childName,
                    'parent_id' => $parent->id,
                ]);
            }
        }
    }
}
