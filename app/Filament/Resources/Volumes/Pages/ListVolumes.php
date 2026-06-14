<?php

namespace App\Filament\Resources\Volumes\Pages;

use App\Filament\Resources\Volumes\VolumeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVolumes extends ListRecords
{
    protected static string $resource = VolumeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
