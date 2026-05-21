<?php

namespace Database\Seeders;

use App\Models\SensitiveExtension;
use Illuminate\Database\Seeder;

class SensitiveExtensionSeeder extends Seeder
{
    public function run(): void
    {
        $extensions = [
            [
                'extension' => 'docx',
                'category' => 'important',
                'label' => 'Document Word',
                'is_enabled' => true,
            ],
            [
                'extension' => 'xlsx',
                'category' => 'important',
                'label' => 'Classeur Excel',
                'is_enabled' => true,
            ],
            [
                'extension' => 'pdf',
                'category' => 'important',
                'label' => 'Document PDF',
                'is_enabled' => true,
            ],
            [
                'extension' => 'csv',
                'category' => 'important',
                'label' => 'Fichier CSV',
                'is_enabled' => true,
            ],
            [
                'extension' => 'sql',
                'category' => 'important',
                'label' => 'Dump ou script SQL',
                'is_enabled' => true,
            ],
            [
                'extension' => 'zip',
                'category' => 'important',
                'label' => 'Archive ZIP',
                'is_enabled' => true,
            ],
            [
                'extension' => 'locked',
                'category' => 'suspicious',
                'label' => 'Extension verrouillée suspecte',
                'is_enabled' => true,
            ],
            [
                'extension' => 'enc',
                'category' => 'suspicious',
                'label' => 'Extension chiffrée suspecte',
                'is_enabled' => true,
            ],
            [
                'extension' => 'encrypted',
                'category' => 'suspicious',
                'label' => 'Extension encrypted suspecte',
                'is_enabled' => true,
            ],
            [
                'extension' => 'crypted',
                'category' => 'suspicious',
                'label' => 'Extension crypted suspecte',
                'is_enabled' => true,
            ],
            [
                'extension' => 'locky',
                'category' => 'suspicious',
                'label' => 'Extension associée à des attaques de type Locky',
                'is_enabled' => true,
            ],
        ];

        foreach ($extensions as $extension) {
            SensitiveExtension::updateOrCreate(
                ['extension' => $extension['extension']],
                $extension
            );
        }
    }
}
