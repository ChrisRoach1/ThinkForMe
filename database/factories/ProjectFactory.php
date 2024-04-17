<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Project;

class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'DeveloperLevel' => $this->faker->word(),
            'TechSpecs' => $this->faker->regexify('[A-Za-z0-9]{500}'),
            'GeneratedProjectTitle' => $this->faker->word(),
            'GeneratedIdea' => $this->faker->word(),
        ];
    }
}
