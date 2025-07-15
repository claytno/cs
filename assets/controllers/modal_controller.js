/***
    Agregar las siguientes propiedades
    Botones que abren o cierran:
    data-action="click->modal#open" o data-action="click->modal#close"
    Padre del boton y modal
    data-controller="modal" (opcional) data-modal-mode-value="static"
    Modal con contenido
    data-modal-target="modal"

    Ejemplo:

    <div class="inline" data-controller="modal">
        <button type="button" data-action="modal#open">Log in</button>
        <div data-modal-target="modal" data-action="modal#close">
            <button type="button" data-action="modal#close">
                Close modal
            </button>
        </div>
    </div>
***/
import { Controller } from '@hotwired/stimulus';

const backdrop = document.createElement('div');
backdrop.id = "backdrop";
backdrop.classList.add('bg-gray-900/50', 'dark:bg-gray-900/80', 'fixed', 'inset-0', 'z-40');
backdrop.dataset.action = "click->modal#close";

export default class extends Controller {
    static targets = [
        "modal"
    ];
    static values = {
        mode: String
    };

    connect() {
        this.modalTarget.classList.add("hidden", "opacity-0");
        if (this.modeValue == "static") {
            backdrop.dataset.action = "";
        }
    }

    close() {
        this.modalTarget.classList.add("hidden", "opacity-0");
        backdrop.remove();
    }

    open() {
        this.modalTarget.classList.remove("hidden", "opacity-0");
        this.modalTarget.insertBefore(backdrop, this.modalTarget.firstChild);
    }
}