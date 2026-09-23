import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['metadata', 'editButton', 'deleteButton'];
    static values = {
        deleteActionName: String
    };

    connect() {
        this.overrideNativeFormSubmission();
        this.updateActionsState();
    }

    disconnect() {
        this.restoreNativeFormSubmission();
    }

    overrideNativeFormSubmission() {
        this.originalFormSubmit = HTMLFormElement.prototype.submit;
        const controller = this;
        HTMLFormElement.prototype.submit = function () {
            if (controller.isDeleteBatchForm(this)) {
                controller.addExpectedVersions(this);
            }
            return controller.originalFormSubmit.call(this);
        };
    }

    restoreNativeFormSubmission() {
        HTMLFormElement.prototype.submit = this.originalFormSubmit;
    }

    isDeleteBatchForm(form) {
        const actionName = form.querySelector('input[name="batchActionName"]')?.value;
        return actionName === this.deleteActionNameValue;
    }

    addExpectedVersions(form) {
        this.getSelectedAttributes().forEach(({ id, version }) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `expectedVersions[${id}]`;
            input.value = String(version);
            form.appendChild(input);
        });
    }

    selectionChanged(event) {
        if (!event.target.matches('input.form-batch-checkbox, input.form-batch-checkbox-all')) {
            return;
        }
        queueMicrotask(() => this.updateActionsState());
    }

    updateActionsState() {
        const selected = this.getSelectedAttributes();
        if (this.hasEditButtonTarget) {
            this.editButtonTarget.hidden = selected.length !== 1;
        }
        if (this.hasDeleteButtonTarget) {
            const containsBuiltIn = selected.some(({ builtIn }) => builtIn);
            this.deleteButtonTarget.hidden = selected.length === 0 || containsBuiltIn;
        }
    }

    getSelectedAttributes() {
        const selected = [];
        this.metadataTargets.forEach((metadata) => {
            const row = metadata.closest('tr');
            const checkbox = row.querySelector('input.form-batch-checkbox');
            if (!checkbox?.checked) {
                return;
            }
            selected.push({
                id: checkbox.value,
                version: Number(metadata.dataset.version),
                builtIn: metadata.dataset.builtIn === '1',
                editUrl: metadata.dataset.editUrl
            });
        });
        return selected;
    }

    editSelected(event) {
        event.preventDefault();
        const selected = this.getSelectedAttributes();
        if (selected.length !== 1) {
            return;
        }
        window.location.assign(selected[0].editUrl);
    }
}
