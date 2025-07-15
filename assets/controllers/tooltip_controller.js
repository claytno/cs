/***
    Agregar las siguientes propiedades
    Elemento que tendra el tooltip
    data-controller="tooltip" data-action="click->darkmode#toggle mouseenter->tooltip#show mouseleave->tooltip#hide"
    Div del tooltip
    data-tooltip-target="tooltip"

    Ejemplo:

    <a href="#" data-controller="tooltip" data-action="click->darkmode#toggle mouseenter->tooltip#show mouseleave->tooltip#hide">
        Contenido
        <div data-tooltip-target="tooltip" class="absolute z-40 left-0 bottom-12 w-max">
            Configuración
        </div>
    </a>
***/
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        "tooltip"
    ];

    connect() {
        this.tooltipTarget.classList.add("hidden", "opacity-0");
    }

    hide() {
        this.tooltipTarget.classList.add("hidden", "opacity-0");
    }

    show() {
        this.tooltipTarget.classList.remove("hidden", "opacity-0");
    }
}