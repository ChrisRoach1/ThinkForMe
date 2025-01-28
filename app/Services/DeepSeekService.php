<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;

class DeepSeekService
{

    private $apikey;
    private $deepseekApiUrl;
    private $deepseekModel;
    public function __construct()
    {
        $this->apikey = config('deepseekApiKey');
        $this->deepseekApiUrl = 'https://api.deepseek.com/chat/completions';
        $this->deepseekModel = 'deepseek-reasoner';
    }

    public function CallApi($prompt){
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apikey,
            'Content-Type' => 'application/json',
        ])->post($this->deepseekApiUrl, [
            'model' => $this->deepseekModel,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a principal software engineer with an eye for small business ideas.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return $response->json();
    }
}
