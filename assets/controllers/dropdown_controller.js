/***
    Agregar las siguientes propiedades
    Boton que abre y cierra:
    data-action="click->dropdown#toggle" data-dropdown-target="button"
    Padre del boton y lista
    data-controller="dropdown"
    Lista con contenido
    data-dropdown-target="list"

    Ejemplo:

    <div data-controller="dropdown">
        <button type="button" class="w-full flex" data-action="dropdown#toggle" data-dropdown-target="button">
            Boton
        </button>
        <ul class="my-2" data-dropdown-target="list">
            <li>Opcion 1</li>
            <li>Opcion 2</li>
            <li>Opcion 3</li>
        </ul>
    </div>
***/
import { Controller } from '@hotwired/stimulus';

var status = false;

export default class extends Controller {
    static targets = [
        "button",
        "list"
    ];

    connect() {
        this.buttonTarget.insertAdjacentHTML('beforeend', '<svg class="size-6 self-center inline ml-auto" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>');
        this.listTarget.classList.add("hidden");
    }

    toggle() {
        if (!status) {
            this.open();
        } else {
            this.close();
        }
        status = !status;
    }

    close() {
        this.buttonTarget.lastChild.classList.remove("rotate-180");
        this.listTarget.classList.add("hidden");
    }

    open() {
        this.buttonTarget.lastChild.classList.add("rotate-180");
        this.listTarget.classList.remove("hidden");
    }
}