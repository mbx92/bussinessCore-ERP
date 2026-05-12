<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Access Control', 'description' => 'Sistem akses kontrol keamanan: card reader, fingerprint, face recognition'],
            ['name' => 'Access Point', 'description' => 'Wireless access point untuk jaringan WiFi indoor/outdoor'],
            ['name' => 'Accessories', 'description' => 'Aksesoris pendukung perangkat CCTV dan IT'],
            ['name' => 'Adapter Wifi', 'description' => 'USB adapter WiFi untuk koneksi wireless'],
            ['name' => 'Adaptor', 'description' => 'Adaptor dan power supply untuk perangkat CCTV/IT'],
            ['name' => 'Alat Listrik', 'description' => 'Peralatan listrik: kabel listrik, stop kontak, pipa, fischer, dll'],
            ['name' => 'Box Panel', 'description' => 'Box panel dan casing untuk instalasi perangkat'],
            ['name' => 'Bracket', 'description' => 'Bracket mounting untuk kamera CCTV, TV, dan perangkat lainnya'],
            ['name' => 'Connector', 'description' => 'Konektor RJ45, BNC, HDMI, dan konektor instalasi lainnya'],
            ['name' => 'Converter', 'description' => 'Converter sinyal: HDMI to VGA, DVI to VGA, DP to HDMI'],
            ['name' => 'DVR', 'description' => 'Digital Video Recorder untuk perekaman sistem CCTV analog'],
            ['name' => 'Enclosure', 'description' => 'Enclosure dan casing untuk HDD eksternal'],
            ['name' => 'Flash Drive', 'description' => 'USB Flash Drive untuk penyimpanan data portabel'],
            ['name' => 'IP Camera', 'description' => 'Kamera IP untuk monitoring jaringan: WiFi dan PoE camera'],
            ['name' => 'Input Device', 'description' => 'Keyboard, mouse, dan perangkat input lainnya'],
            ['name' => 'Jasa', 'description' => 'Layanan jasa teknis: instalasi, setting, maintenance CCTV dan IT'],
            ['name' => 'Jasa Instalasi', 'description' => 'Layanan jasa instalasi kabel, pipa, dan perangkat'],
            ['name' => 'Kabel', 'description' => 'Kabel jaringan UTP/FTP, kabel coaxial, kabel HDMI, dan kabel instalasi'],
            ['name' => 'Kamera CCTV', 'description' => 'Kamera CCTV analog dan IP: indoor, outdoor, PTZ, dome, bullet'],
            ['name' => 'License', 'description' => 'Lisensi software: Windows, Office, dan aplikasi lainnya'],
            ['name' => 'Mini PC', 'description' => 'Mini PC dan barebone untuk kebutuhan komputasi kompak'],
            ['name' => 'Monitor', 'description' => 'Monitor display LED/LCD untuk CCTV dan komputer'],
            ['name' => 'Motherboard', 'description' => 'Mainboard komputer desktop'],
            ['name' => 'NVR', 'description' => 'Network Video Recorder untuk sistem IP Camera'],
            ['name' => 'Notebook', 'description' => 'Laptop dan notebook untuk kebutuhan mobile computing'],
            ['name' => 'PC Case', 'description' => 'Casing komputer desktop dengan berbagai form factor'],
            ['name' => 'PC Rakitan', 'description' => 'Komputer rakitan siap pakai'],
            ['name' => 'Power Supply', 'description' => 'Power supply untuk komputer desktop'],
            ['name' => 'Power Supply CCTV', 'description' => 'Power supply jaring 12V khusus sistem CCTV'],
            ['name' => 'Printer', 'description' => 'Printer inkjet, thermal, dan label untuk kebutuhan cetak'],
            ['name' => 'Processor', 'description' => 'Prosesor CPU Intel dan AMD untuk komputer desktop'],
            ['name' => 'RAM', 'description' => 'Memory RAM DIMM dan SODIMM DDR3/DDR4 untuk PC dan laptop'],
            ['name' => 'Rack', 'description' => 'Rack server, wallmount rack, dan aksesoris rack jaringan'],
            ['name' => 'Router', 'description' => 'Router Mikrotik, TP-Link, Ruijie, dan router jaringan lainnya'],
            ['name' => 'SSD', 'description' => 'Solid State Drive SATA dan NVMe untuk penyimpanan data cepat'],
            ['name' => 'Scanner', 'description' => 'Barcode scanner 1D/2D untuk sistem POS'],
            ['name' => 'Smart TV', 'description' => 'Smart TV dan display digital untuk presentasi dan monitoring'],
            ['name' => 'Software', 'description' => 'Software dan aplikasi: POS, ERP, dan sistem bisnis lainnya'],
            ['name' => 'Storage', 'description' => 'HDD, MicroSD, memory card untuk penyimpanan data dan CCTV'],
            ['name' => 'Switch', 'description' => 'Network switch unmanaged dan managed untuk distribusi LAN'],
            ['name' => 'Switch PoE', 'description' => 'Switch PoE untuk CCTV IP dan perangkat jaringan PoE'],
            ['name' => 'USB Hub', 'description' => 'USB Hub untuk memperbanyak port USB'],
        ];

        foreach ($categories as $category) {
            ProductCategory::query()->firstOrCreate(
                ['name' => $category['name']],
                array_merge($category, ['status' => 'active'])
            );
        }
    }
}
