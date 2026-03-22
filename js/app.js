const App = {
  currency: 'PKR',
  cartKey: 'lomi_crochet_cart',
  toast(message, type = 'success') {
    const container = document.querySelector('.toast-container') || document.body.appendChild(Object.assign(document.createElement('div'), {className: 'toast-container position-fixed top-0 end-0 p-3'}));
    const wrapper = document.createElement('div');
    wrapper.innerHTML = `<div class="toast align-items-center text-bg-${type} border-0" role="alert"><div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`;
    container.appendChild(wrapper.firstChild);
    const toast = new bootstrap.Toast(container.lastChild, { delay: 2500 });
    toast.show();
  },
  getCart() {
    return JSON.parse(localStorage.getItem(this.cartKey) || '[]');
  },
  saveCart(cart) {
    localStorage.setItem(this.cartKey, JSON.stringify(cart));
    this.updateCartBadge();
  },
  addToLocalCart(product, quantity = 1) {
    const cart = this.getCart();
    const existing = cart.find(item => item.product_id == product.product_id);
    if (existing) existing.quantity += quantity;
    else cart.push({...product, quantity});
    this.saveCart(cart);
  },
  removeFromLocalCart(productId) {
    this.saveCart(this.getCart().filter(item => item.product_id != productId));
  },
  updateLocalQuantity(productId, quantity) {
    const cart = this.getCart().map(item => item.product_id == productId ? {...item, quantity} : item).filter(item => item.quantity > 0);
    this.saveCart(cart);
  },
  updateCartBadge() {
    const count = this.getCart().reduce((sum, item) => sum + Number(item.quantity), 0);
    $('.cart-count').text(count);
  },
  formatCurrency(value) {
    return `${this.currency} ${Number(value).toLocaleString()}`;
  },
  loadCategories(selectors = []) {
    return $.getJSON('includes/api.php?action=categories').then(({categories}) => {
      selectors.forEach(selector => {
        const element = $(selector);
        if (!element.length) return;
        const opts = categories.map(cat => `<option value="${cat.slug}">${cat.name}</option>`).join('');
        element.each(function(){
          if (this.tagName === 'SELECT') $(this).append(opts);
          else $(this).html(categories.map(cat => `<button class="btn btn-outline-secondary btn-sm category-chip me-2 mb-2" data-category="${cat.slug}">${cat.name}</button>`).join(''));
        });
      });
      return categories;
    });
  },
  renderStars(rating) {
    const rounded = Math.round(Number(rating));
    return Array.from({length: 5}, (_, index) => `<i class="${index < rounded ? 'fas' : 'far'} fa-star"></i>`).join('');
  }
};

$(function() { App.updateCartBadge(); });
