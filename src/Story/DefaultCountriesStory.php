<?php

namespace App\Story;

use Zenstruck\Foundry\Story;
use App\Factory\CountryFactory;

final class DefaultCountriesStory extends Story
{
    public function build(): void
    {
        CountryFactory::createMany(3);
    }
}
