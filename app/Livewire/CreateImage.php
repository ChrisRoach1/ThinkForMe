<?php

namespace App\Livewire;

use App\Models\Image;
use Filament\Forms\Components\Checkbox;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions\Concerns\InteractsWithActions;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Images\CreateResponse;
class CreateImage extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public int $creditCost = 3;
    public ?array $data = [];
    public Image $image;

    public function mount(): void
    {
        $this->form->fill();
    }
    public function form(Form $form): Form
    {
        return
            $form
                ->live()
                ->schema([
                    Section::make("")
                        ->schema([
                            TextInput::make('Color')->type("color")->required(),
                            TextInput::make('Description')
                                ->helperText('Describe your image')
                                ->disabled(fn($get) => $get("override") == true)
                                ->required(fn($get) => $get("override") == false),
                            Textarea::make('Style')
                                ->helperText("Describe the style of your image")
                                ->required()
                                ->maxLength(500),
                            Checkbox::make('override')
                                ->helperText("Take full control and enter your own prompt in the style field.")
                                ->required(),
                            Actions::make([
                                Action::make('Create')
                                    ->requiresConfirmation()
                                    ->modalHeading('Create Image')
                                    ->disabled(!auth()->user()->canGenerate($this->creditCost))
                                    ->modalDescription("This will cost you {$this->creditCost} credits, are you sure?")
                                    ->modalSubmitActionLabel('Yes, generate it')
                                    ->action(fn() => $this->create())
                            ])
                        ])->statePath('data')
                ]);

    }

    protected function getFormModel(): Image
    {
        return $this->image;
    }

    public function create(): void
    {
        try{
            $overrideActive = $this->form->getState()["data"]["override"];

            if($overrideActive) {
                $result = $this->CallOverridePrompt();
            }else{
                $result = $this->CallPrompt();
            }

            $fileName = Str::uuid()->toString() . auth()->id() . '_' . date('y-m-d') . '_' . rand(0, 5000) . '.jpg';
            $imageUrl = env('CLOUDFLARE_PUBLIC_URL', '') . '/thinkforme/' . $fileName;
            $data = base64_decode($result->data[0]["b64_json"]);
            Storage::disk('r2')->put(path: $fileName, contents: $data);

            Image::create([
                "user_id" => auth()->id(),
                "Description" => $overrideActive ? "" : $this->form->getState()["data"]["Description"],
                "Color" => $this->form->getState()["data"]["Color"],
                "Style" => $this->form->getState()["data"]["Style"],
                'ImageURL' => $imageUrl,
                'ImageName' => $fileName,
                "Model" => "dall-e-3"
            ]);

            auth()->user()->decrementCredits($this->creditCost);

            Notification::make()
                ->title('Saved successfully')
                ->success()
                ->color('success')
                ->duration(5000)
                ->send();

            $this->redirect('/create-image');
        }catch (\Exception $ex){
            Log::error($ex->getMessage());
        }


    }

    private function CallPrompt(): CreateResponse
    {
        $prompt = "
        Attention AI: YOU MUST FOLLOW THE FOLLOWING RULES FOR THE PROMPT:
        1.) Avoid any additional elements or color palette indications THIS MUST BE FOLLOWED.
        2.) Do not include anything extra, JUST the icon.
        3.) The icon should be one that could be used on a website as the application icon - sort of like a logo.
        4.) NO WORDS SHOULD BE INCLUDED IN IMAGE
        5.) IT SHOULD BE A SINGLE IMAGE NOT THE SAME IMAGE MULTIPLE TIMES
        6.) THE ICON SHOULD HAVE ROUNDED CORNERS
       
        The icon features a \"{$this->form->getState()["data"]["Description"]}\".
                        The overall style should be a touch of realism and {$this->form->getState()["data"]["Style"]},. 
                        The color used should be the closest color to this hex value {$this->form->getState()["data"]["Color"]}.";

        return OpenAI::images()->create([
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024',
            'quality' => 'standard',
            'response_format' => 'b64_json',
        ]);
    }

    private function CallOverridePrompt(): CreateResponse
    {
        $prompt =  "{$this->form->getState()["data"]["Style"]}. The color used should be the closest color to this hex value {$this->form->getState()["data"]["Color"]}.";

        return OpenAI::images()->create([
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024',
            'quality' => 'standard',
            'response_format' => 'b64_json',
        ]);
    }


    public function render()
    {
        return view('livewire.image.create-image');
    }


}
