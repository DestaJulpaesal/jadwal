<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ButtonComponentTest extends TestCase
{
    public function test_button_component_renders_variant_icon_and_label(): void
    {
        $html = Blade::render('<x-button variant="primary" icon="tambah">Tambah tugas</x-button>');

        $this->assertStringContainsString('button--primary', $html);
        $this->assertStringContainsString('icon-tambah', $html);
        $this->assertStringContainsString('Tambah tugas', $html);
    }

    public function test_button_component_infers_a_matching_icon_for_common_actions(): void
    {
        $html = Blade::render('<x-button variant="primary">Tambah tugas</x-button>');

        $this->assertStringContainsString('icon-tambah', $html);
    }

    public function test_icon_only_button_requires_accessible_name_and_title(): void
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('Tombol ikon harus memiliki aria-label dan title.');

        Blade::render('<x-button variant="ghost" icon="tutup" icon-only />');
    }
}
