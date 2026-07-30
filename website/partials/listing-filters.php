<form method="get" action="<?= e(siteWebsiteUrl('listing')) ?>" class="live-filter-form">
    <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?= e($type) ?>"><?php endif; ?>
    <?php if ($filters['q'] !== ''): ?><input type="hidden" name="q" value="<?= e($filters['q']) ?>"><?php endif; ?>

    <label class="filter-label" for="city_<?= isset($filterFormIndex) ? e((string) $filterFormIndex) : 'default' ?>">City</label>
    <select name="city" id="city_<?= isset($filterFormIndex) ? e((string) $filterFormIndex) : 'default' ?>" class="filter-control">
        <option value="">All active cities</option>
        <?php foreach ($cities as $city): ?>
            <option value="<?= e((string) $city['name']) ?>" <?= $filters['city'] === (string) $city['name'] ? 'selected' : '' ?>>
                <?= e((string) $city['name']) ?> (<?= e((string) $city['property_count']) ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <label class="filter-label" for="property_type_<?= isset($filterFormIndex) ? e((string) $filterFormIndex) : 'default' ?>">Property Type</label>
    <select name="property_type_id" id="property_type_<?= isset($filterFormIndex) ? e((string) $filterFormIndex) : 'default' ?>" class="filter-control">
        <option value="">All property types</option>
        <?php foreach ($filterData['property_types'] as $propertyType): ?>
            <option value="<?= e((string) $propertyType['id']) ?>" <?= $filters['property_type_id'] === (int) $propertyType['id'] ? 'selected' : '' ?>>
                <?= e((string) $propertyType['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label class="filter-label" for="budget_<?= isset($filterFormIndex) ? e((string) $filterFormIndex) : 'default' ?>">Budget</label>
    <select name="budget" id="budget_<?= isset($filterFormIndex) ? e((string) $filterFormIndex) : 'default' ?>" class="filter-control">
        <option value="">Any budget</option>
        <option value="low" <?= $filters['budget'] === 'low' ? 'selected' : '' ?>>Budget friendly</option>
        <option value="mid" <?= $filters['budget'] === 'mid' ? 'selected' : '' ?>>Mid range</option>
        <option value="premium" <?= $filters['budget'] === 'premium' ? 'selected' : '' ?>>Premium</option>
    </select>

    <?php if (!in_array($type, ['commercial', 'plots', 'pg'], true)): ?>
        <label class="filter-label" for="bhk_<?= isset($filterFormIndex) ? e((string) $filterFormIndex) : 'default' ?>">Bedrooms</label>
        <select name="bhk" id="bhk_<?= isset($filterFormIndex) ? e((string) $filterFormIndex) : 'default' ?>" class="filter-control">
            <option value="">Any BHK</option>
            <?php foreach ([1, 2, 3, 4] as $bedroom): ?>
                <option value="<?= $bedroom ?>" <?= $filters['bhk'] === $bedroom ? 'selected' : '' ?>><?= $bedroom === 4 ? '4+ BHK' : $bedroom . ' BHK' ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

    <label class="filter-label" for="area_<?= isset($filterFormIndex) ? e((string) $filterFormIndex) : 'default' ?>">Minimum Area</label>
    <input name="min_area" id="area_<?= isset($filterFormIndex) ? e((string) $filterFormIndex) : 'default' ?>" class="filter-control" type="number" min="0" step="50" value="<?= $filters['min_area'] > 0 ? e((string) $filters['min_area']) : '' ?>" placeholder="Any area">

    <button class="listing-search-btn live-filter-submit" type="submit">Apply Filters</button>
</form>
