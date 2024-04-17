<?php

namespace App\Livewire;

use App\Models\Project;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ListProjects extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(Project::query()->where('user_id', auth()->id()))
            ->columns([
                TextColumn::make('GeneratedProjectTitle'),
                TextColumn::make('DeveloperLevel'),
            ])
            ->filters([
                // ...
            ])
            ->actions([
                Action::make('delete')
                    ->requiresConfirmation()
                    ->icon('heroicon-m-trash')
                    ->color("danger")
                    ->action(fn (Project $record) => $record->delete()),
                Action::make('View')
                    ->icon('heroicon-m-eye')
                    ->modalContent(fn (Project $record): View => view(
                        'project',
                        ['project' => $record],
                    ))
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
            ])
            ->bulkActions([
                // ...
            ]);
    }

    public function render()
    {
        return view('livewire.list-projects');
    }
}
