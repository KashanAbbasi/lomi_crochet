$(function () {
  if (!$('#checkoutForm').length) return;
  const cart = App.getCart();
  const subtotal = cart.reduce((sum, item) => sum + Number(item.price) * Number(item.quantity), 0);
  $('#orderSummary').html(cart.map(item => `<li class="list-group-item d-flex justify-content-between"><span>${item.name} × ${item.quantity}</span><strong>${App.formatCurrency(item.price * item.quantity)}</strong></li>`).join(''));
  $('#checkoutTotal').text(App.formatCurrency(subtotal));

  $('#checkoutForm').on('submit', function (e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(this).entries());
    data.items = cart;
    $.ajax({
      url: 'includes/api.php?action=checkout',
      method: 'POST',
      data: JSON.stringify(data),
      contentType: 'application/json'
    }).done(({ order_number }) => {
      localStorage.removeItem(App.cartKey);
      window.location.href = `success.html?order=${order_number}`;
    }).fail((xhr) => App.toast(xhr.responseJSON?.message || 'Checkout failed', 'danger'));
  });
});
