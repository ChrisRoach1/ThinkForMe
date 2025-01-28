<?php

namespace App\Livewire;

use App\Models\Project;
use App\Services\DeepSeekService;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\NoReturn;
use Livewire\Component;
use Filament\Actions\Concerns\InteractsWithActions;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

class CreateProject extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public int $creditCost = 1;
    public Project $project;
    public ?array $data = [];

    public function mount(DeepSeekService $deepseekService): void
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
                                ->options([
                                    "Junior" => "Junior",
                                    "Mid-Level" => "Mid-Level",
                                    "Senior" => "Senior",
                                    "Architect" => "Architect",
                                ])
                                ->required(),
                            Textarea::make('TechSpecs')
                                ->helperText("Provide what tech you want to use, languages, rough ideas for the project, etc...")
                                ->required()
                                ->maxLength(500),
                            Actions::make([
                                Action::make('Create')
                                    ->requiresConfirmation()
                                    ->modalHeading('Create Project')
                                    ->disabled(!auth()->user()->canGenerate($this->creditCost))
                                    ->modalDescription("Creating a project will cost you {$this->creditCost} credit, are you sure?")
                                    ->modalSubmitActionLabel('Yes, generate it')
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
        try{
            if(auth()->user()->credits >= $this->creditCost){
                $projectDescriptionResponse = $this->CallContentPrompt();
                $projectTitleResponse = $this->CallTitlePrompt($projectDescriptionResponse->choices[0]->message->content);

                $descriptionContent = $projectDescriptionResponse->choices[0]->message->content;
                $titleContent = $projectTitleResponse->choices[0]->message->content;

                if($descriptionContent == null || $titleContent == null){
                    Notification::make()
                        ->title('An error has occurred')
                        ->body("Can't generate project at this time, please try again.")
                        ->danger()
                        ->color('danger')
                        ->duration(5000)
                        ->send();

                    $this->redirect('/dashboard');
                }else{
                    Project::create([
                        "user_id" => auth()->id(),
                        "DeveloperLevel" => $this->form->getState()["data"]["DeveloperLevel"],
                        "TechSpecs" => $this->form->getState()["data"]["TechSpecs"],
                        "GeneratedProjectTitle" => $titleContent,
                        "GeneratedIdea" => $descriptionContent
                    ]);

                    auth()->user()->decrementCredits($this->creditCost);

                    Notification::make()
                        ->title('Saved successfully')
                        ->success()
                        ->color('success')
                        ->duration(5000)
                        ->send();

                    $this->redirect('/dashboard');
                }
            }
        }catch (\Exception $ex){
            Log::error($ex->getMessage());
        }
    }

    private function CallContentPrompt(): CreateResponse
    {

        $prompt = "You're looking to use the following tech specifications to build a new side project: \"{$this->form->getState()["data"]["TechSpecs"]}\".
                     These tech specifications could be what certain tech to use, architecture patterns, rough ideas for the project itself, etc... If it's just a rough idea with no technology specified, please advise on the best approach.
                     What sort of app would you build that that could be turned into a profitable SaaS? Please don't speak in the first person
                     when describing the idea. Be sure to keep the idea at 2000 characters or less. Give JUST THE IDEA with no special characters or prefixes at the beginning. Please remove any new line characters as well.
                    At the end of the Idea value please describe how you would start going about implementing the idea with the given tech specifications but don't speak in the first person.";



        return OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => "You are a {$this->form->getState()["data"]["DeveloperLevel"]} level software engineer"],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);
    }

    private function CallTitlePrompt($projectIdea): CreateResponse
    {

        $previousTitles = auth()->user()->projects()->pluck('GeneratedProjectTitle');
        $prevTitleString = "";

        foreach($previousTitles as $title){
            $prevTitleString .= $title . ", ";
        }

        $prompt = "You're looking to give a name to your side project with the following description: \"{$projectIdea}\".
                    Please give JUST the title and don't speak in the first person. Give JUST THE TITLE with no special characters or prefixes.
                     Also, given the following titles ensure the newly generated title is unique {$prevTitleString}";

        return OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => "You are a {$this->form->getState()["data"]["DeveloperLevel"]} level software engineer"],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);
    }

    public function render()
    {
        return view('livewire.project.create-project');
    }
}
