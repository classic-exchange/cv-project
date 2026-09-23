import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ['dataType', 'options', 'option'];
    static values = {
        oneOfManyType: String,
        maxOptions: Number
    };

    connect() {
        this.updateOptions();
        this.element.classList.remove('attribute-options-loading');
        document.addEventListener('ea.collection.item-added', this.updateLimit);
        document.addEventListener('ea.collection.item-removed', this.updateLimit);
        this.updateLimit();
    }

    disconnect() {
        document.removeEventListener('ea.collection.item-added', this.updateLimit);
        document.removeEventListener('ea.collection.item-removed', this.updateLimit);
    }

    updateOptions() {
        const isOneOfMany = this.dataTypeTarget.value === this.oneOfManyTypeValue;
        this.optionsTarget.hidden = !isOneOfMany;
    }

    updateLimit = () => {
        const addButton = this.optionsTarget.querySelector('.field-collection-add-button');
        if (!addButton) {
            return;
        }
        addButton.disabled = this.optionTargets.length >= this.maxOptionsValue;
    }
}
