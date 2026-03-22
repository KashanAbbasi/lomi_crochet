$(function () {
  if (!$('#productDetail').length) return;
  const id = new URLSearchParams(window.location.search).get('id');
  $.getJSON('includes/api.php', { action: 'product', id }, ({ product, reviews }) => {
    $('#productDetail').html(`
      <div class="col-lg-6"><img src="${product.image_url || 'images/product-placeholder.svg'}" class="img-fluid rounded-4 shadow-sm" alt="${product.name}"></div>
      <div class="col-lg-6">
        <span class="badge-soft mb-3 d-inline-block">${product.category_name || 'Craft'}</span>
        <h1>${product.name}</h1>
        <div class="rating-stars mb-2">${App.renderStars(product.average_rating)} <span class="text-muted">(${product.review_count} reviews)</span></div>
        <div class="price mb-3">${App.formatCurrency(product.price)}</div>
        <p class="text-muted">${product.description}</p>
        <div class="d-flex gap-3 align-items-center mb-4"><input type="number" id="qty" class="form-control w-auto" min="1" value="1"><button id="addProductToCart" class="btn btn-primary">Add to Cart</button></div>
        <ul class="list-unstyled small text-muted"><li><strong>Stock:</strong> ${product.stock}</li><li><strong>SEO title:</strong> ${product.seo_title || product.name}</li></ul>
      </div>`);
    $('#reviewList').html(reviews.map(review => `<div class="border rounded-4 p-3 mb-3"><div class="d-flex justify-content-between"><strong>${review.name}</strong><span class="rating-stars">${App.renderStars(review.rating)}</span></div><p class="mb-0 text-muted">${review.review || 'Beautiful handmade craftsmanship.'}</p></div>`).join('') || '<div class="alert alert-light">No reviews yet.</div>');
    $('#addProductToCart').on('click', function () {
      const qty = Number($('#qty').val()) || 1;
      const item = { product_id: product.id, name: product.name, price: product.price, image_url: product.image_url || 'images/product-placeholder.svg' };
      App.addToLocalCart(item, qty);
      $.post('includes/api.php?action=cart_add', { product_id: product.id, quantity: qty }).always(() => App.toast('Added to cart.'));
    });
  }).fail(() => $('#productDetail').html('<div class="col-12"><div class="alert alert-danger">Unable to load product.</div></div>'));
});
