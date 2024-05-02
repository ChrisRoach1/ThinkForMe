<?php

namespace App\Livewire;

use App\Models\Image;
use App\Models\Project;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use OpenAI\Laravel\Facades\OpenAI;

class ListProjects extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public int $creditCost = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(Project::query()->where('user_id', auth()->id())->orderByDesc('created_at'))
            ->columns([
                TextColumn::make('GeneratedProjectTitle')->sortable()->searchable(),
                TextColumn::make('DeveloperLevel')->sortable()->searchable(),
                TextColumn::make('created_at')->label("Generated")->DateTime()->since()
                    ->sortable()->searchable(),
            ])
            ->actions([
                Action::make('delete')
                    ->requiresConfirmation()
                    ->iconButton()
                    ->tooltip("delete project")
                    ->icon('heroicon-m-trash')
                    ->color("danger")
                    ->action(fn (Project $record) => $record->delete()),
                Action::make('View')
                    ->iconButton()
                    ->tooltip("view project")
                    ->color('secondary')
                    ->icon('heroicon-m-eye')
                    ->modalContent(fn (Project $record): View => view(
                        'components.project-modal',
                        ['project' => $record],
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
                Action::make('Create Image')
                    ->requiresConfirmation()
                    ->iconButton()
                    ->icon('heroicon-m-cog')
                    ->modalHeading('Create Image')
                    ->modalDescription("Creating an image will cost you {$this->creditCost} credits, are you sure?")
                    ->modalSubmitActionLabel('Yes, generate it')
                    ->action(fn (Project $record) => $this->createImage($record->GeneratedIdea)),
            ]);
    }

    private function createImage(string $projectDescription): void
    {
        $prompt = "
        Attention AI: YOU MUST FOLLOW THE FOLLOWING RULES FOR THE PROMPT:
        1.) Avoid any additional elements or color palette indications THIS MUST BE FOLLOWED.
        2.) Do not include anything extra, JUST the icon.
        3.) The icon should be one that could be used on a website as the application icon - sort of like a logo.
        4.) NO WORDS SHOULD BE INCLUDED IN IMAGE

        This icon should encapsulate the following project idea: {$projectDescription}. The overall style should be cartoonish but with a touch of realism. Use neutral colors.";

        $response = OpenAI::images()->create([
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024',
            'quality' => 'standard',
            'response_format' => 'b64_json',
        ]);
        $fileName = auth()->id() . '_' . date('y-m-d') . '_' . rand(0, 5000) . '.jpg';
        $imageUrl = env('CLOUDFLARE_PUBLIC_URL', '') . '/thinkforme/' . $fileName;
        $data = base64_decode($response->data[0]["b64_json"]);
        Storage::disk('r2')->put(path: $fileName, contents: $data);

        Image::create([
            "user_id" => auth()->id(),
            "Description" => $projectDescription,
            "Color" => "neutral",
            "Style" => "cartoonish but with a touch of realism",
            'ImageURL' => $imageUrl,
            'ImageName' => $fileName,
            "Model" => "dall-e-3"
        ]);

        auth()->user()->decrement('credits', $this->creditCost);

        Notification::make()
            ->title('Saved successfully')
            ->success()
            ->color('success')
            ->duration(5000)
            ->send();

        $this->redirect('/create-image');
    }

    public function render()
    {
        return view('livewire.project.list-projects');
    }
}
