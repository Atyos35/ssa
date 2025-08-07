<?php

namespace App\Shared\Story;

use Zenstruck\Foundry\Story;
use App\Shared\Factory\CountryFactory;

final class DefaultCountriesStory extends Story
{
    public function build(): void
    {
        CountryFactory::createMany(3);
    }
}
