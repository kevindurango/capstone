<?php
/**
 * Partial template for product rows
 * Used by the "load more" functionality in manager-product.php
 */

// Check if filteredProducts is defined
if (!isset($filteredProducts) || empty($filteredProducts)) {
    echo '<tr><td colspan="9" class="text-center text-muted">No more products to load</td></tr>';
    exit;
}

// Loop through the products and generate table rows
foreach ($filteredProducts as $product): 
?>
<tr class="product-row" 
    data-status="<?= htmlspecialchars($product['status'] ?? 'pending') ?>"
    data-category="<?= htmlspecialchars($product['category_id'] ?? '') ?>">
    <td>#<?= $product['product_id'] ?></td>
    <td>
        <?php if (!empty($product['image'])): ?>
            <img src="../../public/<?= htmlspecialchars($product['image']) ?>" class="product-image-thumbnail" alt="<?= htmlspecialchars($product['name']) ?>">
        <?php else: ?>
            <img src="../../public/assets/default-product.png" class="product-image-thumbnail" alt="Default Image">
        <?php endif; ?>
    </td>
    <td>
        <div class="font-weight-bold"><?= htmlspecialchars($product['name']) ?></div>
        <small class="text-muted">
            <?= mb_strimwidth(htmlspecialchars($product['description'] ?? ''), 0, 50, "...") ?>
        </small>
    </td>
    <td><?= htmlspecialchars($product['farmer_name'] ?? 'N/A') ?></td>
    <td>₱<?= number_format($product['price'], 2) ?></td>
    <td>
        <?php if ($product['stock'] <= 10): ?>
            <span class="stock-warning"><?= $product['stock'] ?></span>
        <?php else: ?>
            <?= $product['stock'] ?>
        <?php endif; ?>
        <span class="unit-badge"><?= htmlspecialchars($product['unit_type']) ?></span>
    </td>
    <td><?= htmlspecialchars($product['category'] ?? 'Uncategorized') ?></td>
    <td>
        <span class="badge badge-<?= getStatusBadgeClass($product['status'] ?? 'pending') ?>">
            <?= ucfirst(htmlspecialchars($product['status'] ?? 'pending')) ?>
        </span>
    </td>
    <td>
        <div class="btn-group">
            <button class="btn btn-sm btn-info view-product" 
                    data-id="<?= $product['product_id'] ?>"
                    title="View Product Details">
                <i class="bi bi-eye"></i>
            </button>
            <button class="btn btn-sm btn-success update-status" 
                    data-id="<?= $product['product_id'] ?>"
                    data-status="<?= htmlspecialchars($product['status'] ?? 'pending') ?>"
                    title="Update Status">
                <i class="bi bi-arrow-up-circle"></i>
            </button>
        </div>
    </td>
</tr>
<?php endforeach; ?>

<?php
?>