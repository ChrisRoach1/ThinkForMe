<?php

namespace App\Livewire;

use App\Enums\DeveloperLevel;
use App\Models\Project;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use JetBrains\PhpStorm\NoReturn;
use Livewire\Component;
use Filament\Actions\Concerns\InteractsWithActions;
use OpenAI\Laravel\Facades\OpenAI;

class CreateProject extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;


    public Project $project;
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return
            $form
                ->schema([
                    Section::make("Generate")
                        ->schema([
                            Select::make('DeveloperLevel')
                                ->enum(DeveloperLevel::class)
                                ->options(DeveloperLevel::class)
                                ->required(),
                            Textarea::make('TechSpecs')
                                ->helperText("Provide what tech you want to use, languages, etc...")
                                ->required()
                                ->maxLength(500),
                            Actions::make([
                                Action::make('Create')
                                    ->action(fn() => $this->create())
                            ])
                        ])->collapsible()
                        ->statePath('data')
                ]);

    }


    protected function getFormModel(): Project
    {
        return $this->project;
    }

    #[NoReturn]
    public function create(): void
    {

        $prompt = "Pretend you are a {$this->form->getState()["data"]["DeveloperLevel"]} level software engineer 
                     and you're looking to use the following tech specifications to build a new side project: \"{$this->form->getState()["data"]["TechSpecs"]}\".
                     These tech specifications are could be what certain tech to use, architecture patterns, etc...
                     What sort of app would you build that that could be turned into a profitable SaaS? Please also give me a title for the application and don't speak in the first person
                     when describing the idea. 
                     Give the title first followed by the idea. Format the response as a valid JSON object that looks like the following:
                     {
                     \"Title\": ...,
                     \"Idea\": ...
                     }
                     At the end of the Idea value please describe how you would start going about implementing the idea with the given tech specifications but don't speak in the first person.
                     ";


        $result = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo-0125',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $decodedContent = json_decode($result->choices[0]->message->content, true);
        //dd($result->choices[0]->message->content);
        Project::create([
            "user_id" => auth()->id(),
            "DeveloperLevel" => $this->form->getState()["data"]["DeveloperLevel"],
            "TechSpecs" => $this->form->getState()["data"]["TechSpecs"],
            "GeneratedProjectTitle" => $decodedContent["Title"],
            "GeneratedIdea" => $decodedContent["Idea"],
        ]);

        Notification::make()
            ->title('Saved successfully')
            ->success()
            ->color('success')
            ->duration(5000)
            ->send();
        
        $this->redirect('/dashboard');
    }

    public function render()
    {
        return view('livewire.create-project');
    }
}
