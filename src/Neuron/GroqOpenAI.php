<?php

namespace App\Neuron;

use NeuronAI\Providers\OpenAI\OpenAI;

final class GroqOpenAI extends OpenAI
{
    protected string $baseUri = 'https://api.groq.com/openai/v1';
}
