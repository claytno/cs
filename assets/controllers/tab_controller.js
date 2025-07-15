/***
    Agregar las siguientes propiedades
    Botones:
    data-action="click->tab#open"
    Padre de botones y contenido
    data-controller="tab"
    Botones
    data-tab-target="button" data-content="content1"
    Contenido
    data-tab-target="content" id="content1"
    Opcional contenido por defecto
    defaultbutton defaultcontent en el atributo "data-tab-target"

    Ejemplo:

    <div data-controller="tab" class="z-60">
        <div data-action="click->tab#open" data-tab-target="button defaultbutton" data-content="content1">
            Boton 1
        </div>
        <div data-action="click->tab#open" data-tab-target="button" data-content="content2">
            Boton 2
        </div>
        <div data-tab-target="content defaultcontent" id="content1">
            Content 1
        </div>
        <div data-tab-target="content" id="content2">
            Content 2
        </div>
    </div>
***/
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        "button",
        "content",
        "defaultbutton",
        "defaultcontent"
    ];

    connect() {
        this.contentTargets.forEach((element) => {
            element.classList.add("hidden");
        });
        if (this.hasDefaultbuttonTarget) {
            this.defaultbuttonTarget.classList.add("active");
        }
        if (this.hasDefaultcontentTarget) {
            this.defaultcontentTarget.classList.remove("hidden");
        }
    }

    open(event) {
        event.target.classList.add("active");
        let contentId = event.target.dataset.content;
        this.buttonTargets.forEach((element) => {
            if (element != event.target) {
                element.classList.remove("active");
            }
        });
        this.contentTargets.forEach((element) => {
            if (element.id == contentId) {
                element.classList.remove("hidden");
            } else {
                element.classList.add("hidden");
            }
        });
    }
}