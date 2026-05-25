<?php

declare(strict_types=1);

?>
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title fs-5" id="confirmModalLabel">Confirmar ação</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="d-flex gap-3">
                    <div class="confirm-modal-icon flex-shrink-0" aria-hidden="true">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <p class="mb-0 text-secondary" id="confirmModalMessage">Deseja continuar?</p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmModalSubmit">
                    <i class="bi bi-check-lg"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>