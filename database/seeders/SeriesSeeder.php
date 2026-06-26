<?php

namespace Database\Seeders;

use App\Models\Series;
use App\Models\Volume;
use Illuminate\Database\Seeder;

class SeriesSeeder extends Seeder
{
    public function run(): void
    {
        $seriesList = [
            ['title_romaji' => 'Berserk', 'status' => 'publishing', 'type' => 'manga', 'total_volumes' => 42, 'score' => 9.45],
            ['title_romaji' => 'Vinland Saga', 'status' => 'publishing', 'type' => 'manga', 'total_volumes' => 28, 'score' => 8.72],
            ['title_romaji' => 'Vagabond', 'status' => 'on_hiatus', 'type' => 'manga', 'total_volumes' => 37, 'score' => 9.10],
            ['title_romaji' => 'One Piece', 'status' => 'publishing', 'type' => 'manga', 'total_volumes' => 107, 'score' => 8.90],
            ['title_romaji' => 'Dungeon Meshi', 'title_english' => 'Delicious in Dungeon', 'status' => 'finished', 'type' => 'manga', 'total_volumes' => 14, 'score' => 8.65],
            ['title_romaji' => 'Chainsaw Man', 'status' => 'publishing', 'type' => 'manga', 'total_volumes' => 17, 'score' => 8.74],
            ['title_romaji' => 'Spy x Family', 'status' => 'publishing', 'type' => 'manga', 'total_volumes' => 13, 'score' => 8.12],
            ['title_romaji' => 'Frieren', 'title_english' => 'Frieren: Beyond Journey\'s End', 'status' => 'publishing', 'type' => 'manga', 'total_volumes' => 13, 'score' => 8.89],
            ['title_romaji' => 'Blue Period', 'status' => 'publishing', 'type' => 'manga', 'total_volumes' => 17, 'score' => 8.40],
            ['title_romaji' => 'Oyasumi Punpun', 'title_english' => 'Goodnight Punpun', 'status' => 'finished', 'type' => 'manga', 'total_volumes' => 13, 'score' => 8.73],
        ];

        foreach ($seriesList as $data) {
            $series = Series::create($data);

            $volumeCount = min($data['total_volumes'] ?? 3, 5);
            for ($i = 1; $i <= $volumeCount; $i++) {
                Volume::create([
                    'series_id'     => $series->id,
                    'volume_number' => $i,
                    'type'          => 'regular',
                ]);
            }
        }
    }
}
