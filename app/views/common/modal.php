<?php

function modal_form_start(array $o): void
{
  $id = $o['id'];
  $size = $o['size'] ?? '';
  $titleId = !empty($o['titleId']) ? ' id="' . $o['titleId'] . '"' : '';
  ?>
  <div class="modal fade" id="<?= $id ?>" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog<?= $size ? " $size" : '' ?>">
      <div class="modal-content">
        <form id="<?= $o['formId'] ?>">
          <?php if (!empty($o['hasHiddenId'])): ?>
          <input type="hidden" name="id" id="<?= $o['hiddenId'] ?? $id . 'Id' ?>">
          <?php endif; ?>
          <div class="modal-header">
            <h5 class="modal-title"<?= $titleId ?>><?= htmlspecialchars($o['title']) ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
  <?php
}

function modal_form_end(?string $formId = null): void
{
  $saveText = $GLOBALS['_modal_save_text'] ?? 'Guardar';
  $saveClass = $GLOBALS['_modal_save_class'] ?? 'primary';
  $submitId = $GLOBALS['_modal_submit_id'] ?? '';
  ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-<?= $saveClass ?>"<?= $submitId ? ' id="' . $submitId . '"' : '' ?>><?= htmlspecialchars($saveText) ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php
  unset($GLOBALS['_modal_save_text'], $GLOBALS['_modal_save_class'], $GLOBALS['_modal_submit_id']);
}

function modal_form(array $o): void
{
  $GLOBALS['_modal_save_text'] = $o['saveText'] ?? 'Guardar';
  $GLOBALS['_modal_save_class'] = $o['saveClass'] ?? 'primary';
  $GLOBALS['_modal_submit_id'] = $o['submitId'] ?? '';
  modal_form_start($o);
}

function modal_detail_start(array $o): void
{
  $id = $o['id'];
  $size = $o['size'] ?? '';
  ?>
  <div class="modal fade" id="<?= $id ?>" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog<?= $size ? " $size" : '' ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><?= htmlspecialchars($o['title']) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body"<?= !empty($o['bodyId']) ? ' id="' . $o['bodyId'] . '"' : '' ?>>
  <?php
}

function modal_detail_end(): void
{
  ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
  <?php
}
