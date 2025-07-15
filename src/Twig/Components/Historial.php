<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class Historial
{
    use DefaultActionTrait;

    public function getActividades()
    {
        return [
            1 => [
                "icono" => 1,
                "texto" => "Weastancio llegó",
                "detalle" => "Curao como raja pero llegó",
                "tiempo" => "Hace un rato atras",
            ],
            2 => [
                "icono" => 2,
                "texto" => "Weastancio se fue",
                "detalle" => "Seguramente a seguir dandole",
                "tiempo" => "Reciencito",
            ],
            3 => [
                "icono" => 3,
                "texto" => "Portón abierto",
                "tiempo" => "Hace 3 horas",
            ],
            4 => [
                "icono" => 3,
                "texto" => "Portón abierto",
                "tiempo" => "Hace 5 horas",
            ]
        ];
    }
}