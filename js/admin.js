$(function () {
  if (!$('#adminRoot').length) return;

  const loadStats = () => $.getJSON('includes/api.php', { action: 'admin_stats' }).done(({ stats }) => {
    $('#statProducts').text(stats.products);
    $('#statUsers').text(stats.users);
    $('#statOrders').text(stats.orders);
    $('#statRevenue').text(App.formatCurrency(stats.revenue));
  });

  const loadProducts = () => $.getJSON('includes/api.php', { action: 'admin_products' }).done(({ products }) => {
    $('#adminProductsTable').html(products.map(product => `<tr>
      <td>${product.name}</td><td>${product.category_name || '-'}</td><td>${App.formatCurrency(product.price)}</td><td>${product.stock}</td>
      <td><button class="btn btn-sm btn-outline-danger delete-product" data-id="${product.id}">Delete</button></td></tr>`).join(''));
  });

  App.loadCategories(['#adminCategory']);
  loadStats();
  loadProducts();

  $('#productForm').on('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    $.ajax({ url: 'includes/api.php?action=admin_save_product', method: 'POST', data: formData, processData: false, contentType: false })
      .done(() => { App.toast('Product saved.'); this.reset(); loadProducts(); loadStats(); })
      .fail(() => App.toast('Save failed.', 'danger'));
  });

  $(document).on('click', '.delete-product', function(){
    $.post('includes/api.php?action=admin_delete_product', { id: $(this).data('id') })
      .done(() => { App.toast('Product deleted.', 'warning'); loadProducts(); loadStats(); })
      .fail(() => App.toast('Delete failed.', 'danger'));
  });
});
