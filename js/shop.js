$(function () {
  if (!$('#product-list').length) return;

  const loadProducts = () => {
    const params = {
      action: 'products',
      search: $('#searchInput').val(),
      category: $('#categoryFilter').val(),
      sort: $('#sortFilter').val()
    };
    $.getJSON('includes/api.php', params, ({ products }) => {
      const html = products.map(product => `
        <div class="col-md-6 col-lg-4">
          <div class="product-card h-100">
            <img src="${product.image_url || 'images/product-placeholder.svg'}" class="w-100 object-cover" alt="${product.name}">
            <div class="p-4">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge-soft">${product.category_name || 'Craft'}</span>
                <span class="rating-stars">${App.renderStars(product.average_rating)}</span>
              </div>
              <h5>${product.name}</h5>
              <p class="text-muted">${product.short_description || product.description.substring(0, 90)}...</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="price">${App.formatCurrency(product.price)}</span>
                <div class="d-flex gap-2">
                  <a href="product.html?id=${product.id}" class="btn btn-outline-dark btn-sm">View</a>
                  <button class="btn btn-primary btn-sm add-to-cart" data-product='${JSON.stringify({product_id: product.id, name: product.name, price: product.price, image_url: product.image_url || 'images/product-placeholder.svg'})}'>Add</button>
                </div>
              </div>
            </div>
          </div>
        </div>`).join('');
      $('#product-list').html(html || '<div class="col-12"><div class="alert alert-light">No products found.</div></div>');
    });
  };

  App.loadCategories(['#categoryFilter']);
  loadProducts();

  $('#searchForm, #sortFilter, #categoryFilter').on('submit change', loadProducts);
  $('#searchInput').on('keyup', () => window.clearTimeout(window.searchTimer) || (window.searchTimer = setTimeout(loadProducts, 400)));

  $(document).on('click', '.add-to-cart', function () {
    const product = $(this).data('product');
    App.addToLocalCart(product, 1);
    $.post('includes/api.php?action=cart_add', { product_id: product.product_id, quantity: 1 }).always(() => {
      App.toast('Product added to cart.');
    });
  });
});
