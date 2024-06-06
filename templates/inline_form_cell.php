<?php
/** @var callable $trans */
/** @var string $entityName */
/** @var string $fieldName */
/** @var string $value */
/** @var \S2\AdminYard\Form\Form $form */
/** @var array $primaryKey */

$formQueryParams = http_build_query(array_merge([
    'entity' => $entityName,
    'action' => 'patch',
    'field'  => $fieldName,
], $primaryKey));

$formId   = 'id-' . md5(serialize($primaryKey) . $fieldName);
$holderId = $formId . '-' . $fieldName;

?>
<span class="inline-form-holder" id="<?= $holderId ?>"><?= $value ?></span>
<form class="inline-form" id="<?= $formId ?>" method="POST" action="?<?= $formQueryParams ?>">
    <?php foreach ($form->getHiddenControls() as $control): ?>
        <?= $control->getHtml() ?>
    <?php endforeach; ?>
    <?php foreach ($form->getVisibleControls() as $fieldName => $control): ?>
        <div
            class="form-control-<?= strtolower(basename(strtr(get_class($control), ['\\' => '/']))) ?> field-<?= $entityName ?>-<?= $fieldName ?>">
            <span class="input-wrapper"><?= $control->getHtml() ?></span>
            <span class="validation-errors"></span>
        </div>
    <?php endforeach; ?>
</form>
<script>makeInlineForm('<?= $formId ?>', <?= json_encode($trans('Something went wrong and the value was not saved.'), JSON_THROW_ON_ERROR) ?>);</script>
