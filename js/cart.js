function renderCart() {
  if (!$('#cartTable').length) return;
  const cart = App.getCart();
  let total = 0;
  $('#cartTable').html(cart.map(item => {
    const line = Number(item.price) * Number(item.quantity);
    total += line;
    return `<tr>
      <td><div class="d-flex align-items-center gap-3"><img src="${item.image_url}" width="70" height="70" class="rounded object-cover"><div><strong>${item.name}</strong></div></div></td>
      <td>${App.formatCurrency(item.price)}</td>
      <td><input type="number" class="form-control quantity-input" data-id="${item.product_id}" value="${item.quantity}" min="1"></td>
      <td>${App.formatCurrency(line)}</td>
      <td><button class="btn btn-sm btn-outline-danger remove-item" data-id="${item.product_id}">Remove</button></td>
    </tr>`;
  }).join('') || '<tr><td colspan="5" class="text-center py-5">Your cart is empty.</td></tr>');
  $('#cartTotal').text(App.formatCurrency(total));
}

$(function () {
  renderCart();
  $(document).on('change', '.quantity-input', function () {
    const productId = $(this).data('id');
    const quantity = Number($(this).val()) || 1;
    App.updateLocalQuantity(productId, quantity);
    $.post('includes/api.php?action=cart_update', { product_id: productId, quantity });
    renderCart();
  });
  $(document).on('click', '.remove-item', function () {
    const productId = $(this).data('id');
    App.removeFromLocalCart(productId);
    $.post('includes/api.php?action=cart_remove', { product_id: productId });
    App.toast('Item removed.', 'warning');
    renderCart();
  });
});
