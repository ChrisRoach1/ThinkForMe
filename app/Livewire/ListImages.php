<?php

namespace App\Livewire;

use App\Models\Image;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
class ListImages extends Component implements HasForms, HasInfolists
{
    use InteractsWithInfolists;
    use InteractsWithForms;

    public function imageInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state([
                'images' => Image::query()->where('user_id', auth()->id())->orderByDesc('created_at')->get()
            ])
            ->schema([
                RepeatableEntry::make('images')
                    ->schema([
                        ImageEntry::make('ImageURL')->label("")
                            ->hint("Download")
                            ->hintAction(
                            Action::make('Download')
                                ->icon('heroicon-m-folder-arrow-down')
                                ->iconButton()
                                ->color('primary')->action(function (Image $record) {
                                    return Storage::disk('r2')->download($record->ImageName);
                                }),
                        ),

                    ])->grid(['sm'=> 2, 'lg'=>4])->contained(false)
            ]);
    }

    public function render()
    {
        return view('livewire.image.list-images');
    }
}
