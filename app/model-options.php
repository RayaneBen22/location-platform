<?php $currentBrand = null; ?>
<?php foreach ($models as $model): ?>
    <?php if ($currentBrand !== $model['brand_name']): ?>
        <?php if ($currentBrand !== null): ?>
            </optgroup>
        <?php endif; ?>
        <?php $currentBrand = $model['brand_name']; ?>
        <optgroup label="<?= htmlspecialchars($currentBrand, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <option value="<?= (int) $model['id'] ?>" <?= (int) $model['id'] === $modelId ? 'selected' : '' ?>>
        <?= htmlspecialchars($model['brand_name'] . ' ' . $model['model_name'], ENT_QUOTES, 'UTF-8') ?>
    </option>
<?php endforeach; ?>
<?php if ($currentBrand !== null): ?>
    </optgroup>
<?php endif; ?>
