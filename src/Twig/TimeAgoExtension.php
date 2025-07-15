<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TimeAgoExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('time_ago', [$this, 'timeAgo']),
        ];
    }

    public function timeAgo($time)
    {

        $now = new \DateTimeImmutable();
        $diff = $time->diff($now);
        if ($diff->y > 0) {
            if ($diff->y > 1) {
                return "hace $diff->y años";
            }
            return "hace $diff->y año";
        } else if ($diff->m > 0) {
            if ($diff->m > 1) {
                return "hace $diff->m meses";
            }
            return "hace $diff->m mes";
        } else if ($diff->d > 0) {
            if ($diff->d > 1) {
                return "hace $diff->y días";
            }
            return "hace $diff->d día";
        } else if ($diff->h > 0) {
            if ($diff->h > 1) {
                return "hace $diff->h horas";
            }
            return "hace $diff->h hora";
        } else if ($diff->i > 0) {
            if ($diff->i > 1) {
                return "hace $diff->i minutos";
            }
            return "hace $diff->i minuto";
        } else if ($diff->s > 0) {
            if ($diff->s > 1) {
                return "hace $diff->s segundos";
            }
            return "hace $diff->y segundo";
        } else {
            return "ahora";
        }
    }
}