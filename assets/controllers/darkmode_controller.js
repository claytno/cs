/***
    Agregar las siguientes propiedades a cualquier item
    data-controller="darkmode" data-action="click->darkmode#toggle"
***/
import { Controller } from '@hotwired/stimulus';

var status = false;

export default class extends Controller {

    connect() {
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            this.dark();
            status = true;
        } else {
            this.light();
            status = false;
        }
    }

    toggle() {
        if (!status) {
            this.dark();
            localStorage.setItem('color-theme', 'dark');
        } else {
            this.light();
            localStorage.setItem('color-theme', 'light');
        }
        status = !status;
    }

    dark() {
        document.getElementsByTagName("html")[0].classList.add("dark");
        document.getElementById("theme-toggle-dark-icon").classList.remove("hidden");
        document.getElementById("theme-toggle-light-icon").classList.add("hidden");
    }

    light() {
        document.getElementsByTagName("html")[0].classList.remove("dark");
        document.getElementById("theme-toggle-dark-icon").classList.add("hidden");
        document.getElementById("theme-toggle-light-icon").classList.remove("hidden");
    }
}