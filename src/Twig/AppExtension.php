<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('price', [$this, 'formatPrice']),
            new TwigFilter('strPad', [$this, 'strPad']),
        ];
    }

    public function formatPrice(float $number, int $decimals = 0, string $decPoint = '.', string $thousandsSep = ','): string
    {
        $price = number_format($number, $decimals, $decPoint, $thousandsSep);
        $price = 'XOF' . $price;

        return $price;
    }

    public function strPad($number, $pad_length, $pad_string): string
    {
        return str_pad($number, $pad_length, $pad_string, STR_PAD_LEFT);
    }
}
